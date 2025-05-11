import Link from 'next/link';
import { ArrowLeft, Clock, Download, ExternalLink, FileText, Search, SortAsc, SortDesc } from 'lucide-react';

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

// サンプルデータ - アクセスログ
const accessLogs = [
  {
    id: 1,
    timestamp: '2025-06-01 14:32:45',
    page: 1,
    timeSpent: '45s',
    ipAddress: '192.168.1.1',
    userAgent: 'Chrome/120.0.0.0',
  },
  {
    id: 2,
    timestamp: '2025-06-01 14:33:30',
    page: 2,
    timeSpent: '58s',
    ipAddress: '192.168.1.1',
    userAgent: 'Chrome/120.0.0.0',
  },
  {
    id: 3,
    timestamp: '2025-06-01 14:34:28',
    page: 3,
    timeSpent: '32s',
    ipAddress: '192.168.1.1',
    userAgent: 'Chrome/120.0.0.0',
  },
  {
    id: 4,
    timestamp: '2025-06-01 14:35:00',
    page: 4,
    timeSpent: '125s',
    ipAddress: '192.168.1.1',
    userAgent: 'Chrome/120.0.0.0',
  },
  {
    id: 5,
    timestamp: '2025-06-01 14:37:05',
    page: 5,
    timeSpent: '68s',
    ipAddress: '192.168.1.1',
    userAgent: 'Chrome/120.0.0.0',
  },
  {
    id: 6,
    timestamp: '2025-06-02 09:15:22',
    page: 1,
    timeSpent: '22s',
    ipAddress: '192.168.1.1',
    userAgent: 'Chrome/120.0.0.0',
  },
  {
    id: 7,
    timestamp: '2025-06-02 09:15:44',
    page: 2,
    timeSpent: '35s',
    ipAddress: '192.168.1.1',
    userAgent: 'Chrome/120.0.0.0',
  },
];

// サンプルデータ - ページサマリー
const pageSummary = [
  { page: 1, views: 2, avgTimeSpent: '33.5s', lastViewed: '2025-06-02 09:15:22' },
  { page: 2, views: 2, avgTimeSpent: '46.5s', lastViewed: '2025-06-02 09:15:44' },
  { page: 3, views: 1, avgTimeSpent: '32s', lastViewed: '2025-06-01 14:34:28' },
  { page: 4, views: 1, avgTimeSpent: '125s', lastViewed: '2025-06-01 14:35:00' },
  { page: 5, views: 1, avgTimeSpent: '68s', lastViewed: '2025-06-01 14:37:05' },
];

// 静的パラメータの生成
export async function generateStaticParams() {
  // サンプル用のIDを返す
  return [
    { id: '1' },
    { id: '2' },
    { id: '3' },
    { id: '4' },
    { id: '5' },
    { id: '6' },
    { id: '7' },
  ];
}

export default function AccessLogPage({ params }: { params: { id: string } }) {
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
             Wonderful株式会社 - 第2四半期製品概要
          </p>
        </div>
        
        <div className="grid gap-6 mb-6 md:grid-cols-4">
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium">総閲覧数</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">7</div>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium">閲覧ページ数</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">5 / 8</div>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium">ページあたりの平均時間</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">62s</div>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium">最終アクティビティ</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">2日前</div>
            </CardContent>
          </Card>
        </div>

        <Tabs defaultValue="activity">
          <TabsList className="grid grid-cols-2 w-[400px] mb-6">
            <TabsTrigger value="activity">アクティビティログ</TabsTrigger>
            <TabsTrigger value="summary">ページサマリー</TabsTrigger>
          </TabsList>
          
          <TabsContent value="activity" className="space-y-4">
            <div className="flex justify-between">
              <div className="relative w-[300px]">
                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="ログを検索..."
                  className="pl-8"
                />
              </div>
              
              <div className="flex gap-2">
                <Button variant="outline" size="icon" title="昇順に並び替え">
                  <SortAsc className="h-4 w-4" />
                </Button>
                <Button variant="outline" size="icon" title="降順に並び替え">
                  <SortDesc className="h-4 w-4" />
                </Button>
                <Button variant="outline" size="sm" className="gap-2">
                  <Download className="h-4 w-4" />
                  エクスポート
                </Button>
              </div>
            </div>
            
            <Card>
              <CardContent className="p-0">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>タイムスタンプ</TableHead>
                      <TableHead>ページ</TableHead>
                      <TableHead>閲覧時間</TableHead>
                      <TableHead className="hidden md:table-cell">IPアドレス</TableHead>
                      <TableHead className="hidden md:table-cell">ユーザーエージェント</TableHead>
                      <TableHead className="w-[100px]">アクション</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {accessLogs.map((log) => (
                      <TableRow key={log.id}>
                        <TableCell className="font-medium">{log.timestamp}</TableCell>
                        <TableCell>{log.page}</TableCell>
                        <TableCell>{log.timeSpent}</TableCell>
                        <TableCell className="hidden md:table-cell">{log.ipAddress}</TableCell>
                        <TableCell className="hidden md:table-cell">{log.userAgent}</TableCell>
                        <TableCell>
                          <Button variant="ghost" size="icon" title="詳細を表示">
                            <ExternalLink className="h-4 w-4" />
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </CardContent>
            </Card>
          </TabsContent>
          
          <TabsContent value="summary" className="space-y-4">
            <div className="flex justify-between">
              <div className="flex items-center gap-2">
                <FileText className="h-5 w-5 text-muted-foreground" />
                <span className="text-muted-foreground">ページごとのエンゲージメント指標</span>
              </div>
              
              <Button variant="outline" size="sm" className="gap-2">
                <Download className="h-4 w-4" />
                エクスポート
              </Button>
            </div>
            
            <Card>
              <CardContent className="p-0">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>ページ番号</TableHead>
                      <TableHead>閲覧数</TableHead>
                      <TableHead>平均閲覧時間</TableHead>
                      <TableHead>最終閲覧</TableHead>
                      <TableHead className="w-[100px]">アクション</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {pageSummary.map((page) => (
                      <TableRow key={page.page}>
                        <TableCell className="font-medium">ページ {page.page}</TableCell>
                        <TableCell>{page.views}</TableCell>
                        <TableCell>{page.avgTimeSpent}</TableCell>
                        <TableCell>{page.lastViewed}</TableCell>
                        <TableCell>
                          <Button variant="ghost" size="icon" title="詳細を表示">
                            <Clock className="h-4 w-4" />
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))}
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