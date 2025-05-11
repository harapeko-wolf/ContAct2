'use client';

import { useState } from 'react';
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
  Users
} from 'lucide-react';
import { cn } from '@/lib/utils'; // cn関数をインポート

// ステータスの定義
const STATUS_OPTIONS = ['すべて', '受注', '検討中', '失注'] as const;
type StatusType = typeof STATUS_OPTIONS[number];

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
import { Input } from '@/components/ui/input';
import { useToast } from '@/hooks/use-toast';

// 会社のモックデータ
const companies = [
  {
    id: 1,
    name: 'Wonderful Corporation',
    contactPerson: 'John Smith',
    email: 'john@wanderful.com',
    documents: 4,
    views: 127,
    score: 85,
    status: '検討中',
    uuid: '8f7d1c6e-95a2-4eeb-8acd-f310a3a2'
  },
  {
    id: 2,
    name: 'Globex Industries',
    contactPerson: 'Susan Johnson',
    email: 'susan@globex.com',
    documents: 2,
    views: 89,
    score: 72,
    status: '受注',
    uuid: '3e5f2b7a-12c6-48d9-b7e1-a2f9c8d3'
  },
  {
    id: 3,
    name: 'Stark Enterprises',
    contactPerson: 'Tony Stark',
    email: 'tony@stark.com',
    documents: 6,
    views: 245,
    score: 93,
    status: '受注',
    uuid: '9c4b7d2e-65a1-4f7c-93b2-e8d5f1a6'
  },
  {
    id: 4,
    name: 'Initech',
    contactPerson: 'Peter Gibbons',
    email: 'peter@initech.com',
    documents: 1,
    views: 32,
    score: 45,
    status: '失注',
    uuid: '5a8c2d7f-41e6-49b3-8e7d-2f9a1c5b'
  },
];

export default function CompaniesPage() {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedStatus, setSelectedStatus] = useState<StatusType>('すべて');
  const { toast } = useToast();

  const filteredCompanies = companies.filter(company => {
    const matchesSearch = 
      company.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      company.contactPerson.toLowerCase().includes(searchTerm.toLowerCase()) ||
      company.email.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesStatus = selectedStatus === 'すべて' || company.status === selectedStatus;
    
    return matchesSearch && matchesStatus;
  });

  const copyLink = (uuid: string) => {
    navigator.clipboard.writeText(`${window.location.origin}/view/${uuid}`);
    toast({
      title: 'リンクをコピーしました',
      description: '会社と共有できるリンクをクリップボードにコピーしました',
    });
  };

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
          {filteredCompanies.map(company => (
            <Card key={company.id} className="overflow-hidden">
              <CardHeader className="pb-2">
                <div className="flex justify-between items-start">
                  <div>
                    <div className="flex items-center gap-2 mb-1">
                      <span className={cn(
                        "px-2 py-0.5 text-xs font-medium rounded-full",
                        company.status === '受注' && "bg-green-100 text-green-700",
                        company.status === '検討中' && "bg-blue-100 text-blue-700",
                        company.status === '失注' && "bg-red-100 text-red-700"
                      )}>
                        {company.status}
                      </span>
                    </div>
                    <CardTitle className="text-xl">
                      {company.name}
                    </CardTitle>
                  </div>
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost" size="icon" className="h-8 w-8">
                        <MoreHorizontal className="h-4 w-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem 
                        className="gap-2 cursor-pointer"
                        onClick={() => copyLink(company.uuid)}
                      >
                        <ClipboardCopy className="h-4 w-4" />
                        リンクをコピー
                      </DropdownMenuItem>
                      <Link href={`/admin/companies/${company.id}/edit`}>
                        <DropdownMenuItem className="gap-2 cursor-pointer">
                          <Edit className="h-4 w-4" />
                          会社を編集
                        </DropdownMenuItem>
                      </Link>
                      <Link href={`/admin/companies/${company.id}/pdfs`}>
                        <DropdownMenuItem className="gap-2 cursor-pointer">
                          <FileText className="h-4 w-4" />
                          PDF管理
                        </DropdownMenuItem>
                      </Link>
                      <DropdownMenuSeparator />
                      <Link href={`/admin/companies/${company.id}/access-log`}>
                        <DropdownMenuItem className="gap-2 cursor-pointer">
                          <Eye className="h-4 w-4" />
                          アクセスログを表示
                        </DropdownMenuItem>
                      </Link>
                   
                      <DropdownMenuSeparator />
                      <DropdownMenuItem className="gap-2 cursor-pointer text-red-600">
                        <Trash2 className="h-4 w-4" />
                        会社を削除
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>
                <CardDescription>
                  {company.contactPerson} | {company.email}
                </CardDescription>
              </CardHeader>
              <CardContent className="pb-4">
                <div className="space-y-4">
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <p className="text-muted-foreground">エンゲージメントスコア</p>
                      <p className="font-medium flex items-center gap-1">
                        <span className={cn(
                          "inline-block w-2 h-2 rounded-full",
                          company.score >= 80 ? "bg-green-500" :
                          company.score >= 60 ? "bg-yellow-500" :
                          "bg-red-500"
                        )} />
                        {company.score}
                      </p>
                    </div>
                    <div>
                      <p className="text-muted-foreground">総閲覧数</p>
                      <p className="font-medium">{company.views}</p>
                    </div>
                  </div>
                  
                  <div className="flex flex-wrap gap-2">
                    <Button 
                      variant="outline" 
                      size="sm"
                      className="gap-2"
                      onClick={() => copyLink(company.uuid)}
                    >
                      <ClipboardCopy className="h-3.5 w-3.5" />
                      リンクをコピー
                    </Button>
                    <Link href={`/view/${company.uuid}`} target="_blank">
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
      </div>
    </AdminLayout>
  );
}