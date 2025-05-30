import { screen, waitFor, act, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import EditCompanyPage from '../page'
import { companyApi } from '@/lib/api'
import { renderWithAuth } from '../../../__tests__/test-utils'

// APIモック
jest.mock('@/lib/api', () => ({
  companyApi: {
    getById: jest.fn(),
    update: jest.fn(),
  },
}))

// モックデータ
const mockCompany = {
  id: '1',
  name: 'テスト株式会社',
  email: 'test@example.com',
  status: 'inactive',
  phone: '',
  address: '',
  website: '',
  description: '',
  industry: '',
  employee_count: undefined,
  created_at: '2024-01-01T00:00:00.000000Z',
  updated_at: '2024-01-01T00:00:00.000000Z',
}

beforeAll(() => {
  // JSDOMにhasPointerCaptureを追加
  Element.prototype.hasPointerCapture = () => false;
});

describe('EditCompanyPage', () => {
  beforeEach(() => {
    // モックのリセット
    jest.clearAllMocks()
    // APIのモック実装
    ;(companyApi.get as jest.Mock).mockResolvedValue(mockCompany)
    ;(companyApi.update as jest.Mock).mockResolvedValue(mockCompany)
  })

  it('既存の会社情報が表示される', async () => {
    renderWithAuth(<EditCompanyPage params={{ id: '1' }} />)
    // 会社情報の表示を待機
    await waitFor(() => {
      expect(screen.getByDisplayValue('テスト株式会社')).toBeInTheDocument()
      expect(screen.getByDisplayValue('test@example.com')).toBeInTheDocument()
    })
  })

  it('バリデーションエラーが表示される', async () => {
    renderWithAuth(<EditCompanyPage params={{ id: '1' }} />)
    // 会社情報の表示を待機
    await waitFor(() => {
      expect(screen.getByDisplayValue('テスト株式会社')).toBeInTheDocument()
    })
    // 必須フィールドをクリア
    await act(async () => {
      await userEvent.clear(screen.getByLabelText('会社名'))
      await userEvent.clear(screen.getByLabelText('メールアドレス'))
    })
    // 送信ボタンを取得してクリック
    const submitButton = screen.getByRole('button', { name: /更新する/i })
    await act(async () => {
      await userEvent.click(submitButton)
    })
    // エラーメッセージの確認
    expect(screen.getByText('会社名は必須です')).toBeInTheDocument()
    expect(screen.getByText('有効なメールアドレスを入力してください')).toBeInTheDocument()
  })

  it('会社情報が更新される', async () => {
    (companyApi.get as jest.Mock).mockResolvedValue({
      ...mockCompany,
      status: 'inactive',
    })
    renderWithAuth(<EditCompanyPage params={{ id: '1' }} />)
    await waitFor(() => {
      expect(screen.getByDisplayValue('テスト株式会社')).toBeInTheDocument()
    })
    await act(async () => {
      await userEvent.clear(screen.getByLabelText('会社名'))
      await userEvent.type(screen.getByLabelText('会社名'), '更新後株式会社')
    })
    // 送信ボタンを取得してクリック
    const submitButton = screen.getByRole('button', { name: /更新する/i })
    await act(async () => {
      await userEvent.click(submitButton)
    })
    await waitFor(() => {
      expect(companyApi.update).toHaveBeenCalledWith('1', expect.objectContaining({
        name: '更新後株式会社',
        email: 'test@example.com',
        status: 'inactive',
      }))
    })
  })
}) 