'use client';

import React, { useState, useEffect, useRef } from 'react';
import { ChevronLeft, ChevronRight, ZoomIn, ZoomOut, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Document, Page, pdfjs } from 'react-pdf';
import { cn } from '@/lib/utils';

// PDFワーカーの設定
pdfjs.GlobalWorkerOptions.workerSrc = new URL(
  'pdfjs-dist/build/pdf.worker.min.js',
  import.meta.url,
).toString();

export interface PDFViewerProps {
  pdfUrl: string;
  onLastPage?: (isLastPage: boolean) => void;
  isActive?: boolean;
}

const PDFViewer: React.FC<PDFViewerProps> = ({ pdfUrl, onLastPage, isActive }) => {
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(0);
  const [scale, setScale] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [pageWidth, setPageWidth] = useState(0);
  const containerRef = useRef<HTMLDivElement>(null);

  // コンテナのサイズに応じてPDFのスケールを調整
  useEffect(() => {
    const updateScale = () => {
      if (containerRef.current) {
        const containerWidth = containerRef.current.clientWidth - 48; // padding分を考慮
        setPageWidth(containerWidth);
      }
    };

    updateScale();
    window.addEventListener('resize', updateScale);
    return () => window.removeEventListener('resize', updateScale);
  }, []);

  // PDFの読み込みと表示の処理
  useEffect(() => {
    const initPDF = async () => {
      try {
        setIsLoading(true);
        setError(null);
      } catch (error) {
        console.error('PDFの読み込みエラー:', error);
        setError('PDFの読み込みに失敗しました');
      } finally {
        setIsLoading(false);
      }
    };
    initPDF();
  }, [pdfUrl]);

  // ページ移動の処理
  const handlePrevPage = () => {
    if (currentPage > 1) {
      const newPage = currentPage - 1;
      setCurrentPage(newPage);
      onLastPage?.(newPage === totalPages);
    }
  };

  const handleNextPage = () => {
    if (currentPage < totalPages) {
      const newPage = currentPage + 1;
      setCurrentPage(newPage);
      onLastPage?.(newPage === totalPages);
    }
  };

  // ズーム処理
  const handleZoomIn = () => {
    setScale(prev => Math.min(prev + 0.1, 2));
  };

  const handleZoomOut = () => {
    setScale(prev => Math.max(prev - 0.1, 0.5));
  };

  // PDFドキュメントの読み込み完了時の処理
  const onDocumentLoadSuccess = ({ numPages }: { numPages: number }) => {
    setTotalPages(numPages);
    setIsLoading(false);
  };
  return (
    <div className="flex flex-col w-full bg-white rounded-lg shadow-lg overflow-hidden" ref={containerRef}>
      {/* ナビゲーションコントロール */}
      <div className="flex flex-col sm:flex-row items-center justify-between p-2 border-b bg-gray-50 gap-2">
        <div className="flex items-center gap-2 w-full sm:w-auto justify-center sm:justify-start">
          <Button
            variant="outline"
            size="sm"
            onClick={handlePrevPage}
            disabled={currentPage <= 1}
            className="min-w-[80px]"
          >
            <ChevronLeft className="h-4 w-4" />
            <span className="ml-1">前へ</span>
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={handleNextPage}
            disabled={currentPage >= totalPages}
            className="min-w-[80px]"
          >
            <span className="mr-1">次へ</span>
            <ChevronRight className="h-4 w-4" />
          </Button>
          <span className="mx-2 min-w-[80px] text-center">
            {currentPage} / {totalPages} ページ
          </span>
        </div>
        <div className="flex items-center gap-2 justify-center">
          <Button
            variant="outline"
            size="sm"
            onClick={handleZoomOut}
            disabled={scale <= 0.5}
          >
            <ZoomOut className="h-4 w-4" />
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={handleZoomIn}
            disabled={scale >= 2}
          >
            <ZoomIn className="h-4 w-4" />
          </Button>
          <span className="min-w-[60px] text-center">{Math.round(scale * 100)}%</span>
        </div>
      </div>

      {/* PDFビューワー本体 */}
      <div className="flex-1 overflow-auto p-4">
        <div
          style={{
            transform: `scale(${scale})`,
            transformOrigin: 'top left',
            transition: 'transform 0.2s',
            width: pageWidth ? `${pageWidth}px` : 'auto',
            margin: '0 auto',
          }}
        >
          {error ? (
            <div className="bg-red-50 text-red-600 p-4 rounded">
              {error}
            </div>
          ) : (
            <Document
              file={pdfUrl}
              onLoadSuccess={onDocumentLoadSuccess}
              loading={
                <div className="bg-gray-100 rounded min-h-[800px] flex items-center justify-center">
                  <div className="flex flex-col items-center gap-4">
                    <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
                    <p className="text-gray-500">PDFを読み込み中...</p>
                  </div>
                </div>
              }
              error={
                <div className="bg-red-50 text-red-600 p-4 rounded">
                  PDFの読み込みに失敗しました
                </div>
              }
            >
              <Page
                pageNumber={currentPage}
                scale={scale}
                width={pageWidth}
                renderAnnotationLayer={false}
                renderTextLayer={false}
                loading={
                  <div className="bg-gray-100 rounded min-h-[800px] flex items-center justify-center">
                    <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
                  </div>
                }
              />
            </Document>
          )}
        </div>
      </div>
    </div>
  );
};

export default PDFViewer;