'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { 
  BarChart3, 
  ClipboardCopy, 
  Download, 
  Edit, 
  Eye, 
  FileText, 
  MoreHorizontal, 
  Plus, 
  Trash2, 
  Users,
  Loader2,
  ChevronLeft,
  ChevronRight
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { companyApi, Company, PaginatedResponse } from '@/lib/api';
import AdminLayout from '@/components/admin/layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuSeparator, 
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { Input } from '@/components/ui/input';
import { useToast } from '@/hooks/use-toast';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

// ステータスの定義
const STATUS_OPTIONS = ['すべて', '受注', '検討中', '失注'] as const;
type StatusType = typeof STATUS_OPTIONS[number];

const STATUS_MAP = {
  'active': '受注',
  'considering': '検討中',
  'inactive': '失注',
} as const;

const REVERSE_STATUS_MAP = {
  '受注': 'active',
  '検討中': 'considering',
  '失注': 'inactive',
} as const;

export default function CompaniesPage() {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedStatus, setSelectedStatus] = useState<StatusType>('すべて');
  const [companies, setCompanies] = useState<Company[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [perPage, setPerPage] = useState(10);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);
  const [statusChangeDialog, setStatusChangeDialog] = useState<{
    isOpen: boolean;
    companyId: string | null;
    newStatus: 'active' | 'considering' | 'inactive' | null;
  }>({
    isOpen: false,
    companyId: null,
    newStatus: null,
  });
  const [deleteDialog, setDeleteDialog] = useState<{
    isOpen: boolean;
    companyId: string | null;
  }>({
    isOpen: false,
    companyId: null,
  });
  const { toast } = useToast();

  useEffect(() => {
    loadCompanies();
  }, [currentPage, perPage]);

  const loadCompanies = async () => {
    try {
      const data = await companyApi.getAll(currentPage, perPage);
      // ページネーション形式のレスポンス処理
      if (data.data) {
        setCompanies(data.data);
        setTotalPages(data.last_page || 1);
        setTotalItems(data.total || data.data.length);
      } else {
        // 配列形式の場合（フォールバック）
        setCompanies(Array.isArray(data) ? data : []);
        setTotalPages(1);
        setTotalItems(Array.isArray(data) ? data.length : 0);
      }
    } catch (error) {
      console.error('Company loading error:', error);
      setCompanies([]); // エラー時は空配列を設定
      toast({
        title: 'エラー',
        description: '会社一覧の取得に失敗しました',
        variant: 'destructive',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const handleDelete = async (id: string) => {
    setDeleteDialog({
      isOpen: true,
      companyId: id,
    });
  };

  const confirmDelete = async () => {
    if (!deleteDialog.companyId) return;

    try {
      await companyApi.delete(deleteDialog.companyId);
      setCompanies((companies || []).filter(company => company.id !== deleteDialog.companyId));
      toast({
        title: '成功',
        description: '会社を削除しました',
      });
      // 現在のページの最後のアイテムを削除した場合、前のページに移動
      if ((companies || []).length === 1 && currentPage > 1) {
        setCurrentPage(currentPage - 1);
      } else {
        loadCompanies();
      }
    } catch (error) {
      toast({
        title: 'エラー',
        description: '会社の削除に失敗しました',
        variant: 'destructive',
      });
    } finally {
      setDeleteDialog({
        isOpen: false,
        companyId: null,
      });
    }
  };

  const handleStatusChange = async (companyId: string, newStatus: 'active' | 'considering' | 'inactive') => {
    try {
      const company = (companies || []).find(c => c.id === companyId);
      if (!company) return;

      await companyApi.update(companyId, {
        name: company.name,
        email: company.email,
        status: newStatus
      });
      
      // 会社一覧を再取得
      await loadCompanies();
      
      toast({
        title: '成功',
        description: 'ステータスを更新しました',
      });
    } catch (error) {
      console.error('ステータス更新エラー:', error);
      toast({
        title: 'エラー',
        description: 'ステータスの更新に失敗しました',
        variant: 'destructive',
      });
    } finally {
      setStatusChangeDialog({
        isOpen: false,
        companyId: null,
        newStatus: null,
      });
    }
  };

  const filteredCompanies = (companies || []).filter(company => {
    const matchesSearch = 
      (company.name?.toLowerCase() ?? '').includes(searchTerm.toLowerCase()) ||
      (company.email?.toLowerCase() ?? '').includes(searchTerm.toLowerCase());
    
    const matchesStatus = selectedStatus === 'すべて' || 
      (selectedStatus in REVERSE_STATUS_MAP && company.status === REVERSE_STATUS_MAP[selectedStatus]);
    
    return matchesSearch && matchesStatus;
  });

  const copyLink = (id: string) => {
    navigator.clipboard.writeText(`${window.location.origin}/view/${id}`);
    toast({
      title: 'リンクをコピーしました',
      description: '会社と共有できるリンクをクリップボードにコピーしました',
    });
  };

  if (isLoading) {
    return (
      <AdminLayout>
        <div className="flex-1 flex items-center justify-center p-8">
          <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
        </div>
      </AdminLayout>
    );
  }

  return (
    <AdminLayout>
      <div className="flex-1 space-y-4 p-8">
        <div className="flex justify-between items-center">
          <div>
            <h2 className="text-3xl font-bold tracking-tight mb-2">会社一覧</h2>
            <p className="text-muted-foreground">
              取引先企業と資料を管理
            </p>
          </div>
          <Link href="/admin/companies/create">
            <Button className="gap-2">
              <Plus className="h-4 w-4" />
              会社を追加
            </Button>
          </Link>
        </div>
        
        <div className="flex items-center gap-4">
          <div className="relative w-full max-w-sm">
            <Input
              placeholder="会社を検索..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="pl-10"
            />
            <Users className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          </div>
          <div className="flex gap-2">
            {STATUS_OPTIONS.map((status) => (
              <Button
                key={status}
                variant={selectedStatus === status ? "default" : "outline"}
                size="sm"
                onClick={() => setSelectedStatus(status)}
                className={cn(
                  "min-w-[80px]",
                  status === '受注' && selectedStatus === status && "bg-green-600",
                  status === '検討中' && selectedStatus === status && "bg-blue-600",
                  status === '失注' && selectedStatus === status && "bg-red-600"
                )}
              >
                {status}
              </Button>
            ))}
          </div>
        </div>
        
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {filteredCompanies.map((company, index) => (
            <Card key={company.id ?? `company-${index}`} className="overflow-hidden">
              <CardHeader className="pb-2">
                <div className="flex justify-between items-start">
                  <div>
                    <div className="flex items-center gap-2 mb-1">
                      <span className={cn(
                        "px-2 py-0.5 text-xs font-medium rounded-full",
                        company.status === 'active' && "bg-green-100 text-green-700",
                        company.status === 'considering' && "bg-blue-100 text-blue-700",
                        company.status === 'inactive' && "bg-red-100 text-red-700"
                      )}>
                        {STATUS_MAP[company.status]}
                      </span>
                    </div>
                    <CardTitle className="text-xl">
                      {company.name}
                    </CardTitle>
                  </div>
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost" size="icon" className="h-8 w-8" data-testid="company-menu">
                        <MoreHorizontal className="h-4 w-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" onCloseAutoFocus={(e) => e.preventDefault()}>
                      <DropdownMenuItem 
                        className="gap-2 cursor-pointer"
                        onClick={() => copyLink(company.id)}
                      >
                        <ClipboardCopy className="h-4 w-4" />
                        リンクをコピー
                      </DropdownMenuItem>
                      <DropdownMenuItem asChild>
                        <Link href={`/admin/companies/${company.id}/edit`} className="gap-2 cursor-pointer">
                          <Edit className="h-4 w-4" />
                          会社を編集
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuItem asChild>
                        <Link href={`/admin/companies/${company.id}/pdfs`} className="gap-2 cursor-pointer">
                          <FileText className="h-4 w-4" />
                          PDF管理
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem className="gap-2 cursor-pointer">
                        <BarChart3 className="h-4 w-4" />
                        ステータスを変更
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="sm" className="h-6 px-2 ml-auto">
                              {STATUS_MAP[company.status]}
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end" onCloseAutoFocus={(e) => e.preventDefault()}>
                            <DropdownMenuItem 
                              onClick={() => setStatusChangeDialog({
                                isOpen: true,
                                companyId: company.id,
                                newStatus: 'active'
                              })}
                              className={cn(
                                "gap-2 cursor-pointer",
                                company.status === 'active' && "bg-green-50"
                              )}
                            >
                              受注
                            </DropdownMenuItem>
                            <DropdownMenuItem 
                              onClick={() => setStatusChangeDialog({
                                isOpen: true,
                                companyId: company.id,
                                newStatus: 'considering'
                              })}
                              className={cn(
                                "gap-2 cursor-pointer",
                                company.status === 'considering' && "bg-blue-50"
                              )}
                            >
                              検討中
                            </DropdownMenuItem>
                            <DropdownMenuItem 
                              onClick={() => setStatusChangeDialog({
                                isOpen: true,
                                companyId: company.id,
                                newStatus: 'inactive'
                              })}
                              className={cn(
                                "gap-2 cursor-pointer",
                                company.status === 'inactive' && "bg-red-50"
                              )}
                            >
                              失注
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem asChild>
                        <Link href={`/admin/companies/${company.id}/access-log`} className="gap-2 cursor-pointer">
                          <Eye className="h-4 w-4" />
                          アクセスログを表示
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem 
                        className="gap-2 cursor-pointer text-red-600"
                        onClick={() => handleDelete(company.id)}
                      >
                        <Trash2 className="h-4 w-4" />
                        会社を削除
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>
                <CardDescription>
                  {company.email}
                </CardDescription>
              </CardHeader>
              <CardContent className="pb-4">
                <div className="space-y-4">
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <p className="text-muted-foreground">業種</p>
                      <p className="font-medium">{company.industry || '未設定'}</p>
                    </div>
                    <div>
                      <p className="text-muted-foreground">従業員数</p>
                      <p className="font-medium">{company.employee_count || '未設定'}</p>
                    </div>
                  </div>
                  
                  <div className="flex flex-wrap gap-2">
                    <Button 
                      variant="outline" 
                      size="sm"
                      className="gap-2"
                      onClick={() => copyLink(company.id)}
                    >
                      <ClipboardCopy className="h-3.5 w-3.5" />
                      リンクをコピー
                    </Button>
                    <Link href={`/view/${company.id}`} target="_blank">
                      <Button variant="outline" size="sm" className="gap-2">
                        <Eye className="h-3.5 w-3.5" />
                        プレビュー
                      </Button>
                    </Link>
                    <Link href={`/admin/companies/${company.id}/pdfs`}>
                      <Button variant="outline" size="sm" className="gap-2">
                        <FileText className="h-3.5 w-3.5" />
                        PDF管理
                      </Button>
                    </Link>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
        
        {filteredCompanies.length === 0 && (
          <div className="text-center py-10">
            <FileText className="h-10 w-10 mx-auto text-muted-foreground mb-4" />
            <h3 className="text-lg font-medium mb-2">会社が見つかりません</h3>
            <p className="text-muted-foreground mb-4">
              {searchTerm ? "別のキーワードで検索してください" : "最初の会社を追加して始めましょう"}
            </p>
            <Link href="/admin/companies/create">
              <Button className="gap-2">
                <Plus className="h-4 w-4" />
                会社を追加
              </Button>
            </Link>
          </div>
        )}

        {totalItems > 0 && (
          <div className="flex items-center justify-between border-t pt-4">
            <div className="flex items-center gap-2">
              <p className="text-sm text-muted-foreground">
                表示件数
              </p>
              <Select
                value={perPage.toString()}
                onValueChange={(value) => {
                  setPerPage(Number(value));
                  setCurrentPage(1);
                }}
              >
                <SelectTrigger className="w-[70px]">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="10">10</SelectItem>
                  <SelectItem value="20">20</SelectItem>
                  <SelectItem value="50">50</SelectItem>
                  <SelectItem value="100">100</SelectItem>
                </SelectContent>
              </Select>
              <p className="text-sm text-muted-foreground">
                全{totalItems}件中 {(currentPage - 1) * perPage + 1}-
                {Math.min(currentPage * perPage, totalItems)}件を表示
              </p>
            </div>
            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => setCurrentPage(currentPage - 1)}
                disabled={currentPage === 1}
              >
                <ChevronLeft className="h-4 w-4" />
              </Button>
              {Array.from({ length: totalPages }, (_, i) => i + 1)
                .filter(page => {
                  if (totalPages <= 7) return true;
                  if (page === 1 || page === totalPages) return true;
                  if (page >= currentPage - 1 && page <= currentPage + 1) return true;
                  return false;
                })
                .map((page, index, array) => {
                  if (index > 0 && array[index - 1] !== page - 1) {
                    return (
                      <span key={`ellipsis-${page}`} className="px-2">
                        ...
                      </span>
                    );
                  }
                  return (
                    <Button
                      key={page}
                      variant={currentPage === page ? "default" : "outline"}
                      size="sm"
                      onClick={() => setCurrentPage(page)}
                    >
                      {page}
                    </Button>
                  );
                })}
              <Button
                variant="outline"
                size="sm"
                onClick={() => setCurrentPage(currentPage + 1)}
                disabled={currentPage === totalPages}
              >
                <ChevronRight className="h-4 w-4" />
              </Button>
            </div>
          </div>
        )}

        <AlertDialog 
          open={statusChangeDialog.isOpen} 
          onOpenChange={(open) => {
            if (!open) {
              setStatusChangeDialog({
                isOpen: false,
                companyId: null,
                newStatus: null,
              });
            }
          }}
        >
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>ステータスを変更</AlertDialogTitle>
              <AlertDialogDescription>
                ステータスを変更してもよろしいですか？
              </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel>キャンセル</AlertDialogCancel>
              <AlertDialogAction
                onClick={() => {
                  if (statusChangeDialog.companyId && statusChangeDialog.newStatus) {
                    handleStatusChange(statusChangeDialog.companyId, statusChangeDialog.newStatus);
                  }
                }}
              >
                変更する
              </AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>

        <AlertDialog 
          open={deleteDialog.isOpen} 
          onOpenChange={(open) => {
            if (!open) {
              setDeleteDialog({
                isOpen: false,
                companyId: null,
              });
            }
          }}
        >
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>会社を削除</AlertDialogTitle>
              <AlertDialogDescription>
                この会社を削除してもよろしいですか？この操作は取り消せません。
              </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel>キャンセル</AlertDialogCancel>
              <AlertDialogAction onClick={confirmDelete}>
                削除する
              </AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
      </div>
    </AdminLayout>
  );
}