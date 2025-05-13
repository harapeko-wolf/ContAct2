jest.mock('@/lib/api', () => ({
  companyApi: {
    getById: jest.fn(),
    update: jest.fn(),
    delete: jest.fn(),
  },
}));

import { render } from '@testing-library/react'
import { AuthContext } from '../../../contexts/AuthContext'
import { useRouter } from 'next/navigation'
import { ToastProvider } from '@/components/ui/toast'

// useToastのグローバルモック
jest.mock('@/hooks/use-toast', () => ({
  useToast: () => ({
    toast: jest.fn(),
  }),
}))

// モックの認証コンテキスト
const mockAuthContext = {
  user: null,
  login: jest.fn(),
  register: jest.fn(),
  logout: jest.fn(),
  isLoading: false,
}

// テスト用のラッパーコンポーネント
export function renderWithAuth(ui: React.ReactElement) {
  const mockRouter = {
    push: jest.fn(),
    replace: jest.fn(),
    back: jest.fn(),
  }

  // useRouterのモック
  jest.spyOn(require('next/navigation'), 'useRouter').mockReturnValue(mockRouter)

  return render(
    <ToastProvider>
      <AuthContext.Provider value={mockAuthContext}>
        {ui}
      </AuthContext.Provider>
    </ToastProvider>
  )
}

// モックデータ
export const mockCompany = {
  id: '1',
  name: 'テスト株式会社',
  email: 'test@example.com',
  phone: '03-1234-5678',
  address: '東京都渋谷区',
  website: 'https://example.com',
  description: 'テスト用の会社です',
  industry: 'IT',
  employee_count: 100,
  status: 'active',
  created_at: '2024-01-01T00:00:00.000000Z',
  updated_at: '2024-01-01T00:00:00.000000Z',
}

// モックのAPI関数
export const mockCompanyApi = {
  getById: jest.fn().mockResolvedValue(mockCompany),
  update: jest.fn().mockResolvedValue(mockCompany),
  delete: jest.fn().mockResolvedValue({}),
}

describe.skip('test-utils dummy', () => {
  it('dummy', () => {
    // ダミーテスト（実行されません）
  })
}) 