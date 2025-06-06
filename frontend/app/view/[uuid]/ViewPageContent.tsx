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

// 型定義
interface SurveyOption {
  id: number;
  label: string;
  score: number;
}

interface SurveySettings {
  title: string;
  description: string;
  options: SurveyOption[];
}

interface PublicSettings {
  'survey.title'?: string;
  'survey.description'?: string;
  'survey.options'?: SurveyOption[];
  'general.require_survey'?: boolean;
  'general.show_booking_option'?: boolean;
}

// デフォルトのアンケート設定（フォールバック用）
const defaultSurveySettings: SurveySettings = {
  title: '資料を表示する前に',
  description: '現在の興味レベルをお聞かせください',
  options: [
    { id: 1, label: '非常に興味がある', score: 100 },
    { id: 2, label: 'やや興味がある', score: 75 },
    { id: 3, label: '詳しい情報が必要', score: 50 },
    { id: 4, label: '興味なし', score: 0 },
  ],
};

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
  
  // アンケート設定の状態
  const [surveySettings, setSurveySettings] = useState<SurveySettings>(defaultSurveySettings);
  const [requireSurvey, setRequireSurvey] = useState(true);
  const [showBookingOption, setShowBookingOption] = useState(true);
  
  const router = useRouter();
  const { toast } = useToast();

  // 公開設定を取得する関数
  const fetchPublicSettings = async () => {
    try {
      const response = await fetch('/api/settings/public');
      if (!response.ok) {
        throw new Error('設定の取得に失敗しました');
      }
      const data = await response.json();
      const settings: PublicSettings = data.data;

      // アンケート設定を更新
      setSurveySettings({
        title: settings['survey.title'] || defaultSurveySettings.title,
        description: settings['survey.description'] || defaultSurveySettings.description,
        options: settings['survey.options'] || defaultSurveySettings.options,
      });

      // 機能設定を更新
      setRequireSurvey(settings['general.require_survey'] ?? true);
      setShowBookingOption(settings['general.show_booking_option'] ?? true);

      console.log('公開設定を取得しました:', settings);
    } catch (error) {
      console.error('公開設定の取得に失敗しました:', error);
      // エラー時はデフォルト設定を使用
      setSurveySettings(defaultSurveySettings);
      setRequireSurvey(true);
      setShowBookingOption(true);
    }
  };

  useEffect(() => {
    const fetchData = async () => {
      setIsLoading(true);
      try {
        // 公開設定とPDFデータを並行取得
        await Promise.all([
          fetchPublicSettings(),
          (async () => {
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
          })()
        ]);
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
  const handleSurveySubmit = async () => {
    if (!interestLevel) {
      toast({
        title: '選択してください',
        description: '興味のレベルを選択してください',
        variant: 'destructive',
      });
      return;
    }
    
    // ドキュメントが読み込まれているかチェック
    if (documents.length === 0) {
      console.warn('ドキュメントがまだ読み込まれていません');
      toast({
        title: 'しばらくお待ちください',
        description: 'ドキュメントの読み込み中です',
        variant: 'destructive',
      });
      return;
    }
    
    try {
      // 選択されたオプションの詳細を取得
      const selectedOption = surveySettings.options.find(option => option.id.toString() === interestLevel);
      if (!selectedOption) {
        toast({
          title: 'エラーが発生しました',
          description: '選択されたオプションが見つかりません',
          variant: 'destructive',
        });
        return;
      }
      
      // 最初のドキュメントにフィードバックを送信
      const firstDocument = documents[0];
      
      console.log('フィードバック送信開始:', {
        documentId: firstDocument.id,
        companyId: uuid,
        selectedOption: selectedOption,
        documentTitle: firstDocument.title
      });
      
      const response = await fetch(`/api/companies/${uuid}/pdfs/${firstDocument.id}/feedback`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          feedback_type: 'survey_response',
          content: `${selectedOption.label} (スコア: ${selectedOption.score})`,
          interest_level: selectedOption.score,
          selected_option: {
            id: selectedOption.id,
            label: selectedOption.label,
            score: selectedOption.score
          }
        }),
      });

      if (!response.ok) {
        const errorData = await response.text();
        console.error('フィードバック送信エラー詳細:', {
          status: response.status,
          statusText: response.statusText,
          errorData: errorData
        });
        throw new Error(`フィードバックの送信に失敗しました (${response.status}: ${response.statusText})`);
      }

      const result = await response.json();
      console.log('フィードバック送信成功:', result);
      
      toast({
        title: 'ご回答ありがとうございます',
        description: '資料をご覧いただけます',
      });
      
      setHasCompletedSurvey(true);
      
    } catch (error) {
      console.error('フィードバック送信エラー:', error);
      toast({
        title: 'エラーが発生しました',
        description: 'フィードバックの送信に失敗しましたが、資料はご覧いただけます',
        variant: 'destructive',
      });
      // エラーが発生してもアンケートは完了扱いにする
      setHasCompletedSurvey(true);
    }
  };

  // 最終ページ到達時の処理
  const handleLastPageReached = (docId: string) => {
    console.log(`Document ${docId} last page reached, showing booking prompt`);
    if (showBookingOption) {
      setShowBookingPrompt(true);
    }
  };

  const handleDocumentClick = (document: PDF) => {
    setSelectedDocument(document);
    setShowViewer(true);
  };

  const handleViewerClose = () => {
    setShowViewer(false);
    setSelectedDocument(null);
  };

  if (isLoading) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center">
        <FileText className="h-16 w-16 text-blue-600 mb-4" />
        <h1 className="text-2xl font-bold mb-2">資料を読み込み中</h1>
        <p className="text-muted-foreground mb-6">しばらくお待ちください</p>
        <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
      </div>
    );
  }

  // アンケート未回答かつアンケート必須の場合はアンケートを表示
  if (!hasCompletedSurvey && requireSurvey) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center p-4">
        <div className="w-full max-w-md">
          <Card className="shadow-lg">
            <CardHeader>
              <CardTitle className="text-xl">{surveySettings.title}</CardTitle>
              <CardDescription>
                {surveySettings.description}
              </CardDescription>
            </CardHeader>
            <CardContent>
              <RadioGroup value={interestLevel || ''} onValueChange={setInterestLevel}>
                {surveySettings.options.map(option => (
                  <div key={option.id} className="flex items-center space-x-2 my-3">
                    <RadioGroupItem value={option.id.toString()} id={`option-${option.id}`} />
                    <label htmlFor={`option-${option.id}`} className="text-sm font-medium leading-none cursor-pointer">
                      {option.label}
                    </label>
                  </div>
                ))}
              </RadioGroup>
            </CardContent>
            <CardFooter>
              <Button onClick={handleSurveySubmit} className="w-full">
                資料を表示
              </Button>
            </CardFooter>
          </Card>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex flex-col">
      {/* ヘッダー */}
      <header className="border-b bg-white py-3 px-6 sticky top-0 z-10">
        <div className="flex items-center justify-between max-w-5xl mx-auto">
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-2 border-r pr-4 py-1">
              <FileText className="h-5 w-5 text-blue-600" />
              <span className="font-medium">{companyName}</span>
            </div>
            <div className="flex items-center gap-4">
              <div className="text-sm flex items-center gap-2 bg-blue-50 px-3 py-1.5 rounded-lg">
                <span className="text-muted-foreground">資料数:</span>
                <span className="font-medium text-blue-600">{documents.length}件</span>
              </div>
            </div>
          </div>

        </div>
      </header>

      {/* メインコンテンツ */}
      <main className="flex-1 flex flex-col items-center py-6 px-4">
        {/* 複数のPDFビューワー */}
        <div className="w-full max-w-5xl">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            {documents.map((doc) => (
              <Card
                key={doc.id}
                className="cursor-pointer hover:shadow-lg transition-shadow"
                onClick={() => handleDocumentClick(doc)}
              >
                <CardContent className="p-4">
                  {previewUrls[doc.id] ? (
                    <PDFThumbnail pdfUrl={previewUrls[doc.id]} title={doc.title} />
                  ) : (
                    <div className="aspect-[16/9] bg-gray-100 rounded-lg flex items-center justify-center mb-3">
                      <Loader2 className="h-8 w-8 animate-spin text-gray-400" />
                    </div>
                  )}
                  <h3 className="font-medium text-sm truncate mt-3">{doc.title}</h3>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>

        {/* 固定の予約ボタン */}
        {showBookingOption && (
          <div className="fixed bottom-4 right-4 z-50">
            <Button
              onClick={() => {
                if (companyData?.booking_link) {
                  const bookingUrl = new URL(companyData.booking_link);
                  bookingUrl.searchParams.set('company_id', uuid);
                  bookingUrl.searchParams.set('guest_comment', uuid);
                  window.location.href = bookingUrl.toString();
                } else {
                  setShowBookingPrompt(true);
                }
              }}
              className="shadow-lg gap-2"
              size="lg"
            >
              <Calendar className="h-4 w-4" />
              候補の日時を見る
            </Button>
          </div>
        )}

        {/* ミーティング予約モーダル */}
        {showBookingPrompt && showBookingOption && (
          <Dialog open={showBookingPrompt} onOpenChange={setShowBookingPrompt}>
            <DialogContent className="sm:max-w-[500px]">
              <DialogHeader className="text-center">
                <DialogTitle className="text-xl font-bold">ご覧いただきありがとうございます</DialogTitle>
                <DialogDescription>
                  より詳しくご説明させていただきます
                </DialogDescription>
              </DialogHeader>
              <div className="py-4">
                <div className="flex gap-3">
                  <Button onClick={() => {
                    if (companyData?.booking_link) {
                      const bookingUrl = new URL(companyData.booking_link);
                      bookingUrl.searchParams.set('company_id', uuid);
                      bookingUrl.searchParams.set('guest_comment', uuid);
                      window.location.href = bookingUrl.toString();
                    } else {
                      window.location.href = '/booking';
                    }
                  }} className="flex-1 gap-2">
                    <Calendar className="h-4 w-4" />
                    候補の日時を見る
                  </Button>
                  <Button variant="outline" onClick={() => setShowBookingPrompt(false)} className="flex-1">
                    後で検討する
                  </Button>
                </div>
              </div>
            </DialogContent>
          </Dialog>
        )}

        <Dialog open={showViewer} onOpenChange={handleViewerClose}>
          <DialogContent className="max-w-7xl w-[95vw] h-[auto] max-h-[90vh] sm:w-[85vw] sm:h-[auto] sm:max-h-[85vh] p-0 flex flex-col">
            <DialogHeader className="flex flex-row items-center justify-between p-3 border-b shrink-0">
              <div className="min-w-0 flex-1">
                <DialogTitle className="text-base sm:text-lg font-semibold truncate">
                  {selectedDocument?.title}
                </DialogTitle>
                <DialogDescription className="text-xs sm:text-sm text-gray-500">
                  PDF資料を閲覧中
                </DialogDescription>
              </div>
            </DialogHeader>
            <div className="flex-1 overflow-hidden min-h-0">
              {selectedDocument && (
                <PDFViewer
                  documentId={selectedDocument.id}
                  companyId={uuid}
                  isActive={showViewer}
                  onLastPageReached={handleLastPageReached}
                />
              )}
            </div>
          </DialogContent>
        </Dialog>
      </main>

      {/* フッター */}
      <footer className="py-4 px-6 border-t text-center text-sm text-muted-foreground">
        <p>ContActによるセキュアな資料共有</p>
      </footer>
    </div>
  );
}