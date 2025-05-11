'use client';

import { useState } from 'react';
import { DragDropContext, Droppable, Draggable } from '@hello-pangea/dnd';
import { Eye, FileText, FileUp, GripVertical, Plus, Save, Trash2 } from 'lucide-react';

import AdminLayout from '@/components/admin/layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { useToast } from '@/hooks/use-toast';

// テンプレートの型定義
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
    enabled: boolean;
  }[];
}

export default function PDFTemplatesPage() {
  // サンプルのPDFファイル一覧を関数内に移動
  const availablePdfs = [
    {
      id: 1,
      title: '製品概要',
      description: '製品概要a',
      pages: 8,
      uploadDate: '2025-05-15',
      fileSize: '2.4 MB',
      views: 127,
      visible: true,
      url: '/sample.pdf'
    },
    {
      id: 2,
      title: '価格表',
      description: '価格表a',
      pages: 3,
      uploadDate: '2025-05-09',
      fileSize: '1.1 MB',
      views: 88,
      visible: true,
      url: '/kikaku_sample.pdf'
    }
  ];

  // 利用可能なPDFを選択するダイアログの状態を関数内に移動
  const [showPdfSelector, setShowPdfSelector] = useState(false);
  const [templates, setTemplates] = useState<Template[]>([
    {
      id: '1',
      name: '新規テンプレート',
      pdfs: availablePdfs.map(pdf => ({ ...pdf, enabled: true })),
    },
  ]);
  const [selectedTemplate, setSelectedTemplate] = useState<Template | null>(null);
  const [selectedPdf, setSelectedPdf] = useState<string | null>(null);
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);
  const { toast } = useToast();

  // PDFファイルの置き換え
  const handleFileChange = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file || !selectedTemplate || !selectedPdf) return;

    // ファイルサイズチェック (20MB)
    if (file.size > 20 * 1024 * 1024) {
      toast({
        title: 'エラー',
        description: 'ファイルサイズは20MB以下にしてください',
        variant: 'destructive',
      });
      return;
    }

    // PDFファイルのみ許可
    if (file.type !== 'application/pdf') {
      toast({
        title: 'エラー',
        description: 'PDFファイルのみアップロード可能です',
        variant: 'destructive',
      });
      return;
    }

    try {
      // アップロード処理のシミュレーション
      await new Promise(resolve => setTimeout(resolve, 1000));

      // PDFの更新
      const updatedTemplate = {
        ...selectedTemplate,
        pdfs: selectedTemplate.pdfs.map(pdf =>
          pdf.id === selectedPdf
            ? { ...pdf, name: file.name }
            : pdf
        ),
      };

      setTemplates(templates.map(t =>
        t.id === selectedTemplate.id ? updatedTemplate : t
      ));
      setSelectedTemplate(updatedTemplate);

      toast({
        title: 'PDFを更新しました',
        description: `${file.name}をアップロードしました`,
      });
    } catch (error) {
      toast({
        title: 'エラー',
        description: 'アップロードに失敗しました',
        variant: 'destructive',
      });
    }
  };

  // 新規テンプレート作成
  const createTemplate = () => {
    const newTemplate: Template = {
      id: Date.now().toString(),
      name: '新規テンプレート',
      pdfs: availablePdfs.map(pdf => ({ ...pdf, enabled: false })),
    };
    setTemplates([...templates, newTemplate]);
    setSelectedTemplate(newTemplate);
  };

  // テンプレート削除
  const deleteTemplate = (templateId: string) => {
    setTemplates(templates.filter(t => t.id !== templateId));
    setSelectedTemplate(null);
    toast({
      title: 'テンプレートを削除しました',
    });
  };

  // PDFの有効/無効切り替え
  const togglePdf = (pdfId: string) => {
    if (!selectedTemplate) return;
    
    setSelectedTemplate({
      ...selectedTemplate,
      pdfs: selectedTemplate.pdfs.map(pdf => 
        pdf.id === pdfId ? { ...pdf, enabled: !pdf.enabled } : pdf
      ),
    });
  };

  // ドラッグ&ドロップでの並び替え
  const onDragEnd = (result: any) => {
    if (!result.destination || !selectedTemplate) return;

    const items = Array.from(selectedTemplate.pdfs);
    const [reorderedItem] = items.splice(result.source.index, 1);
    items.splice(result.destination.index, 0, reorderedItem);

    setSelectedTemplate({
      ...selectedTemplate,
      pdfs: items,
    });
  };

  // テンプレートの保存
  const saveTemplate = () => {
    if (!selectedTemplate) return;
    
    setTemplates(templates.map(t => 
      t.id === selectedTemplate.id ? selectedTemplate : t
    ));
    
    toast({
      title: 'テンプレートを保存しました',
      description: `${selectedTemplate.name}を保存しました`,
    });
  };

  return (
    <AdminLayout>
      <div className="flex-1 space-y-4 p-8">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-3xl font-bold tracking-tight">PDFテンプレート管理</h2>
            <p className="text-muted-foreground">
              PDFファイルを組み合わせてテンプレートを作成
            </p>
          </div>
          <Button onClick={createTemplate} className="gap-2">
            <Plus className="h-4 w-4" />
            新規テンプレート
          </Button>
        </div>

        <div className="grid gap-4 md:grid-cols-7">
          {/* テンプレート一覧 */}
          <Card className="md:col-span-2">
            <CardHeader>
              <CardTitle>テンプレート一覧</CardTitle>
              <CardDescription>
                登録済みのテンプレート
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                {templates.map(template => (
                  <div
                    key={template.id}
                    className={`flex items-center justify-between p-2 rounded-md cursor-pointer transition-colors ${
                      selectedTemplate?.id === template.id
                        ? 'bg-blue-50 text-blue-600'
                        : 'hover:bg-gray-50'
                    }`}
                    onClick={() => setSelectedTemplate(template)}
                  >
                    <div className="flex items-center gap-2">
                      <FileText className="h-4 w-4" />
                      <span className="font-medium">{template.name}</span>
                    </div>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8 text-red-500 opacity-0 group-hover:opacity-100"
                      onClick={(e) => {
                        e.stopPropagation();
                        deleteTemplate(template.id);
                      }}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* テンプレート編集 */}
          <Card className="md:col-span-5">
            <CardHeader>
              <CardTitle>テンプレート編集</CardTitle>
              <CardDescription>
                含めるPDFファイルを選択し、順序を指定
              </CardDescription>
            </CardHeader>
            <CardContent>
              {selectedTemplate ? (
                <div className="space-y-6">
                  <div className="space-y-2">
                    <Label htmlFor="templateName">テンプレート名</Label>
                    <Input
                      id="templateName"
                      value={selectedTemplate.name}
                      onChange={(e) => setSelectedTemplate({
                        ...selectedTemplate,
                        name: e.target.value,
                      })}
                    />
                  </div>

                  <div className="space-y-4">
                    <Label>PDFファイル</Label>
                    <DragDropContext onDragEnd={onDragEnd}>
                      <Droppable droppableId="pdfs">
                        {(provided) => (
                          <div
                            {...provided.droppableProps}
                            ref={provided.innerRef}
                            className="space-y-2"
                          >
                            {selectedTemplate.pdfs.map((pdf, index) => (
                              <Draggable
                                key={pdf.id}
                                draggableId={String(pdf.id)}
                                index={index}
                              >
                                {(provided) => (
                                  <div
                                    ref={provided.innerRef}
                                    {...provided.draggableProps}
                                    className={`flex items-center justify-between p-3 rounded-md border ${
                                      pdf.enabled ? 'bg-blue-50 border-blue-200' : 'bg-gray-50'
                                    }`}
                                  >
                                    <div className="flex items-center gap-4">
                                      <div {...provided.dragHandleProps}>
                                        <GripVertical className="h-5 w-5 text-gray-400" />
                                      </div>
                                      <div>
                                        <p className="font-medium">{pdf.title}</p>
                                        <p className="text-sm text-muted-foreground">
                                          {pdf.description}
                                        </p>
                                      </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                      <Button
                                        variant="ghost"
                                        size="sm"
                                        className="gap-2"
                                        onClick={() => {
                                          setPreviewUrl(pdf.url);
                                        }}
                                      >
                                        <Eye className="h-4 w-4" />
                                        プレビュー
                                      </Button>
                                      <Button
                                        variant="ghost"
                                        size="sm"
                                        className="gap-2"
                                        onClick={() => {
                                          setSelectedPdf(String(pdf.id));
                                        }}
                                      >
                                        <FileUp className="h-4 w-4" />
                                        置き換え
                                      </Button>
                                      <Switch
                                        checked={pdf.enabled}
                                        onCheckedChange={() => togglePdf(String(pdf.id))}
                                      />
                                    </div>
                                  </div>
                                )}
                              </Draggable>
                            ))}
                            {provided.placeholder}
                          </div>
                        )}
                      </Droppable>
                    </DragDropContext>
                    
                    <Button 
                      variant="outline" 
                      onClick={() => setShowPdfSelector(true)}
                      className="w-full mt-4"
                    >
                      PDFを追加
                    </Button>
                  </div>

                  {/* PDF選択ダイアログ */}
                  <Dialog open={showPdfSelector} onOpenChange={setShowPdfSelector}>
                    <DialogContent>
                      <DialogHeader>
                        <DialogTitle>PDFを追加</DialogTitle>
                        <DialogDescription>
                          テンプレートに追加するPDFを選択してください
                        </DialogDescription>
                      </DialogHeader>
                      <div className="space-y-4">
                        {availablePdfs.map(pdf => {
                          const isAlreadyAdded = selectedTemplate.pdfs.some(p => p.id === pdf.id);
                          return (
                            <div
                              key={pdf.id}
                              className={`flex items-center justify-between p-3 rounded-md border ${
                                isAlreadyAdded ? 'bg-gray-50' : 'hover:bg-blue-50'
                              }`}
                            >
                              <div className="flex items-center gap-3">
                                <FileText className="h-5 w-5 text-muted-foreground" />
                                <div>
                                  <p className="font-medium">{pdf.title}</p>
                                  <p className="text-sm text-muted-foreground">
                                    {pdf.pages}ページ • {pdf.fileSize}
                                  </p>
                                </div>
                              </div>
                              <Button
                                variant={isAlreadyAdded ? "ghost" : "secondary"}
                                size="sm"
                                disabled={isAlreadyAdded}
                                onClick={() => {
                                  if (!isAlreadyAdded) {
                                    setSelectedTemplate({
                                      ...selectedTemplate,
                                      pdfs: [...selectedTemplate.pdfs, { ...pdf, enabled: true }]
                                    });
                                    setShowPdfSelector(false);
                                    toast({
                                      title: 'PDFを追加しました',
                                      description: `${pdf.title}をテンプレートに追加しました`,
                                    });
                                  }
                                }}
                              >
                                {isAlreadyAdded ? '追加済み' : '追加'}
                              </Button>
                            </div>
                          );
                        })}
                      </div>
                    </DialogContent>
                  </Dialog>

                  <Button onClick={saveTemplate} className="gap-2">
                    <Save className="h-4 w-4" />
                    テンプレートを保存
                  </Button>
                </div>
              ) : (
                <div className="flex flex-col items-center justify-center py-12 text-center">
                  <FileText className="h-12 w-12 text-gray-400 mb-4" />
                  <h3 className="text-lg font-medium mb-2">
                    テンプレートを選択してください
                  </h3>
                  <p className="text-muted-foreground">
                    左側のリストからテンプレートを選択するか、<br />
                    新規テンプレートを作成してください
                  </p>
                </div>
              )}
            </CardContent>
          </Card>

          {/* PDFアップロードダイアログ */}
          <Dialog open={!!selectedPdf} onOpenChange={() => setSelectedPdf(null)}>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>PDFファイルを置き換え</DialogTitle>
                <DialogDescription>
                  新しいPDFファイルをアップロードしてください
                </DialogDescription>
              </DialogHeader>
              <div className="space-y-4 py-4">
                <div className="grid w-full max-w-sm items-center gap-1.5">
                  <Label htmlFor="pdf">PDFファイル</Label>
                  <Input
                    id="pdf"
                    type="file"
                    accept=".pdf"
                    onChange={handleFileChange}
                  />
                </div>
                <p className="text-sm text-muted-foreground">
                  最大20MBまでのPDFファイルをアップロードできます
                </p>
              </div>
            </DialogContent>
          </Dialog>

          {/* PDFプレビューダイアログ */}
          <Dialog open={!!previewUrl} onOpenChange={() => setPreviewUrl(null)}>
            <DialogContent className="max-w-4xl">
              <DialogHeader>
                <DialogTitle>PDFプレビュー</DialogTitle>
              </DialogHeader>
              <div className="aspect-video">
                <iframe
                  src={previewUrl || ''}
                  className="w-full h-full"
                  title="PDF Preview"
                />
              </div>
            </DialogContent>
          </Dialog>
        </div>
      </div>
    </AdminLayout>
  );
}