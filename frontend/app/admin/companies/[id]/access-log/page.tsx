'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { ArrowLeft, Clock, Download, FileText, Search, SortAsc, SortDesc } from 'lucide-react';

import AdminLayout from '@/components/admin/layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useToast } from '@/hooks/use-toast';
import { companyApi, pdfApi, PDF } from '@/lib/api';

interface ViewLog {
  id: string;
  document_id: string;
  page_number: number;
  view_duration: number;
  viewer_ip: string;
  viewer_user_agent: string;
  viewed_at: string;
  viewer_metadata?: any;
  created_at: string;
  updated_at: string;
  document_title?: string;
}

interface PageSummary {
  document_id: string;
  document_title: string;
  page_number: number;
  total_views: number;
  avg_duration: number;
  last_viewed: string;
}

export default function AccessLogPage({ params }: { params: Promise<{ id: string }> }) {
  const [companyId, setCompanyId] = useState<string>('');
  const [companyName, setCompanyName] = useState<string>('');
  const [documents, setDocuments] = useState<PDF[]>([]);
  const [viewLogs, setViewLogs] = useState<ViewLog[]>([]);
  const [pageSummary, setPageSummary] = useState<PageSummary[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('desc');
  const { toast } = useToast();

  useEffect(() => {
    const initializeData = async () => {
      try {
        const resolvedParams = await params;
        const id = resolvedParams.id;
        setCompanyId(id);

        // 会社情報を取得
        const company = await companyApi.get(id);
        setCompanyName(company.name);

        // PDF一覧を取得
        const pdfsResponse = await pdfApi.getAll(id);
        setDocuments(pdfsResponse.data);

        // 会社全体のアクセスログを取得
        console.log('Fetching company view logs for company:', id);
        const allViewLogsResponse = await pdfApi.getCompanyViewLogs(id);
        console.log('Received view logs response:', allViewLogsResponse);
        
        // ページネーション形式のレスポンスからdataを取得
        const allViewLogs = allViewLogsResponse.data || allViewLogsResponse;
        console.log('View logs data:', allViewLogs);
        console.log('View logs type:', typeof allViewLogs);
        console.log('View logs length:', Array.isArray(allViewLogs) ? allViewLogs.length : 'Not an array');

        // PDF情報をログに追加
        const logsWithTitle = (Array.isArray(allViewLogs) ? allViewLogs : []).map((log: any) => {
          const document = pdfsResponse.data.find(pdf => pdf.id === log.document_id);
          return {
            ...log,
            document_title: document ? document.title : '不明なドキュメント'
          };
        });

        console.log('Logs with title:', logsWithTitle);
        setViewLogs(logsWithTitle);

        // ページサマリーを計算
        const summaryMap = new Map<string, PageSummary>();
        const pageGroups = logsWithTitle.reduce((acc: any, log: any) => {
          const key = `${log.document_id}-${log.page_number}`;
          if (!acc[key]) {
            acc[key] = {
              document_id: log.document_id,
              document_title: log.document_title,
              page_number: log.page_number,
              views: [],
              last_viewed: log.viewed_at
            };
          }
          acc[key].views.push(log.view_duration);
          if (new Date(log.viewed_at) > new Date(acc[key].last_viewed)) {
            acc[key].last_viewed = log.viewed_at;
          }
          return acc;
        }, {});

        Object.values(pageGroups).forEach((group: any) => {
          const avgDuration = group.views.reduce((sum: number, duration: number) => sum + duration, 0) / group.views.length;
          summaryMap.set(`${group.document_id}-${group.page_number}`, {
            document_id: group.document_id,
            document_title: group.document_title,
            page_number: group.page_number,
            total_views: group.views.length,
            avg_duration: Math.round(avgDuration),
            last_viewed: group.last_viewed
          });
        });

        setPageSummary(Array.from(summaryMap.values()));
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

    initializeData();
  }, [params, toast]);

  // フィルタリングとソート
  const filteredLogs = viewLogs
    .filter(log => 
      log.document_title?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      log.viewer_ip.includes(searchTerm) ||
      log.page_number.toString().includes(searchTerm)
    )
    .sort((a, b) => {
      const dateA = new Date(a.viewed_at);
      const dateB = new Date(b.viewed_at);
      return sortOrder === 'desc' ? dateB.getTime() - dateA.getTime() : dateA.getTime() - dateB.getTime();
    });

  const filteredSummary = pageSummary
    .filter(summary => 
      summary.document_title.toLowerCase().includes(searchTerm.toLowerCase()) ||
      summary.page_number.toString().includes(searchTerm)
    )
    .sort((a, b) => {
      if (a.document_title !== b.document_title) {
        return a.document_title.localeCompare(b.document_title);
      }
      return a.page_number - b.page_number;
    });

  // 統計計算
  const totalViews = viewLogs.length;
  const avgTimeSpent = viewLogs.length > 0 
    ? Math.round(viewLogs.reduce((sum, log) => sum + log.view_duration, 0) / viewLogs.length)
    : 0;
  const lastActivity = viewLogs.length > 0 
    ? new Date(Math.max(...viewLogs.map(log => new Date(log.viewed_at).getTime())))
    : null;
  const daysAgo = lastActivity 
    ? Math.floor((Date.now() - lastActivity.getTime()) / (1000 * 60 * 60 * 24))
    : null;

  const formatDuration = (seconds: number) => {
    if (seconds < 60) return `${seconds}s`;
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}m ${remainingSeconds}s`;
  };

  const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString('ja-JP', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    });
  };

  const formatLastActivity = () => {
    if (!lastActivity || daysAgo === null) {
      return 'データなし';
    }
    if (daysAgo === 0) {
      return '今日';
    }
    return `${daysAgo}日前`;
  };

  if (isLoading) {
    return (
      <AdminLayout>
        <div className="flex-1 p-8">
          <div className="flex items-center justify-center h-64">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
          </div>
        </div>
      </AdminLayout>
    );
  }

  return (
    <AdminLayout>
      <div className="flex-1 p-8">
        <Link href="/admin/companies" className="inline-block">
          <Button 
            variant="ghost" 
            className="gap-2 mb-6"
          >
            <ArrowLeft className="h-4 w-4" />
            会社一覧に戻る
          </Button>
        </Link>
        
        <div className="mb-6">
          <h2 className="text-3xl font-bold tracking-tight mb-2">アクセスログ</h2>
          <p className="text-muted-foreground">
            {companyName}
          </p>
        </div>
        
        <div className="grid gap-6 mb-6 md:grid-cols-3">
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium">総閲覧数</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{totalViews}</div>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium">ページあたりの平均時間</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{formatDuration(avgTimeSpent)}</div>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium">最終アクティビティ</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">
                {formatLastActivity()}
              </div>
            </CardContent>
          </Card>
        </div>

        <Tabs defaultValue="summary">
          <TabsList className="grid grid-cols-2 w-[400px] mb-6">
            <TabsTrigger value="summary">ページサマリー</TabsTrigger>
            <TabsTrigger value="activity">アクティビティログ</TabsTrigger>
          </TabsList>
          
          <TabsContent value="summary" className="space-y-4">
            <div className="flex justify-between">
              <div className="flex items-center gap-2">
                <FileText className="h-5 w-5 text-muted-foreground" />
                <span className="text-muted-foreground">ページごとのエンゲージメント指標</span>
              </div>
              
              <div className="relative w-[300px]">
                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="PDF名やページ番号で検索..."
                  className="pl-8"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </div>
            </div>
            
            <Card>
              <CardContent className="p-0">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>PDF名</TableHead>
                      <TableHead>ページ番号</TableHead>
                      <TableHead>閲覧数</TableHead>
                      <TableHead>平均閲覧時間</TableHead>
                      <TableHead>最終閲覧</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {filteredSummary.length === 0 ? (
                      <TableRow>
                        <TableCell colSpan={5} className="text-center py-8 text-muted-foreground">
                          アクセスログがありません
                        </TableCell>
                      </TableRow>
                    ) : (
                      filteredSummary.map((summary) => (
                        <TableRow key={`${summary.document_id}-${summary.page_number}`}>
                          <TableCell className="font-medium">{summary.document_title}</TableCell>
                          <TableCell>ページ {summary.page_number}</TableCell>
                          <TableCell>{summary.total_views}</TableCell>
                          <TableCell>{formatDuration(summary.avg_duration)}</TableCell>
                          <TableCell>{formatDateTime(summary.last_viewed)}</TableCell>
                        </TableRow>
                      ))
                    )}
                  </TableBody>
                </Table>
              </CardContent>
            </Card>
          </TabsContent>
          
          <TabsContent value="activity" className="space-y-4">
            <div className="flex justify-between">
              <div className="relative w-[300px]">
                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="ログを検索..."
                  className="pl-8"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </div>
              
              <div className="flex gap-2">
                <Button 
                  variant="outline" 
                  size="icon" 
                  title="昇順に並び替え"
                  onClick={() => setSortOrder('asc')}
                  className={sortOrder === 'asc' ? 'bg-gray-100' : ''}
                >
                  <SortAsc className="h-4 w-4" />
                </Button>
                <Button 
                  variant="outline" 
                  size="icon" 
                  title="降順に並び替え"
                  onClick={() => setSortOrder('desc')}
                  className={sortOrder === 'desc' ? 'bg-gray-100' : ''}
                >
                  <SortDesc className="h-4 w-4" />
              </Button>
              </div>
            </div>
            
            <Card>
              <CardContent className="p-0">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>タイムスタンプ</TableHead>
                      <TableHead>PDF名</TableHead>
                      <TableHead>ページ</TableHead>
                      <TableHead>閲覧時間</TableHead>
                      <TableHead className="hidden md:table-cell">IPアドレス</TableHead>
                      <TableHead className="hidden lg:table-cell">ユーザーエージェント</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {filteredLogs.length === 0 ? (
                      <TableRow>
                        <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                          アクセスログがありません
                        </TableCell>
                      </TableRow>
                    ) : (
                      filteredLogs.map((log) => (
                        <TableRow key={log.id}>
                          <TableCell className="font-medium">{formatDateTime(log.viewed_at)}</TableCell>
                          <TableCell>{log.document_title}</TableCell>
                          <TableCell>ページ {log.page_number}</TableCell>
                          <TableCell>{formatDuration(log.view_duration)}</TableCell>
                          <TableCell className="hidden md:table-cell">{log.viewer_ip}</TableCell>
                          <TableCell className="hidden lg:table-cell max-w-[200px] truncate" title={log.viewer_user_agent}>
                            {log.viewer_user_agent}
                          </TableCell>
                        </TableRow>
                      ))
                    )}
                  </TableBody>
                </Table>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </AdminLayout>
  );
}