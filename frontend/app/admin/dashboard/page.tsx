'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { 
  BarChart3, 
  Clock, 
  Eye, 
  FileUp, 
  FilesIcon, 
  Users,
  ChevronUp,
  ChevronDown,
  ArrowUpRight
} from 'lucide-react';

import AdminLayout from '@/components/admin/layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';

export default function DashboardPage() {
  const [mounted, setMounted] = useState(false);
  
  useEffect(() => {
    setMounted(true);
  }, []);

  if (!mounted) {
    return null;
  }

  return (
    <AdminLayout>
      <div className="flex-1 space-y-4 p-8">
        <div className="flex items-center justify-between">
          <h2 className="text-3xl font-bold tracking-tight">ダッシュボード</h2>
          <div className="flex items-center gap-2">
            <Link href="/admin/companies/create">
              <Button className="gap-2">
                <Users className="h-4 w-4" />
                会社を追加
              </Button>
            </Link>
            <Link href="/admin/companies">
              <Button variant="outline">すべての会社を表示</Button>
            </Link>
          </div>
        </div>
        
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">
                登録会社数
              </CardTitle>
              <Users className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">12</div>
              <p className="text-xs text-muted-foreground mt-1 flex items-center">
                <span className="text-green-500 flex items-center mr-1">
                  <ChevronUp className="h-3 w-3" />
                  25%
                </span>
                先月比
              </p>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">
                総閲覧数
              </CardTitle>
              <Eye className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">1,429</div>
              <p className="text-xs text-muted-foreground mt-1 flex items-center">
                <span className="text-green-500 flex items-center mr-1">
                  <ChevronUp className="h-3 w-3" />
                  18%
                </span>
                先月比
              </p>
            </CardContent>
          </Card>
        </div>
        

        
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>興味度の回答</CardTitle>
              <CardDescription>
                最新の顧客フィードバック
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div>
                  <div className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded-full bg-green-500"></div>
                    <p className="text-sm font-medium">ワンダフル株式会社</p>
                  </div>
                  <p className="text-xs text-muted-foreground mt-1 pl-5">非常に興味あり - 2時間前</p>
                </div>
                <div>
                  <div className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded-full bg-yellow-500"></div>
                    <p className="text-sm font-medium">グロベックス</p>
                  </div>
                  <p className="text-xs text-muted-foreground mt-1 pl-5">やや興味あり - 5時間前</p>
                </div>
                <div>
                  <div className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded-full bg-green-500"></div>
                    <p className="text-sm font-medium">スターク・インダストリーズ</p>
                  </div>
                  <p className="text-xs text-muted-foreground mt-1 pl-5">非常に興味あり - 1日前</p>
                </div>
                <div>
                  <div className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded-full bg-red-500"></div>
                    <p className="text-sm font-medium">サイバーダイン・システムズ</p>
                  </div>
                  <p className="text-xs text-muted-foreground mt-1 pl-5">興味なし - 2日前</p>
                </div>
              </div>
            </CardContent>
          </Card>
          
          
          <Card>
            <CardHeader>
              <CardTitle>最近のアクティビティ</CardTitle>
              <CardDescription>
                最新の資料閲覧状況
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="flex justify-between items-start">
                  <div>
                    <p className="text-sm font-medium">ワンダフル株式会社 - 製品概要</p>
                    <p className="text-xs text-muted-foreground">14分前に閲覧</p>
                  </div>
                  <Link href="/admin/companies/1/access-log">
                    <Button variant="ghost" size="icon" className="h-6 w-6">
                      <ArrowUpRight className="h-4 w-4" />
                    </Button>
                  </Link>
                </div>
                <div className="flex justify-between items-start">
                  <div>
                    <p className="text-sm font-medium">グロベックス - セキュリティ資料</p>
                    <p className="text-xs text-muted-foreground">1時間前に完了</p>
                  </div>
                  <Link href="/admin/companies/2/access-log">
                    <Button variant="ghost" size="icon" className="h-6 w-6">
                      <ArrowUpRight className="h-4 w-4" />
                    </Button>
                  </Link>
                </div>
                <div className="flex justify-between items-start">
                  <div>
                    <p className="text-sm font-medium">イニテック - 事例資料</p>
                    <p className="text-xs text-muted-foreground">3時間前に閲覧</p>
                  </div>
                  <Link href="/admin/companies/3/access-log">
                    <Button variant="ghost" size="icon" className="h-6 w-6">
                      <ArrowUpRight className="h-4 w-4" />
                    </Button>
                  </Link>
                </div>
                <div className="flex justify-between items-start">
                  <div>
                    <p className="text-sm font-medium">アンブレラ - 技術仕様書</p>
                    <p className="text-xs text-muted-foreground">5時間前に閲覧</p>
                  </div>
                  <Link href="/admin/companies/4/access-log">
                    <Button variant="ghost" size="icon" className="h-6 w-6">
                      <ArrowUpRight className="h-4 w-4" />
                    </Button>
                  </Link>
                </div>
              </div>
            </CardContent>
          </Card>




        </div>
      </div>
    </AdminLayout>
  );
}