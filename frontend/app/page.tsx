// 'use client'ディレクティブを削除し、サーバーコンポーネントとして動作させる

import Link from 'next/link';
import { ArrowRight, BarChart3, Clock, FileText, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export default function Home() {
  return (
    <div className="flex min-h-screen flex-col bg-white">
      {/* Header */}
      <header className="sticky top-0 z-50 w-full border-b bg-white">
        <div className="container flex h-16 items-center justify-between max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center gap-2 font-bold text-xl">
            <FileText className="h-6 w-6 text-blue-600" />
            <span>ContAct</span>
          </div>
          <nav className="hidden md:flex gap-6">
            <Link href="/" className="text-sm font-medium transition-colors hover:text-primary">
              ホーム
            </Link>
            <Link href="#features" className="text-sm font-medium text-muted-foreground transition-colors hover:text-primary">
              機能
            </Link>
            <Link href="#how-it-works" className="text-sm font-medium text-muted-foreground transition-colors hover:text-primary">
              使い方
            </Link>
          </nav>
          <div className="flex items-center gap-4">
            <Link href="/admin/login">
              <Button variant="outline">ログイン</Button>
            </Link>
            <Link href="/admin/register">
              <Button>無料で始める</Button>
            </Link>
          </div>
        </div>
      </header>

      {/* Hero Section */}
      <section className="py-20 md:py-28 container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col items-center text-center">
          <h1 className="flex flex-col gap-2 text-4xl md:text-6xl font-bold mb-6">
            <span className="block">営業資料の閲覧状況を</span><span className="block text-blue-600">リアルタイムに把握</span>
          </h1>
          <p className="text-xl text-muted-foreground max-w-[800px] mb-10">
            専用リンクで営業資料を共有し、閲覧状況をリアルタイムに把握。<br />
            興味度に応じたタイムリーなフォローアップで商談を成約へ導きます。
          </p>
          <div className="flex flex-col sm:flex-row gap-4">
            <Link href="/admin/register">
              <Button size="lg" className="gap-2">
                無料トライアルを開始 <ArrowRight className="h-4 w-4" />
              </Button>
            </Link>
            <Link href="/view/ba7c162f-4d65-4c16-9921-3ee22e1a5ffa">
              <Button variant="outline" size="lg">
                デモを見る
              </Button>
            </Link>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section id="features" className="py-20 bg-gray-50">
        <div className="container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl font-bold mb-4">主な機能</h2>
            <p className="text-muted-foreground max-w-[600px] mx-auto">
              営業資料の効果を最大化するために必要な機能を全て搭載
            </p>
          </div>
          
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Card>
              <CardHeader>
                <FileText className="h-10 w-10 mb-2 text-blue-600" />
                <CardTitle>安全なPDF共有</CardTitle>
                <CardDescription>
                  専用リンクで安全に資料を共有、有効期限の設定も可能
                </CardDescription>
              </CardHeader>
              <CardContent>
                <p>セキュアな共有リンクで機密資料を管理。アクセス権限はいつでも取り消し可能です。</p>
              </CardContent>
            </Card>
            
            <Card>
              <CardHeader>
                <Clock className="h-10 w-10 mb-2 text-blue-600" />
                <CardTitle>閲覧状況の追跡</CardTitle>
                <CardDescription>
                  どのページをどれだけ閲覧したか、詳細に把握
                </CardDescription>
              </CardHeader>
              <CardContent>
                <p>資料のどの部分に興味を持たれているのかを分析し、より効果的な営業資料作りに活用できます。</p>
              </CardContent>
            </Card>
            
            <Card>
              <CardHeader>
                <BarChart3 className="h-10 w-10 mb-2 text-blue-600" />
                <CardTitle>興味度分析</CardTitle>
                <CardDescription>
                  直感的なダッシュボードで顧客の興味を可視化
                </CardDescription>
              </CardHeader>
              <CardContent>
                <p>閲覧行動をヒートマップやスコアで分析。時系列での興味度の変化も把握できます。</p>
              </CardContent>
            </Card>
            
            <Card>
              <CardHeader>
                <Users className="h-10 w-10 mb-2 text-blue-600" />
                <CardTitle>顧客フィードバック</CardTitle>
                <CardDescription>
                  4段階の簡単なアンケートで興味度を把握
                </CardDescription>
              </CardHeader>
              <CardContent>
                <p>最適なタイミングで表示される簡単なアンケートで、見込み客の購買意欲を直接把握できます。</p>
              </CardContent>
            </Card>
            
            <Card className="md:col-span-2">
              <CardHeader>
                <CardTitle>営業活動を加速する統合ソリューション</CardTitle>
                <CardDescription>
                  資料閲覧から商談予約までをスムーズに実現
                </CardDescription>
              </CardHeader>
              <CardContent>
                <p className="mb-4">ContActは資料分析と即時対応ツールを組み合わせ、営業サイクルを短縮します：</p>
                <ul className="list-disc pl-5 space-y-2">
                  <li>エンゲージメント指標で有望リードを特定</li>
                  <li>興味を示した閲覧者に商談予約を促進</li>
                  <li>エンゲージメントスコアでフォローアップの優先順位付け</li>
                  <li>資料閲覧から成約までの過程を追跡</li>
                </ul>
              </CardContent>
            </Card>
          </div>
        </div>
      </section>

      {/* How It Works */}
      <section id="how-it-works" className="py-20 container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16">
          <h2 className="text-3xl font-bold mb-4">使い方</h2>
          <p className="text-muted-foreground max-w-[600px] mx-auto">
            ContActは営業プロセスにシームレスに統合
          </p>
        </div>
        
        <div className="relative">
          <div className="absolute top-0 bottom-0 left-1/2 border-l border-gray-200 -ml-px hidden md:block"></div>
          
          <div className="space-y-16">
            <div className="relative flex flex-col md:flex-row">
              <div className="md:w-1/2 pb-10 md:pb-0 md:pr-10 flex flex-col items-end">
                <div className="text-right max-w-md">
                  <div className="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 text-blue-600 mb-4">1</div>
                  <h3 className="text-xl font-bold mb-2">営業資料をアップロード</h3>
                  <p className="text-muted-foreground">
                    PDFをセキュアなダッシュボードにアップロード。<br />会社やキャンペーンごとに整理できます。
                  </p>
                </div>
              </div>
              <div className="absolute left-1/2 -translate-x-1/2 flex items-center justify-center z-10 hidden md:flex">
                <div className="w-8 h-8 rounded-full bg-blue-600 border-4 border-white"></div>
              </div>
              <div className="md:w-1/2 md:pl-10">
                {/* Empty space for alignment */}
              </div>
            </div>
            
            <div className="relative flex flex-col md:flex-row">
              <div className="md:w-1/2 pb-10 md:pb-0 md:pr-10">
                {/* Empty space for alignment */}
              </div>
              <div className="absolute left-1/2 -translate-x-1/2 flex items-center justify-center z-10 hidden md:flex">
                <div className="w-8 h-8 rounded-full bg-blue-600 border-4 border-white"></div>
              </div>
              <div className="md:w-1/2 md:pl-10">
                <div className="max-w-md">
                  <div className="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 text-blue-600 mb-4">2</div>
                  <h3 className="text-xl font-bold mb-2">専用リンクを生成</h3>
                  <p className="text-muted-foreground">
                    見込み客ごとに固有の追跡リンクを作成。<br />期間限定オファーには有効期限も設定可能です。
                  </p>
                </div>
              </div>
            </div>
            
            <div className="relative flex flex-col md:flex-row">
              <div className="md:w-1/2 pb-10 md:pb-0 md:pr-10 flex flex-col items-end">
                <div className="text-right max-w-md">
                  <div className="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 text-blue-600 mb-4">3</div>
                  <h3 className="text-xl font-bold mb-2">見込み客と共有</h3>
                  <p className="text-muted-foreground">
                    メール、メッセージ、CRMでリンクを共有。<br />リンクを開くと自動的に追跡を開始します。
                  </p>
                </div>
              </div>
              <div className="absolute left-1/2 -translate-x-1/2 flex items-center justify-center z-10 hidden md:flex">
                <div className="w-8 h-8 rounded-full bg-blue-600 border-4 border-white"></div>
              </div>
              <div className="md:w-1/2 md:pl-10">
                {/* Empty space for alignment */}
              </div>
            </div>
            
            <div className="relative flex flex-col md:flex-row">
              <div className="md:w-1/2 pb-10 md:pb-0 md:pr-10">
                {/* Empty space for alignment */}
              </div>
              <div className="absolute left-1/2 -translate-x-1/2 flex items-center justify-center z-10 hidden md:flex">
                <div className="w-8 h-8 rounded-full bg-blue-600 border-4 border-white"></div>
              </div>
              <div className="md:w-1/2 md:pl-10">
                <div className="max-w-md">
                  <div className="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 text-blue-600 mb-4">4</div>
                  <h3 className="text-xl font-bold mb-2">閲覧状況を把握</h3>
                  <p className="text-muted-foreground">
                    見込み客の資料閲覧状況をリアルタイムで確認。<br />どのページに時間を費やし、いつ読み終えたかが分かります。
                  </p>
                </div>
              </div>
            </div>
            
            <div className="relative flex flex-col md:flex-row">
              <div className="md:w-1/2 pb-10 md:pb-0 md:pr-10 flex flex-col items-end">
                <div className="text-right max-w-md">
                  <div className="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 text-blue-600 mb-4">5</div>
                  <h3 className="text-xl font-bold mb-2">興味のある見込み客を商談へ</h3>
                  <p className="text-muted-foreground">
                    高いエンゲージメントを示した閲覧者に、<br />興味度の確認と直接の商談予約を促します。
                  </p>
                </div>
              </div>
              <div className="absolute left-1/2 -translate-x-1/2 flex items-center justify-center z-10 hidden md:flex">
                <div className="w-8 h-8 rounded-full bg-blue-600 border-4 border-white"></div>
              </div>
              <div className="md:w-1/2 md:pl-10">
                {/* Empty space for alignment */}
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="bg-blue-600 py-16 text-white">
        <div className="container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-3xl font-bold mb-4">営業プロセスを変革する準備はできましたか？</h2>
          <p className="max-w-[600px] mx-auto mb-8">
            今すぐ顧客の興味を把握し、より多くの商談を獲得しましょう。
          </p>
          <Link href="/admin/register">
            <Button size="lg" variant="secondary" className="gap-2">
              無料トライアルを開始 <ArrowRight className="h-4 w-4" />
            </Button>
          </Link>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t py-12">
        <div className="container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex flex-col md:flex-row justify-between items-center">
            <div className="flex items-center gap-2 font-bold text-xl mb-4 md:mb-0">
              <FileText className="h-6 w-6 text-blue-600" />
              <span>ContAct</span>
            </div>
            <div className="flex flex-col md:flex-row gap-4 md:gap-8">
              <Link href="#" className="text-sm text-muted-foreground hover:text-foreground">
                利用規約
              </Link>
              <Link href="#" className="text-sm text-muted-foreground hover:text-foreground">
                プライバシーポリシー
              </Link>
              <Link href="#" className="text-sm text-muted-foreground hover:text-foreground">
                お問い合わせ
              </Link>
            </div>
            <div className="mt-4 md:mt-0">
              <p className="text-sm text-muted-foreground">© 2025 ContAct Inc. All rights reserved.</p>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
}