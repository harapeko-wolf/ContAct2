import { screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import CompaniesPage from '../page'
import CreateCompanyPage from '../create/page'
import { companyApi } from '@/lib/api'
import { renderWithAuth } from './test-utils'
import { act } from '@testing-library/react'

// JSDOMのhasPointerCaptureモック
if (!window.HTMLElement.prototype.hasPointerCapture) {
  window.HTMLElement.prototype.hasPointerCapture = function (_pointerId) { return false; };
}

// scrollIntoViewのモック
if (!window.HTMLElement.prototype.scrollIntoView) {
  window.HTMLElement.prototype.scrollIntoView = function () {};
}

// APIモック
jest.mock('@/lib/api', () => ({
  companyApi: {
    getAll: jest.fn(),
    delete: jest.fn(),
    update: jest.fn(),
    create: jest.fn(),
  },
}))

// モックデータ
const mockCompanies = {
  data: [
    {
      id: '1',
      name: 'テスト株式会社',
      email: 'test@example.com',
      status: 'active',
      phone: null,
      address: null,
      website: null,
      description: null,
      industry: null,
      employee_count: null,
      created_at: '2024-01-01T00:00:00.000000Z',
      updated_at: '2024-01-01T00:00:00.000000Z',
    },
  ],
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 1,
}

describe('CompaniesPage', () => {
  beforeEach(() => {
    // モックのリセット
    jest.clearAllMocks()
    // APIのモック実装
    ;(companyApi.getAll as jest.Mock).mockResolvedValue(mockCompanies)
  })

  it('会社一覧が表示される', async () => {
    await act(async () => {
      renderWithAuth(<CompaniesPage />)
    })
    
    // 会社一覧の表示を待機
    await waitFor(() => {
      expect(screen.getByText('テスト株式会社')).toBeInTheDocument()
    })
    
    // ステータスバッジの確認（最初のspanを取得）
    const badges = screen.getAllByText('受注')
    expect(badges.length).toBeGreaterThan(0)
  })

  it('検索機能が動作する', async () => {
    await act(async () => {
      renderWithAuth(<CompaniesPage />)
    })
    
    // 検索ボックスの表示を待機
    await waitFor(() => {
      expect(screen.getByPlaceholderText('会社を検索...')).toBeInTheDocument()
    })
    
    // 検索を実行
    const searchInput = screen.getByPlaceholderText('会社を検索...')
    await act(async () => {
      await userEvent.type(searchInput, 'テスト')
    })
    
    // APIが呼ばれたことを確認
    await waitFor(() => {
      expect(companyApi.getAll).toHaveBeenCalled()
    })
  })

  it('ステータスフィルターが動作する', async () => {
    await act(async () => {
      renderWithAuth(<CompaniesPage />)
    })
    
    // ステータスボタンの表示を待機
    await waitFor(() => {
      expect(screen.getAllByText('受注').length).toBeGreaterThan(0)
    })
    
    // ステータスフィルターボタン（button）を取得
    const statusButtons = screen.getAllByRole('button', { name: '受注' })
    expect(statusButtons.length).toBeGreaterThan(0)
    
    await act(async () => {
      await userEvent.click(statusButtons[0])
    })
    
    // APIが呼ばれたことを確認
    await waitFor(() => {
      expect(companyApi.getAll).toHaveBeenCalled()
    })
  })

  it('会社の削除が動作する', async () => {
    await act(async () => {
      renderWithAuth(<CompaniesPage />)
    })

    await waitFor(() => {
      expect(screen.getByText('テスト株式会社')).toBeInTheDocument()
    })

    // data-testid="company-menu"のボタンを取得
    const moreButtons = await screen.findAllByTestId('company-menu')
    expect(moreButtons.length).toBeGreaterThan(0)
    const moreButton = moreButtons[0]

    await act(async () => {
      await userEvent.click(moreButton)
    })

    // 「会社を削除」テキストを持つ要素を取得してクリック
    const deleteButton = await screen.findByRole('menuitem', { name: '会社を削除' })
    await act(async () => {
      await userEvent.click(deleteButton)
    })

    // 確認ダイアログの「削除する」ボタンをクリック
    const confirmButton = await screen.findByRole('button', { name: '削除する' })
    await act(async () => {
      await userEvent.click(confirmButton)
    })

    // APIが呼ばれたことを確認
    await waitFor(() => {
      expect(companyApi.delete).toHaveBeenCalledWith('1')
    })
  })
})

describe('CreateCompanyPage', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('バリデーションエラーが表示される', async () => {
    await act(async () => {
      renderWithAuth(<CreateCompanyPage />)
    })

    const submitButton = await screen.findByRole('button', { name: '作成する' })
    await act(async () => {
      await userEvent.click(submitButton)
    })

    await waitFor(() => {
      expect(screen.getByText('会社名は2文字以上必要です')).toBeInTheDocument()
      expect(screen.getByText('有効なメールアドレスを入力してください')).toBeInTheDocument()
    })
  })

  it('会社が作成される', async () => {
    await act(async () => {
      renderWithAuth(<CreateCompanyPage />)
    })

    await act(async () => {
      await userEvent.type(screen.getByLabelText('会社名'), 'テスト株式会社')
      await userEvent.type(screen.getByLabelText('メールアドレス'), 'test@example.com')
    })

    const statusButton = screen.getByLabelText('ステータス')
    await act(async () => {
      await userEvent.click(statusButton)
    })

    // 「受注」オプションをfindAllByRoleで取得しtextContent一致でクリック
    const options = await screen.findAllByRole('option')
    const option = options.find(opt => opt.textContent === '受注')
    expect(option).toBeTruthy()

    await act(async () => {
      await userEvent.click(option!)
    })

    // ドロップダウンを閉じる
    await act(async () => {
      await userEvent.keyboard('{Escape}')
    })

    const submitButton = screen.getByRole('button', { name: '作成する' })
    await act(async () => {
      await userEvent.click(submitButton)
    })

    await waitFor(() => {
      expect(companyApi.create).toHaveBeenCalledWith(expect.objectContaining({
        name: 'テスト株式会社',
        email: 'test@example.com',
        status: 'active',
      }))
    })
  })
}) 