'use client';

import { useState, useEffect } from 'react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/components/admin/layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { Loader2, Save, Trash2, Plus, GripVertical, AlertCircle, Eye, EyeOff } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';
import { Alert, AlertDescription } from '@/components/ui/alert';

// 設定データの型定義
interface GeneralSettings {
  defaultExpiration: number;
  trackPageViews: boolean;
  requireSurvey: boolean;
  showBookingOption: boolean;
}

interface SurveyOption {
  id: number;
  label: string;
  score: number;
}

interface SurveySettings {
  title: string;
  description: string;
  options: SurveyOption[];
}

interface ScoringTier {
  timeThreshold: number;
  points: number;
}

interface ScoringSettings {
  timeThreshold: number;
  completionBonus: number;
  tiers: ScoringTier[];
}

interface FollowupEmailSettings {
  enabled: boolean;
  delayMinutes: number;
  subject: string;
}

interface AccountSettings {
  fullName: string;
  email: string;
  companyName: string;
  currentPassword: string;
  newPassword: string;
  confirmPassword: string;
}

interface AppSettings {
  general?: GeneralSettings;
  survey?: SurveySettings;
  scoring?: ScoringSettings;
  followupEmail?: FollowupEmailSettings;
  account: AccountSettings;
}

interface UserMeta {
  timestamp: string;
  is_admin: boolean;
}

export default function SettingsPage() {
  const [isSaving, setIsSaving] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [hasUnsavedChanges, setHasUnsavedChanges] = useState(false);
  const [activeTab, setActiveTab] = useState('account');
  const [showSurveyPreview, setShowSurveyPreview] = useState(false);
  const [isAdmin, setIsAdmin] = useState(false);
  const { toast } = useToast();
  
  // メインの設定state
  const [settings, setSettings] = useState<AppSettings>({
    general: {
      defaultExpiration: 30,
      trackPageViews: true,
      requireSurvey: true,
      showBookingOption: true,
    },
    survey: {
      title: '',
      description: '',
      options: [],
    },
    scoring: {
      timeThreshold: 5,
      completionBonus: 20,
      tiers: [],
    },
    followupEmail: {
      enabled: true,
      delayMinutes: 15,
      subject: '資料のご確認ありがとうございました - さらに詳しくご説明いたします',
    },
    account: {
      fullName: '',
      email: '',
      companyName: '',
      currentPassword: '',
      newPassword: '',
      confirmPassword: '',
    },
  });

  // 初期設定データ（差分検出用）
  const [initialSettings, setInitialSettings] = useState<AppSettings>(settings);

  // バリデーション状態
  const [validationErrors, setValidationErrors] = useState<Record<string, string>>({});
  const [passwordStrength, setPasswordStrength] = useState<{
    score: number;
    feedback: string[];
    isValid: boolean;
  }>({ score: 0, feedback: [], isValid: false });

  // パスワード表示状態
  const [showPasswords, setShowPasswords] = useState({
    current: false,
    new: false,
    confirm: false,
  });

  // API関数
  const fetchSettings = async () => {
    try {
      setIsLoading(true);
      
      const response = await fetch('/api/settings', {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${getCookieValue('token')}`,
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      console.log('取得した設定データ:', data);
      
      if (data.data && data.meta) {
        // 管理者権限を設定
        setIsAdmin(data.meta.is_admin || false);
        
        // デフォルト値を確実に設定
        const settingsWithDefaults: AppSettings = {
          account: {
            fullName: data.data.account?.fullName || '',
            email: data.data.account?.email || '',
            companyName: data.data.account?.companyName || '',
            currentPassword: '',
            newPassword: '',
            confirmPassword: '',
          },
        };

        // 管理者の場合のみシステム設定を追加
        if (data.meta.is_admin) {
          settingsWithDefaults.general = {
            defaultExpiration: data.data.general?.defaultExpiration ?? 30,
            trackPageViews: data.data.general?.trackPageViews ?? true,
            requireSurvey: data.data.general?.requireSurvey ?? true,
            showBookingOption: data.data.general?.showBookingOption ?? true,
          };
          
          settingsWithDefaults.survey = {
            title: data.data.survey?.title || '資料をご覧になる前に',
            description: data.data.survey?.description || '現在の興味度をお聞かせください',
            options: data.data.survey?.options?.length > 0 ? data.data.survey.options : [
              { id: 1, label: '非常に興味がある', score: 100 },
              { id: 2, label: 'やや興味がある', score: 75 },
              { id: 3, label: '詳しい情報が必要', score: 50 },
              { id: 4, label: '興味なし', score: 0 },
            ],
          };
          
          settingsWithDefaults.scoring = {
            timeThreshold: data.data.scoring?.timeThreshold ?? 5,
            completionBonus: data.data.scoring?.completionBonus ?? 20,
            tiers: data.data.scoring?.tiers?.length > 0 ? data.data.scoring.tiers : [
              { timeThreshold: 10, points: 1 },
              { timeThreshold: 30, points: 3 },
              { timeThreshold: 60, points: 5 },
            ],
          };
          
          settingsWithDefaults.followupEmail = {
            enabled: data.data.followupEmail?.enabled ?? true,
            delayMinutes: data.data.followupEmail?.delayMinutes ?? 15,
            subject: data.data.followupEmail?.subject || '資料のご確認ありがとうございました - さらに詳しくご説明いたします',
          };
        }
        
        setSettings(settingsWithDefaults);
        setInitialSettings(settingsWithDefaults);
        
        toast({
          title: '設定を読み込みました',
          description: '設定データの取得が完了しました',
        });
      }
    } catch (error) {
      console.error('設定取得エラー:', error);
      toast({
        title: 'エラー',
        description: `設定の取得に失敗しました: ${error instanceof Error ? error.message : 'Unknown error'}`,
        variant: 'destructive',
      });
      
      // エラー時はデフォルト値で初期化
      const defaultSettings: AppSettings = {
        account: {
          fullName: '',
          email: '',
          companyName: '',
          currentPassword: '',
          newPassword: '',
          confirmPassword: '',
        },
      };
      
      setSettings(defaultSettings);
      setInitialSettings(defaultSettings);
    } finally {
      setIsLoading(false);
    }
  };

  const saveSettings = async (settingsToSave: AppSettings) => {
    const response = await fetch('/api/settings', {
      method: 'PUT',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getCookieValue('token')}`,
      },
      body: JSON.stringify(settingsToSave),
    });

    if (!response.ok) {
      const errorData = await response.json();
      console.error('APIエラーレスポンス:', errorData);
      
      // パスワードエラーの場合は詳細メッセージを表示
      if (errorData.error?.code === 'PASSWORD_ERROR') {
        throw new Error(errorData.error.message);
      }
      
      // バリデーションエラーの場合
      if (errorData.error?.code === 'VALIDATION_ERROR') {
        throw new Error('入力内容に誤りがあります。各フィールドを確認してください。');
      }
      
      // その他のエラー
      throw new Error(errorData.error?.message || '設定の保存に失敗しました');
    }

    return response.json();
  };

  // Cookieから値を取得するヘルパー関数
  const getCookieValue = (name: string) => {
    if (typeof document === 'undefined') return '';
    const cookies = document.cookie.split(';');
    for (let cookie of cookies) {
      const [key, value] = cookie.trim().split('=');
      if (key === name) {
        return decodeURIComponent(value);
      }
    }
    return '';
  };

  // CSRFトークンを取得
  const getCsrfToken = () => {
    const cookieValue = document.cookie
      .split('; ')
      .find(row => row.startsWith('XSRF-TOKEN='))
      ?.split('=')[1];
    return cookieValue ? decodeURIComponent(cookieValue) : '';
  };

  // 初期データ読み込み
  useEffect(() => {
    fetchSettings();
  }, []);

  // 設定更新用のヘルパー関数
  const updateGeneralSettings = (updates: Partial<GeneralSettings>) => {
    setSettings(prev => ({
      ...prev,
      general: { ...prev.general, ...updates }
    }));
    setHasUnsavedChanges(true);
  };

  const updateSurveySettings = (updates: Partial<SurveySettings>) => {
    setSettings(prev => ({
      ...prev,
      survey: { ...prev.survey, ...updates }
    }));
    setHasUnsavedChanges(true);
  };

  const updateScoringSettings = (updates: Partial<ScoringSettings>) => {
    setSettings(prev => ({
      ...prev,
      scoring: { ...prev.scoring, ...updates }
    }));
    setHasUnsavedChanges(true);
  };

  const updateFollowupEmailSettings = (updates: Partial<FollowupEmailSettings>) => {
    setSettings(prev => ({
      ...prev,
      followupEmail: { ...prev.followupEmail, ...updates }
    }));
    setHasUnsavedChanges(true);
  };

  // パスワード強度チェック関数
  const checkPasswordStrength = (password: string) => {
    const feedback: string[] = [];
    let score = 0;

    if (password.length >= 8) {
      score += 1;
    } else {
      feedback.push('8文字以上にしてください');
    }

    if (/[A-Z]/.test(password)) {
      score += 1;
    } else {
      feedback.push('大文字を含めてください');
    }

    if (/[a-z]/.test(password)) {
      score += 1;
    } else {
      feedback.push('小文字を含めてください');
    }

    if (/[0-9]/.test(password)) {
      score += 1;
    } else {
      feedback.push('数字を含めてください');
    }

    if (/[^A-Za-z0-9]/.test(password)) {
      score += 1;
    } else {
      feedback.push('記号を含めてください');
    }

    const isValid = score >= 4;
    return { score, feedback, isValid };
  };

  const updateAccountSettings = (updates: Partial<AccountSettings>) => {
    setSettings((prev: AppSettings) => ({
      ...prev,
      account: { ...prev.account, ...updates }
    }));
    setHasUnsavedChanges(true);

    // パスワード強度チェック
    if (updates.newPassword !== undefined) {
      const strength = checkPasswordStrength(updates.newPassword);
      setPasswordStrength(strength);
    }
  };

  // パスワード表示切り替え関数
  const togglePasswordVisibility = (field: 'current' | 'new' | 'confirm') => {
    setShowPasswords(prev => ({
      ...prev,
      [field]: !prev[field]
    }));
  };

  // アンケート選択肢の操作
  const updateSurveyOption = (id: number, updates: Partial<SurveyOption>) => {
    const updatedOptions = settings.survey.options.map(option => 
      option.id === id ? { ...option, ...updates } : option
    );
    updateSurveySettings({ options: updatedOptions });
  };

  const addSurveyOption = () => {
    const maxId = Math.max(...settings.survey.options.map(o => o.id), 0);
    const maxScore = Math.max(...settings.survey.options.map(o => o.score), 0);
    const newId = maxId + 1;
    const newOption = { 
      id: newId, 
      label: '新しい選択肢',
      score: Math.max(maxScore - 25, 0) // 前の最高スコアから25引いた値、最低0
    };
    updateSurveySettings({ 
      options: [...settings.survey.options, newOption] 
    });
  };

  const removeSurveyOption = (id: number) => {
    if (settings.survey.options.length <= 2) {
      toast({
        title: 'エラー',
        description: '選択肢は最低2つ必要です',
        variant: 'destructive',
      });
      return;
    }
    const filteredOptions = settings.survey.options.filter(option => option.id !== id);
    updateSurveySettings({ options: filteredOptions });
  };

  const resetSurveyToDefault = () => {
    updateSurveySettings({
      title: '資料をご覧になる前に',
      description: '現在の興味度をお聞かせください',
      options: [
        { id: 1, label: '非常に興味がある', score: 100 },
        { id: 2, label: 'やや興味がある', score: 75 },
        { id: 3, label: '詳しい情報が必要', score: 50 },
        { id: 4, label: '興味なし', score: 0 },
      ],
    });
  };

  // スコアリング層の操作
  const addScoringTier = () => {
    const newTier = { timeThreshold: 120, points: 8 };
    updateScoringSettings({ 
      tiers: [...settings.scoring.tiers, newTier] 
    });
  };

  const updateScoringTier = (index: number, updates: Partial<ScoringTier>) => {
    const updatedTiers = settings.scoring.tiers.map((tier, i) => 
      i === index ? { ...tier, ...updates } : tier
    );
    updateScoringSettings({ tiers: updatedTiers });
  };

  const removeScoringTier = (index: number) => {
    if (settings.scoring.tiers.length <= 1) {
      toast({
        title: 'エラー',
        description: 'スコアリング層は最低1つ必要です',
        variant: 'destructive',
      });
      return;
    }
    const filteredTiers = settings.scoring.tiers.filter((_, i) => i !== index);
    updateScoringSettings({ tiers: filteredTiers });
  };

  // バリデーション関数
  const validateSettings = () => {
    const errors: Record<string, string> = {};

    // アカウント設定のバリデーション
    if (!settings.account.fullName?.trim()) {
      errors['account.fullName'] = '氏名は必須です';
    }

    if (!settings.account.email?.trim()) {
      errors['account.email'] = 'メールアドレスは必須です';
    }

    if (!settings.account.companyName?.trim()) {
      errors['account.companyName'] = '会社名は必須です';
    }

    // パスワード関連のバリデーション
    if (settings.account.newPassword && settings.account.newPassword !== settings.account.confirmPassword) {
      errors['account.confirmPassword'] = 'パスワードが一致しません';
    }

    if (settings.account.newPassword && settings.account.newPassword.length < 8) {
      errors['account.newPassword'] = 'パスワードは8文字以上で入力してください';
    }

    // 管理者のみ：システム設定のバリデーション
    if (isAdmin && settings.general) {
      if (settings.general.defaultExpiration < 0) {
        errors['general.defaultExpiration'] = '有効期限は0以上の値を入力してください';
      }

      if (settings.general.defaultExpiration > 365) {
        errors['general.defaultExpiration'] = '有効期限は365日以下にしてください';
      }
    }

    // 管理者のみ：アンケート設定のバリデーション
    if (isAdmin && settings.survey) {
      if (!settings.survey.title?.trim()) {
        errors['survey.title'] = 'アンケートタイトルは必須です';
      }

      if (!settings.survey.description?.trim()) {
        errors['survey.description'] = 'アンケート説明は必須です';
      }

      if (settings.survey.options.length < 2) {
        errors['survey.options'] = '選択肢は最低2つ必要です';
      }
    }

    // 管理者のみ：スコアリング設定のバリデーション
    if (isAdmin && settings.scoring) {
      if (settings.scoring.timeThreshold < 0) {
        errors['scoring.timeThreshold'] = '最小閲覧時間は0以上の値を入力してください';
      }

      if (settings.scoring.completionBonus < 0) {
        errors['scoring.completionBonus'] = '完了ボーナスは0以上の値を入力してください';
      }

      // スコアリング層のバリデーション
      settings.scoring.tiers.forEach((tier, index) => {
        if (tier.timeThreshold < 0) {
          errors[`scoring.tiers.${index}.timeThreshold`] = '時間は0以上の値を入力してください';
        }
        if (tier.points < 0) {
          errors[`scoring.tiers.${index}.points`] = 'ポイントは0以上の値を入力してください';
        }
      });
    }

    setValidationErrors(errors);
    return Object.keys(errors).length === 0;
  };

  // リアルタイムバリデーション
  useEffect(() => {
    if (hasUnsavedChanges) {
      validateSettings();
    }
  }, [settings, hasUnsavedChanges]);

  // 設定保存関数
  const handleSaveSettings = async () => {
    // 保存前にバリデーションチェック
    const isValid = validateSettings();
    if (!isValid) {
      toast({
        title: 'バリデーションエラー',
        description: '入力内容に誤りがあります。エラーメッセージを確認してください。',
        variant: 'destructive',
      });
      return;
    }

    setIsSaving(true);
    
    try {
      // 送信するデータをコンソールに出力
      console.log('設定保存開始:', {
        settings,
        account: settings.account,
        hasPassword: !!settings.account.newPassword,
        passwordLength: settings.account.newPassword?.length || 0
      });

      await saveSettings(settings);
      
      setInitialSettings(settings);
      setHasUnsavedChanges(false);
      
      toast({
        title: '設定を保存しました',
        description: '変更が正常に保存されました',
      });
    } catch (error) {
      console.error('保存エラー:', error);
      toast({
        title: 'エラー',
        description: error instanceof Error ? error.message : '設定の保存に失敗しました',
        variant: 'destructive',
      });
    } finally {
      setIsSaving(false);
    }
  };

  // 未保存変更の警告
  useEffect(() => {
    const handleBeforeUnload = (e: BeforeUnloadEvent) => {
      if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = '';
      }
    };

    window.addEventListener('beforeunload', handleBeforeUnload);
    return () => window.removeEventListener('beforeunload', handleBeforeUnload);
  }, [hasUnsavedChanges]);

  // ローディング状態の表示
  if (isLoading) {
    return (
      <AdminLayout>
        <div className="flex-1 flex items-center justify-center p-8">
          <div className="flex items-center gap-4">
            <Loader2 className="h-8 w-8 animate-spin" />
            <span className="text-lg">設定を読み込み中...</span>
          </div>
        </div>
      </AdminLayout>
    );
  }

  return (
    <AdminLayout>
      <div className="flex-1 space-y-4 p-8">
        <div className="flex items-center justify-between">
          <h2 className="text-3xl font-bold tracking-tight">設定</h2>
          <div className="flex items-center gap-4">
            {hasUnsavedChanges && (
              <div className="flex items-center gap-2 text-amber-600">
                <AlertCircle className="h-4 w-4" />
                <span className="text-sm">未保存の変更があります</span>
              </div>
            )}
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
        </div>

        {hasUnsavedChanges && (
          <Alert>
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>
              未保存の変更があります。ページを離れる前に保存してください。
            </AlertDescription>
          </Alert>
        )}
        
        <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-4">
          <TabsList>
            <TabsTrigger value="account">
              アカウント
              {hasUnsavedChanges && activeTab === 'account' && (
                <span className="ml-1 h-2 w-2 rounded-full bg-amber-500" />
              )}
            </TabsTrigger>
            {isAdmin && (
              <>
                <TabsTrigger value="general">
                  一般
                  {hasUnsavedChanges && activeTab === 'general' && (
                    <span className="ml-1 h-2 w-2 rounded-full bg-amber-500" />
                  )}
                </TabsTrigger>
                <TabsTrigger value="survey">
                  アンケート設定
                  {hasUnsavedChanges && activeTab === 'survey' && (
                    <span className="ml-1 h-2 w-2 rounded-full bg-amber-500" />
                  )}
                </TabsTrigger>
                <TabsTrigger value="scoring">
                  スコアリング
                  {hasUnsavedChanges && activeTab === 'scoring' && (
                    <span className="ml-1 h-2 w-2 rounded-full bg-amber-500" />
                  )}
                </TabsTrigger>
                <TabsTrigger value="followupEmail">
                  フォローアップメール
                  {hasUnsavedChanges && activeTab === 'followupEmail' && (
                    <span className="ml-1 h-2 w-2 rounded-full bg-amber-500" />
                  )}
                </TabsTrigger>
              </>
            )}
          </TabsList>
          
          {/* Account Settings */}
          <TabsContent value="account" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>アカウント設定</CardTitle>
                <CardDescription>
                  個人情報とパスワードの設定を行います
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="space-y-2">
                  <Label htmlFor="fullName">氏名</Label>
                  <Input 
                    id="fullName" 
                    value={settings.account.fullName}
                    onChange={(e) => updateAccountSettings({ fullName: e.target.value })}
                    className={validationErrors['account.fullName'] ? 'border-red-500' : ''}
                  />
                  {validationErrors['account.fullName'] && (
                    <p className="text-sm text-red-500">{validationErrors['account.fullName']}</p>
                  )}
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="email">メールアドレス</Label>
                  <Input 
                    id="email" 
                    type="email"
                    value={settings.account.email}
                    onChange={(e) => updateAccountSettings({ email: e.target.value })}
                    className={validationErrors['account.email'] ? 'border-red-500' : ''}
                  />
                  {validationErrors['account.email'] && (
                    <p className="text-sm text-red-500">{validationErrors['account.email']}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="companyName">会社名</Label>
                  <Input 
                    id="companyName" 
                    value={settings.account.companyName}
                    onChange={(e) => updateAccountSettings({ companyName: e.target.value })}
                    className={validationErrors['account.companyName'] ? 'border-red-500' : ''}
                  />
                  {validationErrors['account.companyName'] && (
                    <p className="text-sm text-red-500">{validationErrors['account.companyName']}</p>
                  )}
                </div>
                
                <Separator />
                
                <div className="space-y-4">
                  <h3 className="text-lg font-medium">パスワード変更</h3>
                  <p className="text-sm text-muted-foreground">
                    パスワードを変更する場合は、すべてのフィールドを入力してください
                  </p>
                  
                  <div className="space-y-2">
                    <Label htmlFor="currentPassword">現在のパスワード</Label>
                    <div className="relative">
                      <Input 
                        id="currentPassword" 
                        type={showPasswords.current ? "text" : "password"}
                        value={settings.account.currentPassword}
                        onChange={(e) => updateAccountSettings({ currentPassword: e.target.value })}
                        className={validationErrors['account.currentPassword'] ? 'border-red-500' : ''}
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                        onClick={() => togglePasswordVisibility('current')}
                      >
                        {showPasswords.current ? (
                          <EyeOff className="h-4 w-4" />
                        ) : (
                          <Eye className="h-4 w-4" />
                        )}
                      </Button>
                    </div>
                    {validationErrors['account.currentPassword'] && (
                      <p className="text-sm text-red-500">{validationErrors['account.currentPassword']}</p>
                    )}
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="newPassword">新しいパスワード</Label>
                    <div className="relative">
                      <Input 
                        id="newPassword" 
                        type={showPasswords.new ? "text" : "password"}
                        value={settings.account.newPassword}
                        onChange={(e) => {
                          updateAccountSettings({ newPassword: e.target.value });
                          checkPasswordStrength(e.target.value);
                        }}
                        className={validationErrors['account.newPassword'] ? 'border-red-500' : ''}
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                        onClick={() => togglePasswordVisibility('new')}
                      >
                        {showPasswords.new ? (
                          <EyeOff className="h-4 w-4" />
                        ) : (
                          <Eye className="h-4 w-4" />
                        )}
                      </Button>
                    </div>
                    {validationErrors['account.newPassword'] && (
                      <p className="text-sm text-red-500">{validationErrors['account.newPassword']}</p>
                    )}
                    {settings.account.newPassword && (
                      <div className="space-y-2">
                        <div className="flex items-center gap-2">
                          <div className="flex-1 bg-gray-200 rounded-full h-2">
                            <div 
                              className={`h-2 rounded-full transition-all ${
                                passwordStrength.score < 2 ? 'bg-red-500' :
                                passwordStrength.score < 4 ? 'bg-yellow-500' : 'bg-green-500'
                              }`}
                              style={{ width: `${(passwordStrength.score / 4) * 100}%` }}
                            />
                          </div>
                          <span className="text-sm text-muted-foreground">
                            {passwordStrength.score < 2 ? '弱い' :
                             passwordStrength.score < 4 ? '普通' : '強い'}
                          </span>
                        </div>
                        {passwordStrength.feedback.length > 0 && (
                          <ul className="text-sm text-muted-foreground space-y-1">
                            {passwordStrength.feedback.map((feedback, index) => (
                              <li key={index}>• {feedback}</li>
                            ))}
                          </ul>
                        )}
                      </div>
                    )}
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="confirmPassword">パスワード確認</Label>
                    <div className="relative">
                      <Input 
                        id="confirmPassword" 
                        type={showPasswords.confirm ? "text" : "password"}
                        value={settings.account.confirmPassword}
                        onChange={(e) => updateAccountSettings({ confirmPassword: e.target.value })}
                        className={validationErrors['account.confirmPassword'] ? 'border-red-500' : ''}
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                        onClick={() => togglePasswordVisibility('confirm')}
                      >
                        {showPasswords.confirm ? (
                          <EyeOff className="h-4 w-4" />
                        ) : (
                          <Eye className="h-4 w-4" />
                        )}
                      </Button>
                    </div>
                    {validationErrors['account.confirmPassword'] && (
                      <p className="text-sm text-red-500">{validationErrors['account.confirmPassword']}</p>
                    )}
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
          
          {/* General Settings - Admin Only */}
          {isAdmin && settings.general && (
            <TabsContent value="general" className="space-y-4">
              <Card>
                <CardHeader>
                  <CardTitle>一般設定</CardTitle>
                  <CardDescription>
                    アプリケーションの基本設定を行います（管理者のみ）
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                  <div className="space-y-2">
                    <Label htmlFor="defaultExpiration">リンク有効期限（日数）</Label>
                    <Input 
                      id="defaultExpiration" 
                      type="number" 
                      value={settings.general.defaultExpiration}
                      onChange={(e) => updateGeneralSettings({ defaultExpiration: parseInt(e.target.value) || 0 })}
                      className={validationErrors['general.defaultExpiration'] ? 'border-red-500' : ''}
                    />
                    {validationErrors['general.defaultExpiration'] && (
                      <p className="text-sm text-red-500">{validationErrors['general.defaultExpiration']}</p>
                    )}
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
                      <Switch 
                        id="trackPageViews" 
                        checked={settings.general.trackPageViews}
                        onCheckedChange={(checked) => updateGeneralSettings({ trackPageViews: checked })}
                      />
                  </div>
                  
                  <div className="flex items-center justify-between">
                    <div className="space-y-0.5">
                      <Label htmlFor="requireSurvey">閲覧前のアンケート要求</Label>
                      <p className="text-sm text-muted-foreground">
                        資料閲覧前に興味度を確認
                      </p>
                    </div>
                      <Switch 
                        id="requireSurvey" 
                        checked={settings.general.requireSurvey}
                        onCheckedChange={(checked) => updateGeneralSettings({ requireSurvey: checked })}
                      />
                  </div>
                  
                  <div className="flex items-center justify-between">
                    <div className="space-y-0.5">
                      <Label htmlFor="showBookingOption">ミーティング予約オプションを表示</Label>
                      <p className="text-sm text-muted-foreground">
                        資料閲覧完了後に予約プロンプトを表示
                      </p>
                    </div>
                      <Switch 
                        id="showBookingOption" 
                        checked={settings.general.showBookingOption}
                        onCheckedChange={(checked) => updateGeneralSettings({ showBookingOption: checked })}
                      />
                    </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
          )}
          
          {/* Survey Options - Admin Only */}
          {isAdmin && settings.survey && (
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
                    <Input 
                      id="surveyTitle" 
                      value={settings.survey.title}
                      onChange={(e) => updateSurveySettings({ title: e.target.value })}
                      placeholder="例: 資料への興味度をお聞かせください"
                    />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="surveyDescription">アンケート説明</Label>
                    <Input 
                      id="surveyDescription" 
                      value={settings.survey.description}
                      onChange={(e) => updateSurveySettings({ description: e.target.value })}
                      placeholder="例: より良い資料をご提供するため、ご協力をお願いします"
                    />
                </div>
                
                <Separator />
                
                <div className="space-y-4">
                  <h3 className="text-lg font-medium">アンケート選択肢</h3>
                  <p className="text-sm text-muted-foreground">
                    興味度アンケートの選択肢をカスタマイズ
                  </p>
                  
                    {settings.survey.options.map((option, index) => (
                    <div key={option.id} className="flex items-center gap-4 p-4 border rounded-lg">
                      <div className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center font-medium text-sm">
                          {index + 1}
                      </div>
                      <div className="grid grid-cols-2 gap-4 flex-1">
                        <div>
                          <Label className="text-sm">選択肢テキスト</Label>
                          <Input 
                            value={option.label} 
                            onChange={(e) => updateSurveyOption(option.id, { label: e.target.value })}
                            placeholder={`選択肢 ${index + 1}`}
                          />
                        </div>
                        <div>
                          <Label className="text-sm">スコア（0-100）</Label>
                          <Input 
                            type="number"
                            min="0"
                            max="100"
                            value={option.score} 
                            onChange={(e) => updateSurveyOption(option.id, { score: parseInt(e.target.value) || 0 })}
                            placeholder="スコア値"
                          />
                        </div>
                      </div>
                        {settings.survey.options.length > 2 && (
                          <Button 
                            variant="outline" 
                            size="icon"
                            onClick={() => removeSurveyOption(option.id)}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        )}
                    </div>
                  ))}
                  
                  <div className="flex gap-2">
                      <Button variant="outline" className="flex-1" onClick={addSurveyOption}>
                        <Plus className="h-4 w-4 mr-2" />
                        選択肢を追加
                      </Button>
                      <Button variant="outline" className="flex-1" onClick={resetSurveyToDefault}>
                        デフォルトに戻す
                      </Button>
                    </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
          )}
          
          {/* Scoring Settings */}
          {isAdmin && settings.scoring && (
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
                    <Input 
                      id="timeThreshold" 
                      type="number" 
                      value={settings.scoring.timeThreshold}
                      onChange={(e) => updateScoringSettings({ timeThreshold: parseInt(e.target.value) || 0 })}
                    />
                  <p className="text-sm text-muted-foreground">
                    ページが閲覧されたとカウントする最小時間（秒）
                  </p>
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="completionBonus">完了ボーナスポイント</Label>
                    <Input 
                      id="completionBonus" 
                      type="number" 
                      value={settings.scoring.completionBonus}
                      onChange={(e) => updateScoringSettings({ completionBonus: parseInt(e.target.value) || 0 })}
                    />
                  <p className="text-sm text-muted-foreground">
                    資料を最後まで閲覧した際に付与される追加ポイント
                  </p>
                </div>
                
                <Separator />
                
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                      <div>
                  <h3 className="text-lg font-medium">時間ベースのスコアリング</h3>
                  <p className="text-sm text-muted-foreground">
                    各ページの閲覧時間に応じて付与されるポイント
                  </p>
                    </div>
                      <Button variant="outline" size="sm" onClick={addScoringTier}>
                        <Plus className="h-4 w-4 mr-2" />
                        層を追加
                      </Button>
                  </div>
                  
                    {settings.scoring.tiers.map((tier, index) => (
                      <div key={index} className="flex items-center gap-4 p-4 border rounded-lg">
                        <div className="grid grid-cols-2 gap-4 flex-1">
                    <div>
                            <Label>レベル{index + 1}（秒）</Label>
                            <Input 
                              type="number" 
                              value={tier.timeThreshold}
                              onChange={(e) => updateScoringTier(index, { timeThreshold: parseInt(e.target.value) || 0 })}
                            />
                    </div>
                    <div>
                            <Label>ポイント</Label>
                            <Input 
                              type="number" 
                              value={tier.points}
                              onChange={(e) => updateScoringTier(index, { points: parseInt(e.target.value) || 0 })}
                            />
                    </div>
                  </div>
                        {settings.scoring.tiers.length > 1 && (
                          <Button 
                            variant="outline" 
                            size="icon"
                            onClick={() => removeScoringTier(index)}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        )}
                    </div>
                    ))}
                </div>
              </CardContent>
            </Card>
          </TabsContent>
          )}
          
          {/* Followup Email Settings */}
          {isAdmin && settings.followupEmail && (
          <TabsContent value="followupEmail" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>フォローアップメール設定</CardTitle>
                <CardDescription>
                  PDF閲覧後のフォローアップメール機能を設定
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="flex items-center justify-between">
                  <div className="space-y-0.5">
                    <Label htmlFor="followupEnabled">フォローアップメール機能</Label>
                    <p className="text-sm text-muted-foreground">
                      PDF最終ページ閲覧後、TimeRex予約がない場合に自動メール送信
                    </p>
                  </div>
                  <Switch 
                    id="followupEnabled" 
                    checked={settings.followupEmail.enabled}
                    onCheckedChange={(checked) => updateFollowupEmailSettings({ enabled: checked })}
                  />
                </div>
                
                <Separator />
                
                <div className="space-y-2">
                  <Label htmlFor="delayMinutes">送信遅延時間（分）</Label>
                  <Input 
                    id="delayMinutes" 
                    type="number" 
                    min="1"
                    max="1440"
                    value={settings.followupEmail.delayMinutes}
                    onChange={(e) => updateFollowupEmailSettings({ delayMinutes: parseInt(e.target.value) || 15 })}
                  />
                  <p className="text-sm text-muted-foreground">
                    PDF最終ページ閲覧後、何分後にメールを送信するか（1-1440分）
                  </p>
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="emailSubject">メール件名</Label>
                  <Input 
                    id="emailSubject" 
                    value={settings.followupEmail.subject}
                    onChange={(e) => updateFollowupEmailSettings({ subject: e.target.value })}
                    placeholder="例: 資料のご確認ありがとうございました"
                  />
                  <p className="text-sm text-muted-foreground">
                    フォローアップメールの件名
                  </p>
                </div>
                
                <Separator />
                
                <div className="space-y-4">
                  <h3 className="text-lg font-medium">動作条件</h3>
                  <div className="space-y-2 text-sm text-muted-foreground">
                    <p>• PDF最終ページに到達した場合</p>
                    <p>• 設定した遅延時間が経過した場合</p>
                    <p>• TimeRex予約が完了していない場合</p>
                    <p>• 会社のメールアドレスが設定されている場合</p>
                  </div>
                </div>
                
                <Alert>
                  <AlertCircle className="h-4 w-4" />
                  <AlertDescription>
                    フォローアップメールは1つのPDFにつき1回のみ送信されます。
                    「後で検討する」を選択した場合、タイマーは停止されます。
                  </AlertDescription>
                </Alert>
              </CardContent>
            </Card>
          </TabsContent>
          )}
        </Tabs>
      </div>
    </AdminLayout>
  );
}