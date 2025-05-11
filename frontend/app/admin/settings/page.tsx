'use client';

import { useState } from 'react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/components/admin/layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { Loader2, Save } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';

export default function SettingsPage() {
  const [isSaving, setIsSaving] = useState(false);
  const { toast } = useToast();
  
  const [surveyOptions, setSurveyOptions] = useState([
    { id: 1, label: 'Very Interested' },
    { id: 2, label: 'Somewhat Interested' },
    { id: 3, label: 'Need More Information' },
    { id: 4, label: 'Not Interested' },
  ]);
  
  const handleSaveSettings = () => {
    setIsSaving(true);
    
    // Simulate API call
    setTimeout(() => {
      setIsSaving(false);
      
      toast({
        title: 'Settings saved',
        description: 'Your settings have been updated successfully',
      });
    }, 1000);
  };
  
  const updateSurveyOption = (id: number, value: string) => {
    setSurveyOptions(
      surveyOptions.map(option => 
        option.id === id ? { ...option, label: value } : option
      )
    );
  };

  return (
    <AdminLayout>
      <div className="flex-1 space-y-4 p-8">
        <div className="flex items-center justify-between">
          <h2 className="text-3xl font-bold tracking-tight">設定</h2>
          <Button onClick={handleSaveSettings} disabled={isSaving} className="gap-2">
            {isSaving ? (
              <>
                <Loader2 className="h-4 w-4 animate-spin" />
                保存中...
              </>
            ) : (
              <>
                <Save className="h-4 w-4" />
                変更を保存
              </>
            )}
          </Button>
        </div>
        
        <Tabs defaultValue="general" className="space-y-4">
          <TabsList>
            <TabsTrigger value="general">一般</TabsTrigger>
            <TabsTrigger value="survey">アンケート設定</TabsTrigger>
            <TabsTrigger value="scoring">スコアリング</TabsTrigger>
            <TabsTrigger value="account">アカウント</TabsTrigger>
          </TabsList>
          
          {/* General Settings */}
          <TabsContent value="general" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>一般設定</CardTitle>
                <CardDescription>
                  アプリケーションの基本設定を行います
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="space-y-2">
                  <Label htmlFor="companyName">会社名</Label>
                  <Input id="companyName" defaultValue="株式会社サンプル" />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="defaultExpiration">リンク有効期限（日数）</Label>
                  <Input id="defaultExpiration" type="number" defaultValue="30" />
                  <p className="text-sm text-muted-foreground">
                    生成されたリンクが失効するまでの日数（0 = 無期限）
                  </p>
                </div>
                
                <Separator />
                
                <div className="space-y-4">
                  <h3 className="text-lg font-medium">機能設定</h3>
                  
                  <div className="flex items-center justify-between">
                    <div className="space-y-0.5">
                      <Label htmlFor="trackPageViews">ページビュー追跡</Label>
                      <p className="text-sm text-muted-foreground">
                        閲覧されたページと閲覧時間を記録
                      </p>
                    </div>
                    <Switch id="trackPageViews" defaultChecked />
                  </div>
                  
                  <div className="flex items-center justify-between">
                    <div className="space-y-0.5">
                      <Label htmlFor="requireSurvey">閲覧前のアンケート要求</Label>
                      <p className="text-sm text-muted-foreground">
                        資料閲覧前に興味度を確認
                      </p>
                    </div>
                    <Switch id="requireSurvey" defaultChecked />
                  </div>
                  
                  <div className="flex items-center justify-between">
                    <div className="space-y-0.5">
                      <Label htmlFor="showBookingOption">ミーティング予約オプションを表示</Label>
                      <p className="text-sm text-muted-foreground">
                        資料閲覧完了後に予約プロンプトを表示
                      </p>
                    </div>
                    <Switch id="showBookingOption" defaultChecked />
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
          
          {/* Survey Options */}
          <TabsContent value="survey" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>アンケート設定</CardTitle>
                <CardDescription>
                  ユーザーに表示する興味度アンケートをカスタマイズ
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="space-y-2">
                  <Label htmlFor="surveyTitle">アンケートタイトル</Label>
                  <Input id="surveyTitle" defaultValue="資料をご覧になる前に" />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="surveyDescription">アンケート説明</Label>
                  <Input id="surveyDescription" defaultValue="現在の興味度をお聞かせください" />
                </div>
                
                <Separator />
                
                <div className="space-y-4">
                  <h3 className="text-lg font-medium">アンケート選択肢</h3>
                  <p className="text-sm text-muted-foreground">
                    興味度アンケートの選択肢をカスタマイズ
                  </p>
                  
                  {surveyOptions.map((option) => (
                    <div key={option.id} className="flex items-center gap-4">
                      <div className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center font-medium text-sm">
                        {option.id}
                      </div>
                      <div className="flex-1">
                        <Input 
                          value={option.label} 
                          onChange={(e) => updateSurveyOption(option.id, e.target.value)}
                        />
                      </div>
                    </div>
                  ))}
                  
                  <div className="flex gap-2">
                    <Button variant="outline" className="flex-1">選択肢を追加</Button>
                    <Button variant="outline" className="flex-1">デフォルトに戻す</Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
          
          {/* Scoring Settings */}
          <TabsContent value="scoring" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>エンゲージメントスコアリング</CardTitle>
                <CardDescription>
                  エンゲージメントスコアの計算方法を設定
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="space-y-2">
                  <Label htmlFor="timeThreshold">最小閲覧時間（秒）</Label>
                  <Input id="timeThreshold" type="number" defaultValue="5" />
                  <p className="text-sm text-muted-foreground">
                    ページが閲覧されたとカウントする最小時間（秒）
                  </p>
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="completionBonus">完了ボーナスポイント</Label>
                  <Input id="completionBonus" type="number" defaultValue="20" />
                  <p className="text-sm text-muted-foreground">
                    資料を最後まで閲覧した際に付与される追加ポイント
                  </p>
                </div>
                
                <Separator />
                
                <div className="space-y-4">
                  <h3 className="text-lg font-medium">時間ベースのスコアリング</h3>
                  <p className="text-sm text-muted-foreground">
                    各ページの閲覧時間に応じて付与されるポイント
                  </p>
                  
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <Label htmlFor="timeTier1">レベル1（秒）</Label>
                      <Input id="timeTier1" type="number" defaultValue="10" />
                    </div>
                    <div>
                      <Label htmlFor="pointsTier1">ポイント</Label>
                      <Input id="pointsTier1" type="number" defaultValue="1" />
                    </div>
                  </div>
                  
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <Label htmlFor="timeTier2">レベル2（秒）</Label>
                      <Input id="timeTier2" type="number" defaultValue="30" />
                    </div>
                    <div>
                      <Label htmlFor="pointsTier2">ポイント</Label>
                      <Input id="pointsTier2" type="number" defaultValue="3" />
                    </div>
                  </div>
                  
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <Label htmlFor="timeTier3">レベル3（秒）</Label>
                      <Input id="timeTier3" type="number" defaultValue="60" />
                    </div>
                    <div>
                      <Label htmlFor="pointsTier3">ポイント</Label>
                      <Input id="pointsTier3" type="number" defaultValue="5" />
                    </div>
                  </div>
                </div>
              </CardContent>
              <CardFooter>
                <Button onClick={handleSaveSettings} disabled={isSaving} className="gap-2">
                  {isSaving ? (
                    <>
                      <Loader2 className="h-4 w-4 animate-spin" />
                      保存中...
                    </>
                  ) : (
                    <>
                      <Save className="h-4 w-4" />
                      スコアリング設定を保存
                    </>
                  )}
                </Button>
              </CardFooter>
            </Card>
          </TabsContent>
          
          {/* Account Settings */}
          <TabsContent value="account" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>アカウント設定</CardTitle>
                <CardDescription>
                  アカウント情報とパスワードの管理
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="space-y-2">
                  <Label htmlFor="fullName">氏名</Label>
                  <Input id="fullName" defaultValue="山田 太郎" />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="email">メールアドレス</Label>
                  <Input id="email" type="email" defaultValue="yamada@example.com" />
                </div>
                
                <Separator />
                
                <div className="space-y-2">
                  <Label htmlFor="currentPassword">現在のパスワード</Label>
                  <Input id="currentPassword" type="password" />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="newPassword">新しいパスワード</Label>
                  <Input id="newPassword" type="password" />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="confirmPassword">新しいパスワード（確認）</Label>
                  <Input id="confirmPassword" type="password" />
                </div>
              </CardContent>
              <CardFooter className="flex justify-between">
                <Button variant="outline">ログアウト</Button>
                <Button onClick={handleSaveSettings} disabled={isSaving} className="gap-2">
                  {isSaving ? (
                    <>
                      <Loader2 className="h-4 w-4 animate-spin" />
                      保存中...
                    </>
                  ) : (
                    'アカウントを更新'
                  )}
                </Button>
              </CardFooter>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </AdminLayout>
  );
}