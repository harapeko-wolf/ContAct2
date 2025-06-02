'use client';

import { useState, useEffect } from 'react';
import { useParams, useRouter } from 'next/navigation';
import {
  DndContext,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
  DragEndEvent,
} from '@dnd-kit/core';
import {
  arrayMove,
  SortableContext,
  sortableKeyboardCoordinates,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import {
  useSortable,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Trash2, Upload, Download, Eye, X, ChevronLeft, ChevronRight, GripVertical } from 'lucide-react';
import { pdfApi, companyApi, type PDF, type Company } from '@/lib/api';
import { toast } from 'sonner';
import { PDFThumbnail } from '@/components/PDFThumbnail';
import AdminLayout from '@/components/admin/layout';
import Cookies from 'js-cookie';

interface PDFCardProps {
  pdf: PDF;
  companyId: string;
  onDelete: (id: string) => void;
  onPreview: (pdf: PDF) => void;
  onDownload: (pdf: PDF) => void;
  onStatusChange: (pdf: PDF, status: 'active' | 'inactive') => void;
  onTitleUpdate: (pdf: PDF, newTitle: string) => void;
  isDragging?: boolean;
}

function SortablePDFCard(props: PDFCardProps) {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({ id: props.pdf.id });

  const [isEditingTitle, setIsEditingTitle] = useState(false);
  const [editTitle, setEditTitle] = useState(props.pdf.title);

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.5 : 1,
  };

  const handleTitleClick = () => {
    setIsEditingTitle(true);
    setEditTitle(props.pdf.title);
  };

  const handleTitleSave = () => {
    if (editTitle.trim() && editTitle !== props.pdf.title) {
      props.onTitleUpdate(props.pdf, editTitle.trim());
    }
    setIsEditingTitle(false);
  };

  const handleTitleCancel = () => {
    setEditTitle(props.pdf.title);
    setIsEditingTitle(false);
  };

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter') {
      handleTitleSave();
    } else if (e.key === 'Escape') {
      handleTitleCancel();
    }
  };

  return (
    <div ref={setNodeRef} style={style}>
      <Card className="overflow-hidden hover:shadow-lg transition-shadow">
        <CardHeader className="pb-3">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2 flex-1">
              <div 
                {...attributes} 
                {...listeners}
                className="cursor-grab active:cursor-grabbing p-1 hover:bg-gray-100 rounded"
              >
                <GripVertical className="h-4 w-4 text-gray-400" />
              </div>
              {isEditingTitle ? (
                <div className="flex items-center gap-2 flex-1">
                  <input
                    type="text"
                    value={editTitle}
                    onChange={(e) => setEditTitle(e.target.value)}
                    onKeyDown={handleKeyPress}
                    onBlur={handleTitleSave}
                    className="flex-1 px-2 py-1 text-lg font-semibold border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    autoFocus
                  />
                  <Button
                    size="sm"
                    variant="ghost"
                    onClick={handleTitleSave}
                    className="px-2 text-green-600 hover:text-green-700"
                  >
                    ✓
                  </Button>
                  <Button
                    size="sm"
                    variant="ghost"
                    onClick={handleTitleCancel}
                    className="px-2 text-red-600 hover:text-red-700"
                  >
                    ✕
                  </Button>
                </div>
              ) : (
                <CardTitle 
                  className="text-lg font-semibold truncate flex-1 mr-2 cursor-pointer hover:text-blue-600 transition-colors"
                  onClick={handleTitleClick}
                  title="クリックして編集"
                >
                  {props.pdf.title}
                </CardTitle>
              )}
            </div>
            <Badge 
              variant={props.pdf.status === 'active' ? 'default' : 'secondary'}
              className="cursor-pointer"
              onClick={() => props.onStatusChange(props.pdf, props.pdf.status === 'active' ? 'inactive' : 'active')}
            >
              {props.pdf.status === 'active' ? 'アクティブ' : '非アクティブ'}
            </Badge>
          </div>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="aspect-[16/9] rounded-lg overflow-hidden">
            <PDFThumbnail 
              companyId={props.companyId} 
              pdfId={props.pdf.id} 
              className="w-full h-full object-cover [&>canvas]:max-w-full [&>canvas]:!h-auto"
              isPublic={false}
            />
          </div>
          
          <div className="space-y-2 text-sm text-gray-600">
            <div className="truncate">ファイル名: {props.pdf.file_name}</div>
            <div>サイズ: {(props.pdf.file_size / 1024 / 1024).toFixed(2)} MB</div>
            <div>作成日: {new Date(props.pdf.created_at).toLocaleDateString('ja-JP')}</div>
          </div>
          
          <div className="flex gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => props.onPreview(props.pdf)}
              className="flex-1"
            >
              <Eye className="h-4 w-4 mr-1" />
              PDFプレビュー
            </Button>
            <Button
              variant="destructive"
              size="sm"
              onClick={() => props.onDelete(props.pdf.id)}
            >
              <Trash2 className="h-4 w-4" />
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

export default function CompanyPDFsPage() {
  const params = useParams();
  const router = useRouter();
  const [pdfs, setPdfs] = useState<PDF[]>([]);
  const [company, setCompany] = useState<Company | null>(null);
  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const [isSorting, setIsSorting] = useState(false);
  
  // モーダル関連のステート
  const [previewModal, setPreviewModal] = useState<{
    isOpen: boolean;
    pdf: PDF | null;
    pdfUrl: string | null;
    loadingPdf: boolean;
  }>({
    isOpen: false,
    pdf: null,
    pdfUrl: null,
    loadingPdf: false,
  });

  const companyId = params.id as string;

  // ドラッグ&ドロップセンサーの設定
  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  useEffect(() => {
    loadData();
  }, [companyId]);

  const loadData = async () => {
    try {
      setLoading(true);
      const [pdfData, companyData] = await Promise.all([
        pdfApi.getAll(companyId),
        companyApi.get(companyId)
      ]);
      
      // sort_order でソートし、ない場合は作成日でソート
      const sortedPdfs = pdfData.data.sort((a, b) => {
        if (a.sort_order !== undefined && b.sort_order !== undefined) {
          return a.sort_order - b.sort_order;
        }
        return new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
      });
      
      setPdfs(sortedPdfs);
      setCompany(companyData);
    } catch (error) {
      console.error('データの読み込みに失敗しました:', error);
      toast.error('データの読み込みに失敗しました');
    } finally {
      setLoading(false);
    }
  };

  const handleDragEnd = async (event: DragEndEvent) => {
    const { active, over } = event;

    if (over && active.id !== over.id) {
      const oldIndex = pdfs.findIndex((pdf) => pdf.id === active.id);
      const newIndex = pdfs.findIndex((pdf) => pdf.id === over.id);

      const newPdfs = arrayMove(pdfs, oldIndex, newIndex);
      setPdfs(newPdfs);

      // サーバーに並び順を送信
      setIsSorting(true);
      try {
        const sortedDocuments = newPdfs.map((pdf: PDF, index: number) => ({
          id: pdf.id,
          sort_order: index + 1,
        }));

        await pdfApi.updateSortOrder(companyId, sortedDocuments);
        toast.success('並び順を更新しました');
      } catch (error) {
        console.error('並び順の更新に失敗しました:', error);
        toast.error('並び順の更新に失敗しました');
        // 失敗した場合は元に戻す
        loadData();
      } finally {
        setIsSorting(false);
      }
    }
  };

  const handleUpload = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    if (file.type !== 'application/pdf') {
      toast.error('PDFファイルのみアップロード可能です');
      return;
    }

    // ファイルサイズチェック (50MB)
    if (file.size > 50 * 1024 * 1024) {
      toast.error('ファイルサイズは50MB以下にしてください');
      return;
    }

    try {
      setUploading(true);
      const formData = new FormData();
      formData.append('file', file);
      formData.append('title', file.name.replace('.pdf', ''));
      await pdfApi.upload(companyId, formData);
      toast.success('PDFをアップロードしました');
      loadData();
    } catch (error) {
      console.error('アップロードに失敗しました:', error);
      toast.error('アップロードに失敗しました');
    } finally {
      setUploading(false);
      // ファイル入力をリセット
      event.target.value = '';
    }
  };

  const handleDelete = async (pdfId: string) => {
    if (!confirm('このPDFを削除してもよろしいですか？')) return;

    try {
      await pdfApi.delete(companyId, pdfId);
      toast.success('PDFを削除しました');
      loadData();
    } catch (error) {
      console.error('削除に失敗しました:', error);
      toast.error('削除に失敗しました');
    }
  };

  const handlePreview = async (pdf: PDF) => {
    try {
      setPreviewModal({
        isOpen: true,
        pdf,
        pdfUrl: null,
        loadingPdf: true,
      });

      // 認証付きでPDFデータを取得
      const token = Cookies.get('token');
      const response = await fetch(pdfApi.getPreviewUrl(companyId, pdf.id), {
        headers: {
          'Authorization': `Bearer ${token}`,
        },
        credentials: 'include',
      });
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      const pdfBlob = await response.blob();
      const objectUrl = URL.createObjectURL(pdfBlob);
      
      setPreviewModal(prev => ({
        ...prev,
        pdfUrl: objectUrl,
        loadingPdf: false,
      }));
      
    } catch (error) {
      console.error('PDFプレビューの表示に失敗しました:', error);
      toast.error('PDFプレビューの表示に失敗しました');
      setPreviewModal(prev => ({ ...prev, loadingPdf: false }));
    }
  };

  const closePreviewModal = () => {
    // オブジェクトURLのクリーンアップ
    if (previewModal.pdfUrl) {
      URL.revokeObjectURL(previewModal.pdfUrl);
    }
    
    setPreviewModal({
      isOpen: false,
      pdf: null,
      pdfUrl: null,
      loadingPdf: false,
    });
  };

  const handleDownload = async (pdf: PDF) => {
    try {
      // 直接ダウンロードAPIを呼び出し
      const response = await fetch(pdfApi.getDownloadUrl(companyId, pdf.id), {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Authorization': `Bearer ${Cookies.get('token') || ''}`,
        },
      });
      
      if (!response.ok) {
        throw new Error('ダウンロードに失敗しました');
      }
      
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = pdf.file_name;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      
      toast.success('ダウンロードを開始しました');
    } catch (error) {
      console.error('ダウンロードに失敗しました:', error);
      toast.error('ダウンロードに失敗しました');
    }
  };

  const handleStatusChange = async (pdf: PDF, status: 'active' | 'inactive') => {
    try {
      await pdfApi.updateStatus(companyId, pdf.id, status);
      toast.success('PDFのステータスを変更しました');
      loadData();
    } catch (error) {
      console.error('ステータスの変更に失敗しました:', error);
      toast.error('ステータスの変更に失敗しました');
    }
  };

  const handleTitleUpdate = async (pdf: PDF, newTitle: string) => {
    try {
      await pdfApi.updateTitle(companyId, pdf.id, newTitle);
      toast.success('PDFのタイトルを更新しました');
      loadData();
    } catch (error) {
      console.error('タイトルの更新に失敗しました:', error);
      toast.error('タイトルの更新に失敗しました');
    }
  };

  if (loading) {
    return (
      <AdminLayout>
        <div className="container mx-auto p-6">
          <div className="text-center">読み込み中...</div>
        </div>
      </AdminLayout>
    );
  }

  return (
    <AdminLayout>
      <div className="container mx-auto p-6">
        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-3xl font-bold">PDF管理</h1>
            {company && (
              <p className="text-gray-600 mt-1">{company.name}</p>
            )}
          </div>
          <div className="flex gap-2">
            <Button
              onClick={() => router.push(`/admin/companies/${companyId}/access-log`)}
              variant="outline"
            >
              アクセスログ
            </Button>
            <Button
              onClick={() => router.push('/admin/companies')}
              variant="outline"
            >
              会社一覧に戻る
            </Button>
          </div>
        </div>

        <div className="mb-6">
          <label htmlFor="pdf-upload">
            <Button asChild disabled={uploading}>
              <span className="cursor-pointer">
                <Upload className="h-4 w-4 mr-2" />
                {uploading ? 'アップロード中...' : 'PDFをアップロード'}
              </span>
            </Button>
          </label>
          <input
            id="pdf-upload"
            type="file"
            accept=".pdf"
            onChange={handleUpload}
            className="hidden"
          />
          {pdfs.length > 1 && (
            <p className="text-sm text-gray-600 mt-2">
              💡 ドラッグ&ドロップで並び順を変更できます
              {isSorting && <span className="ml-2 text-blue-600">更新中...</span>}
            </p>
          )}
        </div>

        {pdfs.length === 0 ? (
          <Card>
            <CardContent className="text-center py-12">
              <p className="text-gray-500">PDFがアップロードされていません</p>
          </CardContent>
          </Card>
        ) : (
          <DndContext 
            sensors={sensors}
            collisionDetection={closestCenter}
            onDragEnd={handleDragEnd}
          >
            <SortableContext items={pdfs.map(pdf => pdf.id)} strategy={verticalListSortingStrategy}>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {pdfs.map((pdf) => (
                  <SortablePDFCard
                    key={pdf.id}
                    pdf={pdf}
                    companyId={companyId}
                    onDelete={handleDelete}
                    onPreview={handlePreview}
                    onDownload={handleDownload}
                    onStatusChange={handleStatusChange}
                    onTitleUpdate={handleTitleUpdate}
                  />
                ))}
              </div>
            </SortableContext>
          </DndContext>
        )}

        {/* PDFプレビューモーダル */}
        <Dialog open={previewModal.isOpen} onOpenChange={closePreviewModal}>
          <DialogContent className="max-w-7xl max-h-[90vh] p-0">
            <DialogHeader className="p-6 pb-4">
              <div className="flex items-center justify-between">
                <div>
                  <DialogTitle className="text-lg">
                    {previewModal.pdf?.title}
                  </DialogTitle>
                  <DialogDescription>
                    {previewModal.pdf?.file_name} • {previewModal.pdf ? (previewModal.pdf.file_size / 1024 / 1024).toFixed(2) : '0'} MB
                  </DialogDescription>
                </div>
                {/* <Button
                  variant="ghost"
                  size="sm"
                  onClick={closePreviewModal}
                >
                  <X className="h-4 w-4" />
                </Button> */}
              </div>
            </DialogHeader>
            
            <div className="px-6 pb-6">
              {previewModal.loadingPdf ? (
                <div className="h-96 flex items-center justify-center bg-gray-50 rounded-lg">
                  <div className="text-center">
                    <div className="text-lg">PDFを読み込み中...</div>
                  </div>
                </div>
              ) : previewModal.pdfUrl ? (
                <div className="space-y-4">
                  {/* PDF表示 */}
                  <div className="flex justify-center bg-gray-50 rounded-lg p-4">
                    <iframe
                      src={previewModal.pdfUrl}
                      className="w-full h-96"
                      title="PDF Preview"
                      style={{ border: 'none' }}
                    />
                  </div>
                </div>
              ) : (
                <div className="h-96 flex items-center justify-center bg-gray-50 rounded-lg">
                  <div className="text-center text-red-600">
                    PDFの読み込みに失敗しました
                  </div>
                </div>
              )}
            </div>
          </DialogContent>
        </Dialog>
      </div>
    </AdminLayout>
  );
}