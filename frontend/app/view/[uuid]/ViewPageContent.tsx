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

// PDFワーカーの設定
pdfjs.GlobalWorkerOptions.workerSrc = `//cdnjs.cloudflare.com/ajax/libs/pdf.js/${pdfjs.version}/pdf.worker.min.js`;

// PDFビューワーを動的にインポート（クライアントサイドのみ）
const PDFViewer = dynamic(() => import('./PDFViewer'), {
  ssr: false,
  loading: () => (
    <div className="flex items-center justify-center h-[600px]">
      <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
    </div>
  ),
});

// デモ用のドキュメントデータ
const documentData = {
  companyName: '株式会社サンプル',
  documents: [
    {
      id: 1,
      title: '製品概要資料',
      url: '/sample.pdf',
      totalPages: 8,
    },
    {
      id: 2,
      title: '技術仕様書',
      url: '/kikaku_sample.pdf',
      totalPages: 12,
    }
  ]
};

// アンケートの選択肢
const surveyOptions = [
  { id: 'very-interested', label: '非常に興味がある', value: 'very_interested' },
  { id: 'somewhat-interested', label: 'やや興味がある', value: 'somewhat_interested' },
  { id: 'need-more-info', label: '詳しい情報が必要', value: 'need_more_info' },
  { id: 'not-interested', label: '興味なし', value: 'not_interested' },
];

// PDFサムネイルコンポーネント
const PDFThumbnail = ({ pdfUrl, title }: { pdfUrl: string; title: string }) => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);
  const [pageSize, setPageSize] = useState({ width: 320, height: 180 });

  const onDocumentLoadSuccess = () => {
    setLoading(false);
  };

  const onDocumentLoadError = () => {
    setLoading(false);
    setError(true);
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
      <div className="aspect-[16/9] bg-gray-100 rounded-lg flex items-center justify-center">
        <FileText className="h-12 w-12 text-gray-400" />
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
          height={pageSize.height}
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
  const router = useRouter();
  const { toast } = useToast();

  useEffect(() => {
    const fetchData = async () => {
      setIsLoading(true);
      try {
        const company = await companyApi.getById(uuid);
        setCompanyName(company.name);
        const pdfs = await pdfApi.getAll(uuid);
        setDocuments(pdfs.data);
        // プレビューURLも取得
        const urls: { [id: string]: string } = {};
        for (const doc of pdfs.data) {
          urls[doc.id] = await pdfApi.getPreviewUrl(uuid, doc.id);
        }
        setPreviewUrls(urls);
      } catch {
        setCompanyName('会社名取得エラー');
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
  const handleLastPage = (docId: string, isLastPage: boolean) => {
    if (isLastPage) {
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

  // アンケート未回答の場合はアンケートを表示
  if (!hasCompletedSurvey) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center p-4">
        <div className="w-full max-w-md">
          <Card className="shadow-lg">
            <CardHeader>
              <CardTitle className="text-xl">資料を表示する前に</CardTitle>
              <CardDescription>
                現在の興味レベルをお聞かせください
              </CardDescription>
            </CardHeader>
            <CardContent>
              <RadioGroup value={interestLevel || ''} onValueChange={setInterestLevel}>
                {surveyOptions.map(option => (
                  <div key={option.id} className="flex items-center space-x-2 my-3">
                    <RadioGroupItem value={option.value} id={option.id} />
                    <label htmlFor={option.id} className="text-sm font-medium leading-none cursor-pointer">
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
        <div className="fixed bottom-4 right-4 z-50">
          <Button
            onClick={() => setShowBookingPrompt(true)}
            className="shadow-lg gap-2"
            size="lg"
          >
            <Calendar className="h-4 w-4" />
            候補の日時を見る
          </Button>
        </div>

        {/* ミーティング予約モーダル */}
        {showBookingPrompt && (
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
                  <Button onClick={() => window.location.href = '/booking'} className="flex-1 gap-2">
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
          <DialogContent className="max-w-5xl w-[95vw] h-[auto] max-h-[90vh] sm:w-[85vw] sm:h-[auto] sm:max-h-[85vh] p-0 flex flex-col">
            <DialogHeader className="flex flex-row items-center justify-between p-3 border-b shrink-0">
              <div className="min-w-0 flex-1">
                <DialogTitle className="text-base sm:text-lg font-semibold truncate">
                  {selectedDocument?.title}
                </DialogTitle>
                <DialogDescription className="text-xs sm:text-sm text-gray-500">
                  PDF資料を閲覧中
                </DialogDescription>
              </div>
              {/* <Button
                variant="ghost"
                size="icon"
                onClick={handleViewerClose}
                className="h-8 w-8 rounded-full"
              >
                <X className="h-4 w-4" />
              </Button> */}
            </DialogHeader>
            <div className="flex-1 overflow-hidden min-h-0">
              {selectedDocument && (
                <PDFViewer
                  documentId={selectedDocument.id}
                  companyId={uuid}
                  isActive={showViewer}
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