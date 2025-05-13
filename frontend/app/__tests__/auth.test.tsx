jest.mock('@/lib/api', () => ({
  post: jest.fn(),
  get: jest.fn(),
}));

import '@testing-library/jest-dom';
import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { useRouter } from 'next/navigation';
import Cookies from 'js-cookie';
import { useToast } from '@/hooks/use-toast';
import LoginPage from '@/app/admin/login/page';
import { AuthProvider, useAuth } from '@/app/contexts/AuthContext';
import api from '@/lib/api';
import { ToastProvider } from '@/components/ui/toast';

// useToastのグローバルモック
const mockToast = { toast: jest.fn() };
jest.mock('@/hooks/use-toast', () => ({ useToast: () => mockToast }));

// モックの設定
jest.mock('next/navigation', () => ({
  useRouter: jest.fn(),
}));

jest.mock('js-cookie', () => ({
  get: jest.fn(),
  set: jest.fn(),
  remove: jest.fn(),
}));

// テスト用のラッパーコンポーネント
function renderWithAuth(ui: React.ReactElement) {
  const mockRouter = {
    push: jest.fn(),
    replace: jest.fn(),
    back: jest.fn(),
  };

  // useRouterのモック
  jest.spyOn(require('next/navigation'), 'useRouter').mockReturnValue(mockRouter);

  return render(
    <ToastProvider>
      <AuthProvider>
        {ui}
      </AuthProvider>
    </ToastProvider>
  );
}

describe('認証機能のテスト', () => {
  const mockRouter = {
    push: jest.fn(),
  };

  const mockApi = {
    post: jest.fn(),
    get: jest.fn(),
  };

  beforeEach(() => {
    (useRouter as jest.Mock).mockReturnValue(mockRouter);
    jest.clearAllMocks();
    Cookies.remove('token');
    (api.post as jest.Mock).mockImplementation(mockApi.post);
    (api.get as jest.Mock).mockImplementation(mockApi.get);
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
      mockApi.post.mockResolvedValueOnce({
        data: {
          token: 'test-token',
          user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
          },
        },
      });

      render(
        <AuthProvider>
          <LoginPage />
        </AuthProvider>
      );

      fireEvent.change(screen.getByLabelText('メールアドレス'), {
        target: { value: 'test@example.com' },
      });
      fireEvent.change(screen.getByLabelText('パスワード'), {
        target: { value: 'password123' },
      });

      fireEvent.click(screen.getByRole('button', { name: 'ログイン' }));

      await waitFor(() => {
        expect(mockApi.post).toHaveBeenCalledWith('/auth/login', {
          email: 'test@example.com',
          password: 'password123',
        });
        expect(mockRouter.push).toHaveBeenCalledWith('/admin/dashboard');
        expect(Cookies.set).toHaveBeenCalledWith('token', 'test-token', { expires: 7 });
        expect(mockToast.toast).toHaveBeenCalledWith({
          title: 'ログインしました',
          description: 'ContActへようこそ',
        });
      });
    });

    it('ログイン失敗時にエラーメッセージが表示される', async () => {
      mockApi.post.mockRejectedValueOnce(new Error('Invalid credentials'));

      render(
        <AuthProvider>
          <LoginPage />
        </AuthProvider>
      );

      fireEvent.change(screen.getByLabelText('メールアドレス'), {
        target: { value: 'test@example.com' },
      });
      fireEvent.change(screen.getByLabelText('パスワード'), {
        target: { value: 'wrong-password' },
      });

      fireEvent.click(screen.getByRole('button', { name: 'ログイン' }));

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
      mockApi.post.mockResolvedValueOnce({
        data: {
          token: 'test-token',
          user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
          },
        },
      });

      const TestComponent = () => {
        const { user, login } = useAuth();
        return (
          <div>
            <div>{user ? `ログイン中: ${user.email}` : '未ログイン'}</div>
            <button onClick={() => login('test@example.com', 'password123')}>ログイン</button>
          </div>
        );
      };

      renderWithAuth(<TestComponent />);

      fireEvent.click(screen.getByRole('button', { name: 'ログイン' }));

      await waitFor(() => {
        expect(screen.getByText('ログイン中: test@example.com')).toBeInTheDocument();
      });
    });

    it('ログアウト後にユーザー情報がクリアされる', async () => {
      mockApi.post.mockResolvedValueOnce({
        data: {
          token: 'test-token',
          user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
          },
        },
      });

      const TestComponent = () => {
        const { user, login, logout } = useAuth();
        return (
          <div>
            <div>{user ? `ログイン中: ${user.email}` : '未ログイン'}</div>
            <button onClick={() => login('test@example.com', 'password123')}>ログイン</button>
            <button onClick={logout}>ログアウト</button>
          </div>
        );
      };

      renderWithAuth(<TestComponent />);

      fireEvent.click(screen.getByRole('button', { name: 'ログイン' }));

      await waitFor(() => {
        expect(screen.getByText('ログイン中: test@example.com')).toBeInTheDocument();
      });

      fireEvent.click(screen.getByRole('button', { name: 'ログアウト' }));

      await waitFor(() => {
        expect(screen.getByText('未ログイン')).toBeInTheDocument();
        expect(Cookies.remove).toHaveBeenCalledWith('token');
      });
    });
  });
}); 