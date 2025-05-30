'use client';

import { useState, useEffect } from 'react';
import { FileText, Loader2 } from 'lucide-react';
import { pdfApi } from '@/lib/api';
import Cookies from 'js-cookie';

interface PDFThumbnailProps {
  companyId: string;
  pdfId: string;
  className?: string;
  isPublic?: boolean; // 公開用かどうか
}

export function PDFThumbnail({ companyId, pdfId, className = '', isPublic = false }: PDFThumbnailProps) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);
  const [thumbnailUrl, setThumbnailUrl] = useState<string | null>(null);

  useEffect(() => {
    if (!pdfId) {
      setError(true);
      setLoading(false);
      return;
    }

    let objectUrl: string | null = null;

    const loadPdfData = async () => {
      try {
        setLoading(true);
        setError(false);

        let pdfData: Blob;

        if (isPublic) {
          // 公開用は認証なしでフェッチ
          const response = await fetch(pdfApi.getPublicPreviewUrl(companyId, pdfId));
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
          }
          pdfData = await response.blob();
        } else {
          // 管理画面用は認証付きでfetch
          const token = Cookies.get('token');
          const response = await fetch(pdfApi.getPreviewUrl(companyId, pdfId), {
            headers: {
              'Authorization': `Bearer ${token}`,
            },
            credentials: 'include',
          });
          
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
          }
          pdfData = await response.blob();
        }

        // BlobからオブジェクトURLを作成
        objectUrl = URL.createObjectURL(pdfData);
        setThumbnailUrl(objectUrl);
        setLoading(false);
      } catch (error) {
        console.error('PDF取得エラー:', error);
        setError(true);
        setLoading(false);
      }
    };

    loadPdfData();

    // クリーンアップ: コンポーネントがアンマウントされる時にオブジェクトURLを削除
    return () => {
      if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
      }
    };
  }, [companyId, pdfId, isPublic]);

  if (error) {
    return (
      <div className={`bg-gray-100 rounded-lg flex flex-col items-center justify-center aspect-[4/3] p-4 ${className}`}>
        <FileText className="h-12 w-12 text-gray-400 mb-2" />
        <span className="text-xs text-gray-500">PDF</span>
      </div>
    );
  }

  if (loading || !thumbnailUrl) {
    return (
      <div className={`bg-gray-100 rounded-lg flex items-center justify-center aspect-[4/3] ${className}`}>
        <Loader2 className="h-8 w-8 animate-spin text-gray-400" />
      </div>
    );
  }

  return (
    <div className={`bg-white rounded-lg overflow-hidden relative aspect-[4/3] border shadow-sm ${className}`}>
      {/* PDF埋め込み表示 */}
      <embed
        src={`${thumbnailUrl}#view=FitH&toolbar=0&navpanes=0&scrollbar=0`}
        type="application/pdf"
        className="w-full h-full"
      />
      {/* フォールバック: embedが使えない場合の代替表示 */}
      <div className="absolute inset-0 bg-gray-50 flex flex-col items-center justify-center pointer-events-none opacity-0 hover:opacity-100 transition-opacity">
        <FileText className="h-8 w-8 text-gray-400 mb-1" />
        <span className="text-xs text-gray-500">PDFプレビュー</span>
      </div>
    </div>
  );
} 