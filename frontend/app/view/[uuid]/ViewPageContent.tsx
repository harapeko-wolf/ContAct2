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

// PDFビューワーを動的にインポート（クライアントサイドのみ）
const PDFViewer = dynamic(() => import('./PDFViewer'), {
  ssr: false,
  loading: () => (
    <div className="w-full h-full flex items-center justify-center bg-gray-50 min-h-[600px]">
      <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
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

export default function ViewPageContent({ uuid }: { uuid: string }) {
  const [interestLevel, setInterestLevel] = useState<string | null>(null);
  const [hasCompletedSurvey, setHasCompletedSurvey] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [showBookingPrompt, setShowBookingPrompt] = useState(false);
  const [hasReachedLastPage, setHasReachedLastPage] = useState(false);
  const [startedDocuments, setStartedDocuments] = useState<number[]>([]);
  const router = useRouter();
  const { toast } = useToast();

  // ページ読み込みのシミュレーション
  useEffect(() => {
    const timer = setTimeout(() => {
      setIsLoading(false);
    }, 500);
    
    return () => clearTimeout(timer);
  }, []);
  
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
  const handleLastPage = (isLastPage: boolean) => {
    if (isLastPage && !hasReachedLastPage) {
      setHasReachedLastPage(true);
      setShowBookingPrompt(true);
    }
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
              <span className="font-medium">{documentData.companyName}</span>
            </div>
            <div className="flex items-center gap-4">
              <div className="text-sm flex items-center gap-2 bg-blue-50 px-3 py-1.5 rounded-lg">
                <span className="text-muted-foreground">資料数:</span>
                <span className="font-medium text-blue-600">{documentData.documents.length}件</span>
              </div>
            </div>
          </div>

        </div>
      </header>

      {/* メインコンテンツ */}
      <main className="flex-1 flex flex-col items-center py-6 px-4">
        {/* 複数のPDFビューワー */}
        <div className="w-full max-w-5xl">
          <div className="flex gap-2 overflow-x-auto pb-2 px-1 mb-4 sticky top-[64px] z-10">
            {documentData.documents.map((doc, index) => (
              <button
                key={doc.id}
                className={`flex items-center gap-2 px-3 py-2 rounded-lg text-sm whitespace-nowrap transition-colors ${
                  index === 0
                    ? 'bg-blue-600 text-white font-medium shadow-md'
                    : 'bg-gray-50 text-gray-600 hover:bg-gray-100'
                }`}
                onClick={() => {
                  const element = document.getElementById(`pdf-${doc.id}`);
                  element?.scrollIntoView({ behavior: 'smooth' });
                }}
              >
                <FileText className={`h-4 w-4 ${index === 0 ? 'text-white' : ''}`} />
                {doc.title}
                {/* <span className="ml-2 px-2 py-0.5 bg-opacity-20 bg-white rounded text-xs">
                  {doc.totalPages}ページ
                </span> */}
              </button>
            ))}
          </div>

          <div className="space-y-12">
            {documentData.documents.map((doc, index) => (
              <div key={doc.id} id={`pdf-${doc.id}`} className="space-y-4">
                <div className="relative flex items-center justify-between bg-gradient-to-r from-blue-50 to-white p-4 rounded-lg border">
                  <div className="flex items-center gap-4">
                    <div className="bg-blue-100 rounded-lg p-2">
                      <FileText className="h-6 w-6 text-blue-600" />
                    </div>
                    <div>
                      <h2 className="text-xl font-medium">{doc.title}</h2>
                      <p className="text-sm text-muted-foreground">
                        全{doc.totalPages}ページ
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-4">
                    <div className="px-3 py-1.5 bg-blue-100 text-blue-600 rounded-lg font-medium text-sm flex items-center gap-2">
                      <span className="text-xs text-blue-500">資料</span>
                      <span>{index + 1} / {documentData.documents.length}</span>
                    </div>
                  </div>
                </div>
                <div className="relative">
                <PDFViewer 
                  pdfUrl={doc.url}
                  key={doc.id}
                  onLastPage={index === documentData.documents.length - 1 ? handleLastPage : undefined}
                />
                {!startedDocuments.includes(doc.id) && (
                  <div className="absolute inset-0 bg-white/10 backdrop-blur-sm flex items-center justify-center">
                    <Button
                      size="lg"
                      className="gap-2"
                      onClick={() => {
                        setStartedDocuments(prev => [...prev, doc.id]);
                        toast({
                          title: '閲覧を開始しました',
                          description: `${doc.title}の閲覧データを記録しています`,
                        });
                      }}
                    >
                      <FileText className="h-4 w-4" />
                      閲覧
                    </Button>
                    
                  </div>
                
                )}
              </div>
              </div>
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
      </main>

      {/* フッター */}
      <footer className="py-4 px-6 border-t text-center text-sm text-muted-foreground">
        <p>ContActによるセキュアな資料共有</p>
      </footer>
    </div>
  );
}