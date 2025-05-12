import '@testing-library/jest-dom';
import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { useRouter } from 'next/navigation';
import { useToast } from '@/hooks/use-toast';
import { AuthProvider, useAuth } from '@/app/contexts/AuthContext';
import LoginPage from '@/app/admin/login/page';
import Cookies from 'js-cookie';

// モックの設定
jest.mock('next/navigation', () => ({
  useRouter: jest.fn(),
}));

jest.mock('js-cookie', () => ({
  get: jest.fn(),
  set: jest.fn(),
  remove: jest.fn(),
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

      expect(screen.getByRole('heading', { name: 'ログイン' })).toBeInTheDocument();
      expect(screen.getByPlaceholderText('you@example.com')).toBeInTheDocument();
      expect(screen.getByPlaceholderText('••••••••')).toBeInTheDocument();
    });

    it('バリデーションエラーが表示される', async () => {
      render(
        <AuthProvider>
          <LoginPage />
        </AuthProvider>
      );

      const loginButton = screen.getByRole('button', { name: 'ログイン' });
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
      const loginButton = screen.getByRole('button', { name: 'ログイン' });

      fireEvent.change(emailInput, { target: { value: 'test@example.com' } });
      fireEvent.change(passwordInput, { target: { value: 'password123' } });
      fireEvent.click(loginButton);

      await waitFor(() => {
        expect(mockRouter.push).toHaveBeenCalledWith('/admin/dashboard');
        expect(Cookies.set).toHaveBeenCalledWith('token', 'test-token', { expires: 7 });
        expect(mockToast.toast).toHaveBeenCalledWith({
          title: 'ログインしました',
          description: 'ContActへようこそ',
        });
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
      const loginButton = screen.getByRole('button', { name: 'ログイン' });

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

  describe('AuthContext', () => {
    it('初期状態ではユーザーがnullである', () => {
      const TestComponent = () => {
        const { user } = useAuth();
        return <div>{user ? 'ログイン中' : '未ログイン'}</div>;
      };

      render(
        <AuthProvider>
          <TestComponent />
        </AuthProvider>
      );

      expect(screen.getByText('未ログイン')).toBeInTheDocument();
    });

    it('ログイン後にユーザー情報が設定される', async () => {
      const TestComponent = () => {
        const { user, login } = useAuth();
        return (
          <div>
            <div>{user ? `ログイン中: ${user.email}` : '未ログイン'}</div>
            <button onClick={() => login('test@example.com', 'password123')}>ログイン</button>
          </div>
        );
      };

      global.fetch = jest.fn().mockImplementationOnce(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve({ token: 'test-token', user: { id: 1, email: 'test@example.com' } }),
        })
      );

      render(
        <AuthProvider>
          <TestComponent />
        </AuthProvider>
      );

      expect(screen.getByText('未ログイン')).toBeInTheDocument();

      fireEvent.click(screen.getByText('ログイン'));

      await waitFor(() => {
        expect(screen.getByText('ログイン中: test@example.com')).toBeInTheDocument();
      });
    });

    it('ログアウト後にユーザー情報がクリアされる', async () => {
      const TestComponent = () => {
        const { user, login, logout } = useAuth();
        return (
          <div>
            <div>{user ? `ログイン中: ${user.email}` : '未ログイン'}</div>
            <button onClick={() => login('test@example.com', 'password123')}>ログイン</button>
            <button onClick={() => logout()}>ログアウト</button>
          </div>
        );
      };

      global.fetch = jest.fn()
        .mockImplementationOnce(() =>
          Promise.resolve({
            ok: true,
            json: () => Promise.resolve({ token: 'test-token', user: { id: 1, email: 'test@example.com' } }),
          })
        )
        .mockImplementationOnce(() =>
          Promise.resolve({
            ok: true,
            json: () => Promise.resolve({ message: 'ログアウトしました' }),
          })
        );

      render(
        <AuthProvider>
          <TestComponent />
        </AuthProvider>
      );

      fireEvent.click(screen.getByText('ログイン'));

      await waitFor(() => {
        expect(screen.getByText('ログイン中: test@example.com')).toBeInTheDocument();
      });

      fireEvent.click(screen.getByText('ログアウト'));

      await waitFor(() => {
        expect(screen.getByText('未ログイン')).toBeInTheDocument();
        expect(Cookies.remove).toHaveBeenCalledWith('token');
      });
    });
  });
}); 