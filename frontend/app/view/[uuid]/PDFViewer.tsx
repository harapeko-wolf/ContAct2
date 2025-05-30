'use client';

import React, { useState, useEffect, useRef } from 'react';
import { ChevronLeft, ChevronRight, ZoomIn, ZoomOut, Loader2, Maximize2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Document, Page, pdfjs } from 'react-pdf';
import { cn } from '@/lib/utils';
import { pdfApi } from '@/lib/api';

// PDFワーカーの設定
pdfjs.GlobalWorkerOptions.workerSrc = `//cdnjs.cloudflare.com/ajax/libs/pdf.js/${pdfjs.version}/pdf.worker.min.js`;

export interface PDFViewerProps {
  documentId: string;
  companyId: string;
  isActive: boolean;
}

const PDFViewer: React.FC<PDFViewerProps> = ({ documentId, companyId, isActive }) => {
  const [numPages, setNumPages] = useState<number>(0);
  const [pageNumber, setPageNumber] = useState<number>(1);
  const [scale, setScale] = useState<number>(1.0);
  const [pdfUrl, setPdfUrl] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const containerRef = useRef<HTMLDivElement>(null);
  const [pageStartTime, setPageStartTime] = useState<number | null>(null);
  const [isPdfLoaded, setIsPdfLoaded] = useState(false);
  
  // 最新の値を保持するためのref
  const currentPageRef = useRef(pageNumber);
  const pageStartTimeRef = useRef(pageStartTime);
  const isPdfLoadedRef = useRef(isPdfLoaded);
  const hasLoggedRef = useRef(false); // 重複ログ防止フラグ

  // refを最新の値で更新
  useEffect(() => {
    currentPageRef.current = pageNumber;
  }, [pageNumber]);

  useEffect(() => {
    pageStartTimeRef.current = pageStartTime;
  }, [pageStartTime]);

  useEffect(() => {
    isPdfLoadedRef.current = isPdfLoaded;
  }, [isPdfLoaded]);

  // PDFの読み込みと表示の処理
  useEffect(() => {
    const fetchPdfUrl = async () => {
      try {
        const url = pdfApi.getPublicPreviewUrl(companyId, documentId);
        setPdfUrl(url);
      } catch (error) {
        console.error('Failed to fetch PDF URL:', error);
        setError('PDFの読み込みに失敗しました');
      } finally {
        setLoading(false);
      }
    };

    fetchPdfUrl();
  }, [companyId, documentId]);

  // PDF閲覧ログを記録する関数
  const logPageView = async (pageNumber: number, duration: number) => {
    if (!documentId || !companyId || duration <= 0) {
      console.error('Invalid log parameters:', { documentId, companyId, pageNumber, duration });
      return;
    }
    try {
      console.log('Logging page view:', { documentId, companyId, pageNumber, duration });
      await pdfApi.logView(companyId, documentId, {
        page_number: pageNumber,
        view_duration: duration,
      });
    } catch (error) {
      console.error('Failed to log page view:', error);
    }
  };

  // ページ変更時の処理（前のページのログを記録してから新しいページに移動）
  const changePage = (offset: number) => {
    const newPageNumber = Math.min(Math.max(1, pageNumber + offset), numPages);
    
    if (newPageNumber === pageNumber) {
      return; // ページが変わらない場合は何もしない
    }

    // 現在のページの閲覧時間をログに記録
    if (pageStartTime && isPdfLoaded && !hasLoggedRef.current) {
      const duration = Math.floor((Date.now() - pageStartTime) / 1000);
      if (duration > 0) {
        hasLoggedRef.current = true; // ログ送信フラグを設定
        logPageView(pageNumber, duration);
        console.log(`Page ${pageNumber} logged with duration: ${duration}s`);
      }
    }

    // 新しいページに移動
    setPageNumber(newPageNumber);
    // 新しいページの閲覧開始時間をリセット
    setPageStartTime(Date.now());
    // ログフラグをリセット
    hasLoggedRef.current = false;
    
    console.log(`Page changed from ${pageNumber} to ${newPageNumber}, timer reset`);
  };

  // ズーム処理
  const zoomIn = () => {
    setScale(prevScale => {
      const newScale = Math.min(prevScale + 0.1, 2.0); // 最大200%
      return newScale;
    });
  };

  const zoomOut = () => {
    setScale(prevScale => {
      const newScale = Math.max(prevScale - 0.1, 0.2); // 最小20%
      return newScale;
    });
  };

  // 横幅フィット機能
  const fitToWidth = () => {
    setScale(1.0);
  };

  // PDFドキュメントの読み込み完了時の処理
  const onDocumentLoadSuccess = ({ numPages }: { numPages: number }) => {
    setNumPages(numPages);
    setIsPdfLoaded(true);
    // PDF読み込み完了後に初回ページの閲覧開始時間を設定
    setPageStartTime(Date.now());
    console.log('PDF loaded successfully, timer started for page 1');
  };

  // ページの読み込み完了時の処理（実際のページサイズを取得）
  const onPageLoadSuccess = (page: any) => {
    const { width, height } = page.getViewport({ scale: 1 });
    console.log('PDF Page Size:', { width, height, ratio: width / height });
  };

  // コンポーネントのアンマウント時（モーダルを閉じる時）のみ
  useEffect(() => {
    return () => {
      // コンポーネントが完全にアンマウントされる時のみログを記録
      if (pageStartTimeRef.current && isPdfLoadedRef.current && currentPageRef.current && !hasLoggedRef.current) {
        const duration = Math.floor((Date.now() - pageStartTimeRef.current) / 1000);
        if (duration > 0) {
          logPageView(currentPageRef.current, duration);
          console.log('Component unmounting, logging final page view');
        }
      }
    };
  }, []); // 依存配列を空にして、真のアンマウント時のみ実行

  // isActiveが変更された時（モーダルが閉じられた時）
  useEffect(() => {
    if (!isActive && pageStartTime && isPdfLoaded && pageNumber && !hasLoggedRef.current) {
      const duration = Math.floor((Date.now() - pageStartTime) / 1000);
      if (duration > 0) {
        hasLoggedRef.current = true; // ログ送信フラグを設定
        logPageView(pageNumber, duration);
        console.log('Modal closed, logging final page view');
      }
      // リセット
      setPageStartTime(null);
      setIsPdfLoaded(false);
    }
  }, [isActive]); // isActiveのみを監視

  if (loading) {
    return (
      <div className="flex items-center justify-center h-[600px]">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
      </div>
    );
  }

  if (!pdfUrl) {
    return (
      <div className="flex items-center justify-center h-[600px] text-red-500">
        {error}
      </div>
    );
  }

  return (
    <div className="flex flex-col w-full bg-white overflow-hidden" ref={containerRef}>
      {/* ナビゲーションコントロール */}
      <div className="flex flex-col sm:flex-row items-center justify-between p-2 sm:p-3 border-b bg-gray-50 gap-2 shrink-0">
        <div className="flex items-center gap-1 sm:gap-2 w-full sm:w-auto justify-center sm:justify-start">
          <Button
            variant="outline"
            size="sm"
            onClick={() => changePage(-1)}
            disabled={pageNumber <= 1}
            className="min-w-[60px] sm:min-w-[80px] text-xs sm:text-sm"
          >
            <ChevronLeft className="h-3 w-3 sm:h-4 sm:w-4" />
            <span className="ml-1">前へ</span>
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => changePage(1)}
            disabled={pageNumber >= numPages}
            className="min-w-[60px] sm:min-w-[80px] text-xs sm:text-sm"
          >
            <span className="mr-1">次へ</span>
            <ChevronRight className="h-3 w-3 sm:h-4 sm:w-4" />
          </Button>
          <span className="mx-1 sm:mx-2 min-w-[80px] sm:min-w-[100px] text-center text-xs sm:text-sm">
            {pageNumber} / {numPages} ページ
          </span>
        </div>
        <div className="flex items-center gap-1 sm:gap-2 justify-center">
          <Button
            variant="outline"
            size="sm"
            onClick={zoomOut}
            disabled={scale <= 0.2}
            className="p-1 sm:p-2"
          >
            <ZoomOut className="h-3 w-3 sm:h-4 sm:w-4" />
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={zoomIn}
            disabled={scale >= 2.0}
            className="p-1 sm:p-2"
          >
            <ZoomIn className="h-3 w-3 sm:h-4 sm:w-4" />
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={fitToWidth}
            className="p-1 sm:p-2"
            title="100%表示"
          >
            <Maximize2 className="h-3 w-3 sm:h-4 sm:w-4" />
          </Button>
          <span className="min-w-[50px] sm:min-w-[60px] text-center text-xs sm:text-sm">{Math.round(scale * 100)}%</span>
        </div>
      </div>

      {/* PDFビューワー本体 */}
      <div className="overflow-auto p-2 flex justify-center min-h-0 flex-1">
        <div className="flex justify-center w-full max-w-full">
          <Document
            file={pdfUrl}
            onLoadSuccess={onDocumentLoadSuccess}
            loading={
              <div className="rounded aspect-[16/9] w-full max-w-[600px] flex items-center justify-center">
                <div className="flex flex-col items-center gap-4">
                  <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
                  <p className="text-gray-500">PDFを読み込み中...</p>
                </div>
              </div>
            }
            error={
              <div className="bg-red-50 text-red-600 p-4 rounded aspect-[16/9] w-full max-w-[600px] flex items-center justify-center">
                <div className="text-center">
                  <p className="font-medium">PDFの読み込みに失敗しました</p>
                  <p className="text-sm mt-2">ファイルが破損しているか、サポートされていない形式です</p>
                </div>
              </div>
            }
          >
            <div className="flex justify-center w-full">
              <Page
                pageNumber={pageNumber}
                scale={scale}
                renderAnnotationLayer={false}
                renderTextLayer={false}
                onLoadSuccess={onPageLoadSuccess}
                loading={
                  <div className="bg-gray-100 rounded aspect-[16/9] w-full max-w-[600px] flex items-center justify-center">
                    <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
                  </div>
                }
                className="[&>canvas]:max-w-full [&>canvas]:!h-auto [&>canvas]:w-full"
              />
            </div>
          </Document>
        </div>
      </div>
    </div>
  );
};

export default PDFViewer;