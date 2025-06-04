'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import dynamic from 'next/dynamic';
import { 
  Calendar, 
  Check, 
  FileText, 
  Loader2,
  Clock,
  X 
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { useToast } from '@/hooks/use-toast';
import { pdfApi, companyApi, PDF } from '@/lib/api';
import { Document, Page, pdfjs } from 'react-pdf';

// PDFワーカーの設定（react-pdf v7用）
if (typeof window !== 'undefined') {
  pdfjs.GlobalWorkerOptions.workerSrc = `//cdnjs.cloudflare.com/ajax/libs/pdf.js/${pdfjs.version}/pdf.worker.min.js`;
}

// PDFビューワーを動的にインポート（クライアントサイドのみ）
const PDFViewer = dynamic(() => import('./PDFViewer'), {
  ssr: false,
  loading: () => (
    <div className="flex items-center justify-center h-[600px]">
      <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
    </div>
  ),
});

// アプリケーション設定の型定義
interface AppSettings {
  'general.require_survey': boolean;
  'general.show_booking_option': boolean;
  'survey.title': string;
  'survey.description': string;
  'survey.options': Array<{
    id: number;
    label: string;
  }>;
}

// PDFサムネイルコンポーネント
const PDFThumbnail = ({ pdfUrl, title }: { pdfUrl: string; title: string }) => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [pageSize, setPageSize] = useState({ width: 320, height: 180 });

  const onDocumentLoadSuccess = () => {
    setLoading(false);
    setError(null);
  };

  const onDocumentLoadError = (error: any) => {
    setLoading(false);
    console.error('PDF loading error:', error);
    setError(`PDFの読み込みに失敗しました: ${error.message || 'Unknown error'}`);
  };

  const onPageLoadSuccess = (page: any) => {
    const { width, height } = page.getViewport({ scale: 1 });
    const aspectRatio = width / height;
    
    // 16:9比率を基準に、実際のPDF比率に合わせて調整
    const containerWidth = 320;
    const containerHeight = containerWidth / aspectRatio;
    
    setPageSize({ width: containerWidth, height: containerHeight });
  };

  if (error) {
    return (
      <div className="aspect-[16/9] bg-gray-100 rounded-lg flex flex-col items-center justify-center p-4">
        <FileText className="h-8 w-8 text-gray-400 mb-2" />
        <div className="text-xs text-center text-red-600">{error}</div>
      </div>
    );
  }

  return (
    <div className="aspect-[16/9] bg-gray-100 rounded-lg overflow-hidden relative">
      {loading && (
        <div className="absolute inset-0 flex items-center justify-center">
          <Loader2 className="h-8 w-8 animate-spin text-gray-400" />
        </div>
      )}
      <Document
        file={pdfUrl}
        onLoadSuccess={onDocumentLoadSuccess}
        onLoadError={onDocumentLoadError}
        loading=""
        error=""
      >
        <Page
          pageNumber={1}
          width={pageSize.width}
          renderAnnotationLayer={false}
          renderTextLayer={false}
          onLoadSuccess={onPageLoadSuccess}
          loading=""
          className="[&>canvas]:max-w-full [&>canvas]:!h-auto"
        />
      </Document>
    </div>
  );
};

export default function ViewPageContent({ uuid }: { uuid: string }) {
  const [companyName, setCompanyName] = useState('');
  const [companyData, setCompanyData] = useState<any>(null);
  const [documents, setDocuments] = useState<PDF[]>([]);
  const [interestLevel, setInterestLevel] = useState<string | null>(null);
  const [hasCompletedSurvey, setHasCompletedSurvey] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [showBookingPrompt, setShowBookingPrompt] = useState(false);
  const [hasReachedLastPage, setHasReachedLastPage] = useState(false);
  const [previewUrls, setPreviewUrls] = useState<{ [id: string]: string }>({});
  const [startedDocuments, setStartedDocuments] = useState<string[]>([]);
  const [finishedDocs, setFinishedDocs] = useState<string[]>([]);
  const [selectedDocument, setSelectedDocument] = useState<PDF | null>(null);
  const [showViewer, setShowViewer] = useState(false);
  
  // アプリケーション設定の状態
  const [appSettings, setAppSettings] = useState<AppSettings | null>(null);
  const [settingsLoading, setSettingsLoading] = useState(true);
  
  const router = useRouter();
  const { toast } = useToast();

  // アプリケーション設定を取得
  useEffect(() => {
    const fetchAppSettings = async () => {
      try {
        setSettingsLoading(true);
        const response = await fetch('/api/settings/public', {
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('App Settings loaded:', data.data);
        setAppSettings(data.data);
      } catch (error) {
        console.error('アプリケーション設定の取得に失敗しました:', error);
        // デフォルト設定でフォールバック
        setAppSettings({
          'general.require_survey': true,
          'general.show_booking_option': true,
          'survey.title': '資料をご覧になる前に',
          'survey.description': '現在の興味度をお聞かせください',
          'survey.options': [
            { id: 1, label: '非常に興味がある' },
            { id: 2, label: 'やや興味がある' },
            { id: 3, label: '詳しい情報が必要' },
            { id: 4, label: '興味なし' },
          ],
        });
      } finally {
        setSettingsLoading(false);
      }
    };

    fetchAppSettings();
  }, []);

  useEffect(() => {
    const fetchData = async () => {
      setIsLoading(true);
      try {
        // 公開APIを使用
        const data = await pdfApi.getPublicAll(uuid);
        setCompanyName(data.company.name);
        setCompanyData(data.company);
        setDocuments(data.data);
        // 公開プレビューURLを使用
        const urls: { [id: string]: string } = {};
        for (const doc of data.data) {
          urls[doc.id] = pdfApi.getPublicPreviewUrl(uuid, doc.id);
        }
        setPreviewUrls(urls);
      } catch (error) {
        console.error('データの読み込みに失敗しました:', error);
        setCompanyName('会社名取得エラー');
        setCompanyData(null);
        setDocuments([]);
        setPreviewUrls({});
      } finally {
        setIsLoading(false);
      }
    };
    fetchData();
  }, [uuid]);
  
  // アンケート送信の処理
  const handleSurveySubmit = () => {
    if (!interestLevel) {
      toast({
        title: '選択してください',
        description: '興味のレベルを選択してください',
        variant: 'destructive',
      });
      return;
    }
    
    // バックエンドへの送信シミュレーション
    console.log('アンケート送信 UUID:', uuid, '興味レベル:', interestLevel);
    
    toast({
      title: 'ご回答ありがとうございます',
      description: '資料をご覧いただけます',
    });
    
    setHasCompletedSurvey(true);
  };

  // 最終ページ到達時の処理
  const handleLastPageReached = (docId: string) => {
    console.log(`Document ${docId} last page reached, showing booking prompt`);
    setShowBookingPrompt(true);
  };

  const handleDocumentClick = (document: PDF) => {
    setSelectedDocument(document);
    setShowViewer(true);
  };

  const handleViewerClose = () => {
    setShowViewer(false);
    setSelectedDocument(null);
  };

  // ローディング状態
  if (isLoading || settingsLoading) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center p-8 space-y-4">
        <Loader2 className="h-8 w-8 animate-spin" />
        <p className="text-muted-foreground">読み込み中...</p>
      </div>
    );
  }

  // アンケートが必要かつ未完了の場合
  if (appSettings?.['general.require_survey'] && !hasCompletedSurvey) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center p-8">
        <Card className="w-full max-w-2xl">
          <CardHeader className="text-center space-y-2">
            <CardTitle className="text-2xl">{appSettings['survey.title']}</CardTitle>
            <CardDescription className="text-lg">
              {appSettings['survey.description']}
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            <RadioGroup value={interestLevel || ""} onValueChange={setInterestLevel}>
              {appSettings['survey.options'].map((option) => (
                <div key={option.id} className="flex items-center space-x-3 p-3 rounded-lg border hover:bg-muted/50 transition-colors">
                  <RadioGroupItem value={option.id.toString()} id={`option-${option.id}`} />
                  <label 
                    htmlFor={`option-${option.id}`} 
                    className="flex-1 text-lg cursor-pointer"
                  >
                    {option.label}
                  </label>
                </div>
              ))}
            </RadioGroup>
          </CardContent>
          <CardFooter className="flex justify-center pt-6">
            <Button 
              onClick={handleSurveySubmit}
              size="lg"
              className="px-8"
              disabled={!interestLevel}
            >
              回答して資料を見る
            </Button>
          </CardFooter>
        </Card>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-8">
      <div className="max-w-7xl mx-auto space-y-8">
        {/* ヘッダー */}
        <div className="text-center space-y-4">
          <h1 className="text-4xl font-bold text-gray-900">{companyName}</h1>
          <p className="text-xl text-gray-600">資料一覧</p>
        </div>

        {/* ドキュメント一覧 */}
        <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
          {documents.map((document) => (
            <Card
              key={document.id}
              className="group cursor-pointer transition-all duration-300 hover:shadow-xl hover:scale-105"
              onClick={() => handleDocumentClick(document)}
            >
              <CardHeader className="space-y-4">
                <PDFThumbnail 
                  pdfUrl={previewUrls[document.id] || ''} 
                  title={document.title}
                />
                <div className="space-y-2">
                  <CardTitle className="text-xl group-hover:text-blue-600 transition-colors">
                    {document.title}
                  </CardTitle>
                  <CardDescription className="flex items-center gap-2">
                    <FileText className="h-4 w-4" />
                    {document.file_name}
                  </CardDescription>
                </div>
              </CardHeader>
              <CardFooter className="flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                  <Clock className="h-4 w-4" />
                  <span>推定読了時間: {Math.ceil((document.page_count || 8) * 1.5)}分</span>
                </div>
                <Button variant="outline" size="sm">
                  開く
                </Button>
              </CardFooter>
            </Card>
          ))}
        </div>

        {/* PDFビューワーダイアログ */}
        {showViewer && selectedDocument && (
          <Dialog open={showViewer} onOpenChange={setShowViewer}>
            <DialogContent className="max-w-5xl w-full h-[90vh] p-0">
              <DialogHeader className="p-6 pb-2">
                <DialogTitle className="flex items-center justify-between">
                  <span>{selectedDocument.title}</span>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={handleViewerClose}
                  >
                    <X className="h-4 w-4" />
                  </Button>
                </DialogTitle>
              </DialogHeader>
              <div className="flex-1 p-6 pt-2">
                <PDFViewer
                  pdfUrl={pdfApi.getPublicViewUrl(uuid, selectedDocument.id)}
                  uuid={uuid}
                  documentId={selectedDocument.id}
                  onLastPageReached={handleLastPageReached}
                  onDocumentStarted={(docId) => {
                    if (!startedDocuments.includes(docId)) {
                      setStartedDocuments([...startedDocuments, docId]);
                    }
                  }}
                  onDocumentFinished={(docId) => {
                    if (!finishedDocs.includes(docId)) {
                      setFinishedDocs([...finishedDocs, docId]);
                    }
                  }}
                />
              </div>
            </DialogContent>
          </Dialog>
        )}

        {/* 予約促進ダイアログ */}
        {appSettings?.['general.show_booking_option'] && showBookingPrompt && (
          <Dialog open={showBookingPrompt} onOpenChange={setShowBookingPrompt}>
            <DialogContent className="max-w-md">
              <DialogHeader>
                <DialogTitle className="flex items-center gap-2">
                  <Calendar className="h-5 w-5 text-blue-600" />
                  ミーティングのご案内
                </DialogTitle>
                <DialogDescription>
                  資料をご覧いただき、ありがとうございます。
                  詳細についてお話しさせていただけませんか？
                </DialogDescription>
              </DialogHeader>
              <div className="flex gap-3 pt-4">
                <Button
                  onClick={() => {
                    window.open('https://calendly.com/your-calendar', '_blank');
                    setShowBookingPrompt(false);
                  }}
                  className="flex-1"
                >
                  <Calendar className="mr-2 h-4 w-4" />
                  予約する
                </Button>
                <Button
                  variant="outline"
                  onClick={() => setShowBookingPrompt(false)}
                  className="flex-1"
                >
                  後で検討
                </Button>
              </div>
            </DialogContent>
          </Dialog>
        )}
      </div>
    </div>
  );
}