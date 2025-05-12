import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { useRouter } from 'next/navigation';
import { useToast } from '@/hooks/use-toast';
import { AuthProvider, useAuth } from '@/app/contexts/AuthContext';
import LoginPage from '@/app/admin/login/page';

// モックの設定
jest.mock('next/navigation', () => ({
  useRouter: jest.fn(),
}));

jest.mock('@/hooks/use-toast', () => ({
  useToast: jest.fn(),
}));

// テスト用のラッパーコンポーネント
function TestWrapper({ children }: { children: React.ReactNode }) {
  return <AuthProvider>{children}</AuthProvider>;
}

describe('認証機能のテスト', () => {
  const mockRouter = {
    push: jest.fn(),
  };

  const mockToast = {
    toast: jest.fn(),
  };

  beforeEach(() => {
    (useRouter as jest.Mock).mockReturnValue(mockRouter);
    (useToast as jest.Mock).mockReturnValue(mockToast);
    jest.clearAllMocks();
  });

  describe('ログインページ', () => {
    it('ログインフォームが正しく表示される', () => {
      render(
        <AuthProvider>
          <LoginPage />
        </AuthProvider>
      );

      expect(screen.getByText('ログイン')).toBeInTheDocument();
      expect(screen.getByPlaceholderText('you@example.com')).toBeInTheDocument();
      expect(screen.getByPlaceholderText('••••••••')).toBeInTheDocument();
    });

    it('バリデーションエラーが表示される', async () => {
      render(
        <AuthProvider>
          <LoginPage />
        </AuthProvider>
      );

      const loginButton = screen.getByText('ログイン');
      fireEvent.click(loginButton);

      await waitFor(() => {
        expect(screen.getByText('有効なメールアドレスを入力してください')).toBeInTheDocument();
        expect(screen.getByText('パスワードは8文字以上で入力してください')).toBeInTheDocument();
      });
    });

    it('ログイン成功時にダッシュボードにリダイレクトされる', async () => {
      global.fetch = jest.fn().mockImplementationOnce(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve({ token: 'test-token', user: { id: 1, email: 'test@example.com' } }),
        })
      );

      render(
        <AuthProvider>
          <LoginPage />
        </AuthProvider>
      );

      const emailInput = screen.getByPlaceholderText('you@example.com');
      const passwordInput = screen.getByPlaceholderText('••••••••');
      const loginButton = screen.getByText('ログイン');

      fireEvent.change(emailInput, { target: { value: 'test@example.com' } });
      fireEvent.change(passwordInput, { target: { value: 'password123' } });
      fireEvent.click(loginButton);

      await waitFor(() => {
        expect(mockRouter.push).toHaveBeenCalledWith('/admin/dashboard');
      });
    });

    it('ログイン失敗時にエラーメッセージが表示される', async () => {
      global.fetch = jest.fn().mockImplementationOnce(() =>
        Promise.resolve({
          ok: false,
          json: () => Promise.resolve({ message: 'メールアドレスとパスワードを確認してください' }),
        })
      );

      render(
        <AuthProvider>
          <LoginPage />
        </AuthProvider>
      );

      const emailInput = screen.getByPlaceholderText('you@example.com');
      const passwordInput = screen.getByPlaceholderText('••••••••');
      const loginButton = screen.getByText('ログイン');

      fireEvent.change(emailInput, { target: { value: 'test@example.com' } });
      fireEvent.change(passwordInput, { target: { value: 'wrong-password' } });
      fireEvent.click(loginButton);

      await waitFor(() => {
        expect(mockToast.toast).toHaveBeenCalledWith({
          title: 'ログインに失敗しました',
          description: 'メールアドレスとパスワードを確認してください',
          variant: 'destructive',
        });
      });
    });
  });

  describe('ログアウト機能', () => {
    it('ログアウト成功時にログインページにリダイレクトされる', async () => {
      const TestComponent = () => {
        const { logout } = useAuth();
        return (
          <button onClick={logout}>
            ログアウト
          </button>
        );
      };

      render(
        <AuthProvider>
          <TestComponent />
        </AuthProvider>
      );

      const logoutButton = screen.getByText('ログアウト');
      fireEvent.click(logoutButton);

      await waitFor(() => {
        expect(mockToast.toast).toHaveBeenCalledWith({
          title: 'ログアウトしました',
          description: 'またのご利用をお待ちしております',
        });
        expect(mockRouter.push).toHaveBeenCalledWith('/admin/login');
      });
    });
  });
}); 