'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { ArrowLeft, Eye, FileText, FileUp, MoreHorizontal, Trash2, Plus, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuSeparator, 
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { useToast } from '@/hooks/use-toast';

interface PDF {
  id: number;
  title: string;
  pages: number;
  uploadDate: string;
  fileSize: string;
  views: number;
  visible: boolean;
}

interface Template {
  id: string;
  name: string;
  pdfs: {
    id: number;
    title: string;
    pages: number;
    uploadDate: string;
    fileSize: string;
    views: number;
    visible: boolean;
  }[];
}

interface PDFListClientProps {
  initialPdfs: PDF[];
  companyId: string;
}

export default function PDFListClient({ initialPdfs, companyId }: PDFListClientProps) {
  const [pdfs, setPdfs] = useState(initialPdfs);
  const [showTemplates, setShowTemplates] = useState(false);
  const [templates] = useState<Template[]>([
    {
      id: '1',
      name: '製品概要セット',
      pdfs: [
        {
          id: 1,
          title: '製品概要',
          pages: 8,
          uploadDate: '2025-05-15',
          fileSize: '2.4 MB',
          views: 0,
          visible: true,
        },
        {
          id: 2,
          title: '技術仕様書',
          pages: 12,
          uploadDate: '2025-05-10',
          fileSize: '3.8 MB',
          views: 0,
          visible: true,
        }
      ]
    },
    {
      id: '2',
      name: '導入事例セット',
      pdfs: [
        {
          id: 3,
          title: '導入ガイド',
          pages: 15,
          uploadDate: '2025-05-08',
          fileSize: '4.2 MB',
          views: 0,
          visible: true,
        },
        {
          id: 4,
          title: 'ケーススタディ',
          pages: 20,
          uploadDate: '2025-05-01',
          fileSize: '5.6 MB',
          views: 0,
          visible: true,
        }
      ]
    }
  ]);
  const router = useRouter();
  const { toast } = useToast();

  const toggleVisibility = (pdfId: number) => {
    setPdfs(pdfs.map(pdf => 
      pdf.id === pdfId ? { ...pdf, visible: !pdf.visible } : pdf
    ));
    
    toast({
      title: '表示設定を更新しました',
      description: '資料の表示設定が更新されました',
    });
  };
  
  const deletePdf = (pdfId: number) => {
    setPdfs(pdfs.filter(pdf => pdf.id !== pdfId));
    
    toast({
      title: '資料を削除しました',
      description: '資料が完全に削除されました',
    });
  };

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
            <p className="text-muted-foreground">ワンダフル株式会社</p>
          </div>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => setShowTemplates(true)} className="gap-2">
            <Plus className="h-4 w-4" />
            テンプレートから追加
          </Button>
          <Button className="gap-2">
            <FileUp className="h-4 w-4" />
            PDFをアップロード
          </Button>
        </div>
      </div>
      
      {/* テンプレート選択ダイアログ */}
      <Dialog open={showTemplates} onOpenChange={setShowTemplates}>
        <DialogContent className="max-w-4xl">
          <DialogHeader>
            <DialogTitle>テンプレートから資料を追加</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 md:grid-cols-2">
            {templates.map(template => (
              <Card key={template.id} className="relative">
                <CardHeader>
                  <CardTitle>{template.name}</CardTitle>
                  <CardDescription>
                    {template.pdfs.length}個のPDFファイルを含むテンプレート
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-2 mb-4">
                    {template.pdfs.map(pdf => (
                      <div 
                        key={pdf.id} 
                        className={cn(
                          "flex items-center gap-2 text-sm p-2 rounded-md",
                          pdfs.some(p => p.title === pdf.title)
                            ? "bg-gray-100"
                            : "bg-blue-50"
                        )}
                      >
                        <FileText className="h-4 w-4 text-muted-foreground" />
                        <div className="flex-1">
                          <span>{pdf.title}</span>
                          {pdfs.some(p => p.title === pdf.title) && (
                            <span className="ml-2 text-xs text-gray-500">
                              追加済み
                            </span>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                  <Button
                    disabled={template.pdfs.every(p => pdfs.map(ep => ep.title).includes(p.title))}
                    onClick={() => {
                      const newPdfs = [...pdfs];
                      
                      // テンプレート内のPDFが既に追加されているかチェック
                      const existingTitles = pdfs.map(p => p.title);
                      const newPdfsToAdd = template.pdfs.filter(p => !existingTitles.includes(p.title));
                      
                      if (newPdfsToAdd.length === 0) {
                        toast({
                          title: '追加できませんでした',
                          description: 'このテンプレートのPDFは既に追加されています',
                          variant: 'destructive',
                        });
                        return;
                      }
                      
                      // 新しいIDを生成して未追加のPDFのみを追加
                      const maxId = Math.max(...pdfs.map(p => p.id), 0);
                      const templatedPdfs = newPdfsToAdd.map((pdf, index) => ({
                        ...pdf,
                        id: maxId + index + 1,
                      }));
                      
                      setPdfs([...pdfs, ...templatedPdfs]);
                      setShowTemplates(false);
                      toast({
                        title: 'テンプレートを適用しました',
                        description: `${template.name}から${newPdfsToAdd.length}個のPDFを追加しました`,
                      });
                    }}
                    className="w-full gap-2"
                  >
                    <Plus className="h-4 w-4" />
                    このテンプレートを追加
                  </Button>
                </CardContent>
              </Card>
            ))}
          </div>
        </DialogContent>
      </Dialog>
      
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3 mb-4">
        {pdfs.map(pdf => (
          <Card key={pdf.id} className={pdf.visible ? 'border-blue-100' : 'border-gray-200 opacity-75'}>
            <CardContent className="p-0">
              <div className="p-4 border-b border-gray-100 flex justify-between items-start">
                <div className="flex gap-3 items-start">
                  <div className={`p-2 rounded-md ${pdf.visible ? 'bg-blue-50' : 'bg-gray-50'}`}>
                    <FileText className={`h-6 w-6 ${pdf.visible ? 'text-blue-500' : 'text-gray-400'}`} />
                  </div>
                  <div>
                    <h3 className="font-medium">{pdf.title}</h3>
                    <p className="text-xs text-muted-foreground">
                      {pdf.pages}ページ • {pdf.fileSize} • アップロード日: {pdf.uploadDate}
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
                    <DropdownMenuItem className="cursor-pointer" onClick={() => toggleVisibility(pdf.id)}>
                      {pdf.visible ? '資料を非表示' : '資料を表示'}
                    </DropdownMenuItem>
                    <DropdownMenuItem className="cursor-pointer">
                      PDFを置き換え
                    </DropdownMenuItem>
                    <DropdownMenuItem className="cursor-pointer">
                      プレビュー
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem 
                      className="cursor-pointer text-red-600" 
                      onClick={() => deletePdf(pdf.id)}
                    >
                      削除
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
              
              <div className="p-4">
                <div className="grid grid-cols-2 gap-x-4 gap-y-2 text-sm mb-4">
                  <div>
                    <p className="text-muted-foreground">閲覧数</p>
                    <p className="font-medium">{pdf.views}</p>
                  </div>
                  <div>
                    <p className="text-muted-foreground">ステータス</p>
                    <p className="font-medium">
                      {pdf.visible ? (
                        <span className="text-green-600">表示中</span>
                      ) : (
                        <span className="text-gray-500">非表示</span>
                      )}
                    </p>
                  </div>
                </div>
                
                <div className="flex flex-wrap gap-2">
                  <Button variant="outline" size="sm" className="gap-2">
                    <Eye className="h-3.5 w-3.5" />
                    プレビュー
                  </Button>
                  <Button 
                    variant={pdf.visible ? "destructive" : "outline"} 
                    size="sm"
                    className="gap-2"
                    onClick={() => toggleVisibility(pdf.id)}
                  >
                    {pdf.visible ? '非表示' : '表示'}
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}