'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { ArrowLeft, Eye, FileText, FileUp, MoreHorizontal, Trash2, Plus, X, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuSeparator, 
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { useToast } from '@/hooks/use-toast';
import { pdfApi, PDF, companyApi } from '@/lib/api';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatFileSize } from '@/lib/utils';
import { Document, Page, pdfjs } from 'react-pdf';

// PDFワーカーの設定
pdfjs.GlobalWorkerOptions.workerSrc = `//cdnjs.cloudflare.com/ajax/libs/pdf.js/${pdfjs.version}/pdf.worker.min.js`;

interface PDFListClientProps {
  companyId: string;
}

// PDFサムネイルコンポーネント
const PDFThumbnail = ({ pdfUrl, title }: { pdfUrl: string; title: string }) => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);
  const [pageSize, setPageSize] = useState({ width: 280, height: 158 });

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
    
    // 実際のPDF比率に合わせて調整
    const containerWidth = 320;
    const containerHeight = containerWidth / aspectRatio;
    
    setPageSize({ width: containerWidth, height: containerHeight });
  };

  if (error) {
    return (
      <div className="aspect-[16/9] bg-gray-100 rounded-lg flex items-center justify-center">
        <FileText className="h-8 w-8 text-gray-400" />
      </div>
    );
  }

  return (
    <div className="aspect-[16/9] rounded-lg overflow-hidden relative">
      {loading && (
        <div className="absolute inset-0 flex items-center justify-center">
          <Loader2 className="h-6 w-6 animate-spin text-gray-400" />
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

export default function PDFListClient({ companyId }: PDFListClientProps) {
  const [pdfs, setPdfs] = useState<PDF[]>([]);
  const [companyName, setCompanyName] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [showUploadDialog, setShowUploadDialog] = useState(false);
  const [uploadFile, setUploadFile] = useState<File | null>(null);
  const [uploadTitle, setUploadTitle] = useState('');
  const [isUploading, setIsUploading] = useState(false);
  const [previewUrls, setPreviewUrls] = useState<{ [id: string]: string }>({});
  const [selectedPdf, setSelectedPdf] = useState<PDF | null>(null);
  const [showPreviewModal, setShowPreviewModal] = useState(false);
  const router = useRouter();
  const { toast } = useToast();

  // PDF一覧を取得
  const fetchPdfs = async () => {
    try {
      const response = await pdfApi.getAll(companyId);
      setPdfs(response.data);
    } catch (error) {
      toast({
        title: 'エラーが発生しました',
        description: 'PDF一覧の取得に失敗しました',
        variant: 'destructive',
      });
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    const fetchData = async () => {
      setIsLoading(true);
      try {
        const company = await companyApi.get(companyId);
        setCompanyName(company.name);
        const response = await pdfApi.getAll(companyId);
        setPdfs(response.data);
        
        // プレビューURLも取得
        const urls: { [id: string]: string } = {};
        for (const pdf of response.data) {
          try {
            urls[pdf.id] = await pdfApi.getPreviewUrl(companyId, pdf.id);
          } catch (error) {
            console.error(`Failed to get preview URL for PDF ${pdf.id}:`, error);
          }
        }
        setPreviewUrls(urls);
      } catch (error) {
        console.error('Failed to fetch data:', error);
        toast({
          title: 'エラーが発生しました',
          description: 'データの取得に失敗しました',
          variant: 'destructive',
        });
      } finally {
        setIsLoading(false);
      }
    };

    fetchData();
  }, [companyId, toast]);

  // PDFをアップロード
  const handleUpload = async () => {
    if (!uploadFile || !uploadTitle) return;

    setIsUploading(true);
    try {
      const formData = new FormData();
      formData.append('file', uploadFile);
      formData.append('title', uploadTitle);
      
      const newPdf = await pdfApi.upload(companyId, formData);
      setPdfs([
        {
          ...newPdf,
          created_at: newPdf.created_at || new Date().toISOString(),
          file_size: newPdf.file_size || uploadFile.size,
          file_name: newPdf.file_name || uploadFile.name,
        },
        ...pdfs,
      ]);
      setShowUploadDialog(false);
      setUploadFile(null);
      setUploadTitle('');
      toast({
        title: 'アップロード完了',
        description: 'PDFファイルが正常にアップロードされました',
      });
    } catch (error) {
      toast({
        title: 'エラーが発生しました',
        description: 'PDFのアップロードに失敗しました',
        variant: 'destructive',
      });
    } finally {
      setIsUploading(false);
    }
  };

  // PDFを削除
  const handleDelete = async (pdfId: string) => {
    try {
      await pdfApi.delete(companyId, pdfId);
      setPdfs(pdfs.filter(pdf => pdf.id !== pdfId));
      toast({
        title: '削除完了',
        description: 'PDFファイルが正常に削除されました',
      });
    } catch (error) {
      toast({
        title: 'エラーが発生しました',
        description: 'PDFの削除に失敗しました',
        variant: 'destructive',
      });
    }
  };

  // PDFをプレビュー（新しいタブ）
  const handlePreview = async (pdfId: string) => {
    try {
      const url = await pdfApi.getPreviewUrl(companyId, pdfId);
      window.open(url, '_blank');
    } catch (error) {
      toast({
        title: 'エラーが発生しました',
        description: 'PDFのプレビューに失敗しました',
        variant: 'destructive',
      });
    }
  };

  // PDFをモーダルでプレビュー
  const handleModalPreview = (pdf: PDF) => {
    setSelectedPdf(pdf);
    setShowPreviewModal(true);
  };

  const handleClosePreview = () => {
    setShowPreviewModal(false);
    setSelectedPdf(null);
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4">
      <Button 
        variant="ghost" 
        className="gap-2 mb-6"
        onClick={() => router.push('/admin/companies')}
      >
        <ArrowLeft className="h-4 w-4" />
        会社一覧に戻る
      </Button>
      
      <div className="flex justify-between items-center mb-6">
        <div>
          <h2 className="text-3xl font-bold tracking-tight mb-2">資料一覧</h2>
          <div className="flex items-center gap-4">
            <p className="text-muted-foreground">{companyName}</p>
          </div>
        </div>
        <Button 
          className="gap-2"
          onClick={() => setShowUploadDialog(true)}
        >
          <FileUp className="h-4 w-4" />
          PDFをアップロード
        </Button>
      </div>
      
      {/* アップロードダイアログ */}
      <Dialog open={showUploadDialog} onOpenChange={setShowUploadDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>PDFをアップロード</DialogTitle>
          </DialogHeader>
          <div className="space-y-4">
            <div>
              <Label htmlFor="title">タイトル</Label>
              <Input
                id="title"
                value={uploadTitle}
                onChange={(e) => setUploadTitle(e.target.value)}
                placeholder="資料のタイトルを入力"
                required
              />
            </div>
            <div>
              <Label htmlFor="file">PDFファイル</Label>
              <Input
                id="file"
                type="file"
                accept=".pdf"
                onChange={(e) => setUploadFile(e.target.files?.[0] || null)}
              />
            </div>
          </div>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setShowUploadDialog(false)}
              disabled={isUploading}
            >
              キャンセル
            </Button>
            <Button
              onClick={handleUpload}
              disabled={!uploadFile || !uploadTitle.trim() || isUploading}
            >
              {isUploading ? 'アップロード中...' : 'アップロード'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
      
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-4">
        {pdfs.map(pdf => (
          <Card key={pdf.id} className={pdf.status === 'active' ? 'border-blue-100' : 'border-gray-200 opacity-75'}>
            <CardContent className="p-0">
              <div className="p-4 border-b border-gray-100 flex justify-between items-start">
                <div className="flex gap-3 items-start">
                  <div className={`p-2 rounded-md ${pdf.status === 'active' ? 'bg-blue-50' : 'bg-gray-50'}`}>
                    <FileText className={`h-6 w-6 ${pdf.status === 'active' ? 'text-blue-500' : 'text-gray-400'}`} />
                  </div>
                  <div>
                    <h3 className="font-medium">{pdf.title}</h3>
                    <p className="text-xs text-muted-foreground">
                      {formatFileSize(pdf.file_size)} • アップロード日: {new Date(pdf.created_at).toLocaleDateString()}
                    </p>
                  </div>
                </div>
                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon" className="h-8 w-8">
                      <MoreHorizontal className="h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <DropdownMenuItem 
                      className="cursor-pointer"
                      onClick={() => handlePreview(pdf.id)}
                    >
                      新しいタブで開く
                    </DropdownMenuItem>
                    <DropdownMenuItem 
                      className="cursor-pointer"
                      onClick={() => handleModalPreview(pdf)}
                    >
                      モーダルで表示
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem 
                      className="cursor-pointer text-red-600" 
                      onClick={() => handleDelete(pdf.id)}
                    >
                      削除
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
              
              <div className="p-4">
                {previewUrls[pdf.id] ? (
                  <div className="mb-4">
                    <PDFThumbnail pdfUrl={previewUrls[pdf.id]} title={pdf.title} />
                  </div>
                ) : (
                  <div className="aspect-[16/9] bg-gray-100 rounded-lg flex items-center justify-center mb-4">
                    <Loader2 className="h-6 w-6 animate-spin text-gray-400" />
                  </div>
                )}
                <div className="flex flex-wrap gap-2">
                  <Button 
                    variant="outline" 
                    size="sm" 
                    className="gap-2"
                    onClick={() => handleModalPreview(pdf)}
                  >
                    <Eye className="h-3.5 w-3.5" />
                    プレビュー
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {pdfs.length === 0 && (
        <div className="text-center py-10">
          <FileText className="h-10 w-10 mx-auto text-muted-foreground mb-4" />
          <h3 className="text-lg font-medium mb-2">PDFがありません</h3>
          <p className="text-muted-foreground mb-4">
            最初のPDFをアップロードして始めましょう
          </p>
          <Button 
            className="gap-2"
            onClick={() => setShowUploadDialog(true)}
          >
            <FileUp className="h-4 w-4" />
            PDFをアップロード
          </Button>
        </div>
      )}

      {/* PDFプレビューモーダル */}
      <Dialog open={showPreviewModal} onOpenChange={handleClosePreview}>
        <DialogContent className="max-w-4xl w-[90vw] h-[85vh] sm:w-[85vw] sm:h-[80vh] p-0 flex flex-col">
          <DialogHeader className="flex flex-row items-center justify-between p-4 border-b shrink-0">
            <div>
              <DialogTitle className="text-lg font-semibold">
                {selectedPdf?.title}
              </DialogTitle>
              <DialogDescription className="text-sm text-gray-500">
                PDF資料をプレビュー中
              </DialogDescription>
            </div>
            {/* <Button
              variant="ghost"
              size="icon"
              onClick={handleClosePreview}
              className="h-8 w-8 rounded-full"
            >
              <X className="h-4 w-4" />
            </Button> */}
          </DialogHeader>
          <div className="flex-1 overflow-hidden min-h-0">
            {selectedPdf && previewUrls[selectedPdf.id] && (
              <div className="w-full h-full">
                <iframe
                  src={previewUrls[selectedPdf.id]}
                  className="w-full h-full border-0"
                  title={`PDF Preview: ${selectedPdf.title}`}
                />
              </div>
            )}
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}