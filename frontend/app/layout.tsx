import './globals.css';
import type { Metadata } from 'next';
import { Noto_Sans_JP } from 'next/font/google';
import { Toaster } from '@/components/ui/toaster';

// Noto Sans JPフォントの設定
const notoSansJP = Noto_Sans_JP({
  subsets: ['latin'],
  variable: '--font-noto-sans-jp',
  weight: ['400', '500', '700'],
  preload: true,
});

export const metadata: Metadata = {
  title: 'ContAct - PDF閲覧分析ツール',
  description: '営業資料の閲覧状況を分析し、商談機会を最大化',
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="ja">
      <body className={`${notoSansJP.variable} font-sans`}>
        {children}
        <Toaster />
      </body>
    </html>
  );
}