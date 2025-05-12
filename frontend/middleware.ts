import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

export function middleware(request: NextRequest) {
  const token = request.cookies.get('token')?.value;
  const isAuthPage = request.nextUrl.pathname.startsWith('/admin/login') || 
                    request.nextUrl.pathname.startsWith('/admin/register');
  const isAdminPage = request.nextUrl.pathname.startsWith('/admin');

  // 認証ページにアクセスする場合
  if (isAuthPage) {
    // すでにログインしている場合はダッシュボードにリダイレクト
    if (token) {
      return NextResponse.redirect(new URL('/admin/dashboard', request.url));
    }
    return NextResponse.next();
  }

  // 管理者ページにアクセスする場合
  if (isAdminPage) {
    // ログインしていない場合はログインページにリダイレクト
    if (!token) {
      return NextResponse.redirect(new URL('/admin/login', request.url));
    }
    return NextResponse.next();
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    '/admin/:path*',
  ],
}; 