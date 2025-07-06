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
  ArrowUpRight,
  Loader2
} from 'lucide-react';

import AdminLayout from '@/components/admin/layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { dashboardApi, DashboardData } from '@/lib/api';
import { useToast } from '@/hooks/use-toast';

export default function DashboardPage() {
  const [mounted, setMounted] = useState(false);
  const [dashboardData, setDashboardData] = useState<DashboardData | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const { toast } = useToast();
  
  useEffect(() => {
    setMounted(true);
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      setIsLoading(true);
      const response = await dashboardApi.getStats();
      setDashboardData(response.data);
    } catch (error) {
      console.error('Failed to fetch dashboard data:', error);
      toast({
        title: 'エラーが発生しました',
        description: 'ダッシュボードデータの取得に失敗しました',
        variant: 'destructive',
      });
    } finally {
      setIsLoading(false);
    }
  };

  // 時間差表示用のヘルパー関数
  const getTimeAgo = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInMs = now.getTime() - date.getTime();
    const diffInMinutes = Math.floor(diffInMs / (1000 * 60));
    const diffInHours = Math.floor(diffInMinutes / 60);
    const diffInDays = Math.floor(diffInHours / 24);

    if (diffInMinutes < 60) {
      return `${diffInMinutes}分前`;
    } else if (diffInHours < 24) {
      return `${diffInHours}時間前`;
    } else {
      return `${diffInDays}日前`;
    }
  };

  // フィードバック情報の解析
  const parseFeedbackData = (feedback: any) => {
    // contentからスコア情報を抽出を試行
    const content = feedback.content || '';
    let score = null;
    let label = '';

    // "スコア: 100" パターンの抽出
    const scoreMatch = content.match(/スコア:\s*(\d+)/);
    if (scoreMatch) {
      score = parseInt(scoreMatch[1]);
    }

    // metadata からの情報取得
    if (feedback.metadata) {
      // 新しい形式: selected_option.score
      if (feedback.metadata.selected_option?.score !== undefined) {
        score = feedback.metadata.selected_option.score;
        label = feedback.metadata.selected_option.label || '';
      }
      // 古い形式: interest_level
      else if (feedback.metadata.interest_level !== undefined && feedback.metadata.interest_level !== null) {
        score = feedback.metadata.interest_level;
      }
    }

    // ラベルの抽出（contentから）
    if (!label) {
      // "非常に興味がある (スコア: 100)" パターンからラベル抽出
      const labelMatch = content.match(/^(.+?)\s*\(スコア:/);
      if (labelMatch) {
        label = labelMatch[1].trim();
      } else {
        label = content.replace(/\s*\(スコア:.*?\)/, '').trim();
      }
    }

    return { score, label };
  };

  // 設定に基づく色マッピング（動的）
  const getFeedbackColor = (feedback: any) => {
    const { score } = parseFeedbackData(feedback);
    
    if (score !== null && dashboardData?.survey_settings?.options) {
      const options = dashboardData.survey_settings.options;
      
      // スコアに基づいて適切な選択肢を見つける
      for (const option of options.sort((a, b) => b.score - a.score)) {
        if (score >= option.score) {
          // スコアの範囲に基づいて色を決定
          if (option.score >= 90) return 'bg-green-600';      // 濃い緑: 非常に高い
          if (option.score >= 75) return 'bg-green-500';      // 緑: 高い
          if (option.score >= 50) return 'bg-yellow-500';     // 黄色: 中間
          if (option.score >= 25) return 'bg-orange-500';     // オレンジ: 低い
          return 'bg-red-500';                                 // 赤: 非常に低い
        }
      }
    }
    
    // フォールバック（設定が取得できない場合）
    if (score !== null) {
      if (score >= 90) return 'bg-green-600';      
      if (score >= 75) return 'bg-green-500';      
      if (score >= 50) return 'bg-yellow-500';     
      if (score >= 25) return 'bg-orange-500';     
      return 'bg-red-500';                         
    }
    
    return 'bg-gray-500';
  };

  // 設定に基づくテキスト表示（動的）
  const getFeedbackText = (feedback: any) => {
    const { score, label } = parseFeedbackData(feedback);
    
    // 既存のラベルがある場合はそれを使用
    if (label) {
      return `${label}${score !== null ? ` (${score}点)` : ''}`;
    }
    
    // 設定されたスコア範囲に基づいてラベルを決定
    if (score !== null && dashboardData?.survey_settings?.options) {
      const options = dashboardData.survey_settings.options;
      
      // 最も近いスコアの選択肢を見つける
      let closestOption = options[0];
      let minDiff = Math.abs(score - options[0].score);
      
      for (const option of options) {
        const diff = Math.abs(score - option.score);
        if (diff < minDiff) {
          minDiff = diff;
          closestOption = option;
        }
      }
      
      return `${closestOption.label} (${score}点)`;
    }
    
    // フォールバック: スコアベースの表示
    if (score !== null) {
      if (score >= 90) return `非常に興味あり (${score}点)`;
      if (score >= 75) return `かなり興味あり (${score}点)`;
      if (score >= 50) return `やや興味あり (${score}点)`;
      if (score >= 25) return `少し興味あり (${score}点)`;
      return `興味なし (${score}点)`;
    }
    
    // 最終フォールバック
    return feedback.feedback_type || '回答あり';
  };

  if (!mounted) {
    return null;
  }

  if (isLoading) {
    return (
      <AdminLayout>
        <div className="flex-1 space-y-4 p-8">
          <div className="flex items-center justify-center h-64">
            <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
            <span className="ml-2">データを読み込み中...</span>
          </div>
        </div>
      </AdminLayout>
    );
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
              <div className="text-2xl font-bold">{dashboardData?.stats.total_companies || 0}</div>
              <p className="text-xs text-muted-foreground mt-1 flex items-center">
                <span className={`flex items-center mr-1 ${
                  (dashboardData?.stats.company_growth_rate || 0) >= 0 ? 'text-green-500' : 'text-red-500'
                }`}>
                  {(dashboardData?.stats.company_growth_rate || 0) >= 0 ? (
                    <ChevronUp className="h-3 w-3" />
                  ) : (
                    <ChevronDown className="h-3 w-3" />
                  )}
                  {Math.abs(dashboardData?.stats.company_growth_rate || 0)}%
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
              <div className="text-2xl font-bold">{dashboardData?.stats.total_views || 0}</div>
              <p className="text-xs text-muted-foreground mt-1 flex items-center">
                <span className={`flex items-center mr-1 ${
                  (dashboardData?.stats.view_growth_rate || 0) >= 0 ? 'text-green-500' : 'text-red-500'
                }`}>
                  {(dashboardData?.stats.view_growth_rate || 0) >= 0 ? (
                    <ChevronUp className="h-3 w-3" />
                  ) : (
                    <ChevronDown className="h-3 w-3" />
                  )}
                  {Math.abs(dashboardData?.stats.view_growth_rate || 0)}%
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
                {dashboardData?.recent_feedback.length ? (
                  dashboardData.recent_feedback.map((feedback, index) => (
                    <div key={index}>
                      <div className="flex items-center gap-2">
                        <div className={`w-3 h-3 rounded-full ${getFeedbackColor(feedback)}`}></div>
                        <p className="text-sm font-medium">{feedback.document.company.name} - {feedback.document.title}</p>
                      </div>
                      <p className="text-xs text-muted-foreground mt-1 pl-5">
                        {getFeedbackText(feedback)} - {getTimeAgo(feedback.created_at)}
                      </p>
                    </div>
                  ))
                ) : (
                  <p className="text-sm text-muted-foreground">フィードバックがありません</p>
                )}
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
                {dashboardData?.recent_activity.length ? (
                  dashboardData.recent_activity.map((activity, index) => (
                    <div key={index} className="flex justify-between items-start">
                      <div>
                        <p className="text-sm font-medium">{activity.document.company.name} - {activity.document.title}</p>
                        <p className="text-xs text-muted-foreground">{getTimeAgo(activity.viewed_at)}に閲覧</p>
                      </div>
                      {activity.document.company.id && (
                        <Link href={`/admin/companies/${activity.document.company.id}/access-log`}>
                          <Button variant="ghost" size="icon" className="h-6 w-6">
                            <ArrowUpRight className="h-4 w-4" />
                          </Button>
                        </Link>
                      )}
                    </div>
                  ))
                ) : (
                  <p className="text-sm text-muted-foreground">最近のアクティビティがありません</p>
                )}
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </AdminLayout>
  );
}