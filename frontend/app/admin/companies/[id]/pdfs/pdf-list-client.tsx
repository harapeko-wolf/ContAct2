'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { ArrowLeft, Eye, FileText, FileUp, MoreHorizontal, Trash2, Plus, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
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

interface PDFListClientProps {
  companyId: string;
}

export default function PDFListClient({ companyId }: PDFListClientProps) {
  const [pdfs, setPdfs] = useState<PDF[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [showUploadDialog, setShowUploadDialog] = useState(false);
  const [uploadFile, setUploadFile] = useState<File | null>(null);
  const [uploadTitle, setUploadTitle] = useState('');
  const [isUploading, setIsUploading] = useState(false);
  const router = useRouter();
  const { toast } = useToast();
  const [companyName, setCompanyName] = useState<string>('');

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
    fetchPdfs();
    // 会社名を取得
    companyApi.getById(companyId).then(company => setCompanyName(company.name)).catch(() => setCompanyName('会社名取得エラー'));
  }, [companyId]);

  // PDFをアップロード
  const handleUpload = async () => {
    if (!uploadFile || !uploadTitle) return;

    setIsUploading(true);
    try {
      const newPdf = await pdfApi.upload(companyId, uploadFile, uploadTitle);
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

  // PDFをプレビュー
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
      
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3 mb-4">
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
                      プレビュー
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
                <div className="flex flex-wrap gap-2">
                  <Button 
                    variant="outline" 
                    size="sm" 
                    className="gap-2"
                    onClick={() => handlePreview(pdf.id)}
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
    </div>
  );
}