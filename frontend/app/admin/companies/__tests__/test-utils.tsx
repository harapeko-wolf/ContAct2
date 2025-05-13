import { render } from '@testing-library/react'
import { AuthContext } from '../../../contexts/AuthContext'
import { useRouter } from 'next/navigation'

// useToastのグローバルモック
jest.mock('@/hooks/use-toast', () => ({
  useToast: () => ({
    toast: jest.fn(),
  }),
}))

// テスト用のAuthContextの値
const mockAuthContext = {
  user: {
    id: '1',
    name: 'テストユーザー',
    email: 'test@example.com',
  },
  token: 'dummy-token',
  isLoading: false,
  login: jest.fn(),
  register: jest.fn(),
  logout: jest.fn(),
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
    <AuthContext.Provider value={mockAuthContext as any}>
      {ui}
    </AuthContext.Provider>
  )
}

describe.skip('test-utils dummy', () => {
  it('dummy', () => {
    // ダミーテスト（実行されません）
  })
}) 