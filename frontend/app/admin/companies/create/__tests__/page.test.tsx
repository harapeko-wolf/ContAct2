import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import CreateCompanyPage from '../page'
import { companyApi } from '@/lib/api'
import { renderWithAuth } from '../../__tests__/test-utils'
import { act } from '@testing-library/react'

// APIモック
jest.mock('@/lib/api', () => ({
  companyApi: {
    create: jest.fn(),
  },
}))

// モックデータ
const mockCompany = {
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
}

// JSDOMのpointerイベント・scrollIntoView対応
if (!global.HTMLElement.prototype.hasPointerCapture) {
  global.HTMLElement.prototype.hasPointerCapture = () => false;
}
if (!global.HTMLElement.prototype.scrollIntoView) {
  global.HTMLElement.prototype.scrollIntoView = () => {};
}

describe('CreateCompanyPage', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('フォームが表示される', async () => {
    renderWithAuth(<CreateCompanyPage />)
    
    // 必須フィールドの確認
    expect(screen.getByLabelText('会社名')).toBeInTheDocument()
    expect(screen.getByLabelText('メールアドレス')).toBeInTheDocument()
    expect(screen.getByLabelText('ステータス')).toBeInTheDocument()
  })

  it('バリデーションエラーが表示される', async () => {
    renderWithAuth(<CreateCompanyPage />)
    // 送信ボタンを取得
    const submitButton = await screen.findByRole('button', { name: '作成する' })
    await act(async () => {
      await userEvent.click(submitButton)
    })
    // エラーメッセージの確認
    await waitFor(() => {
      expect(screen.getByText('会社名は2文字以上必要です')).toBeInTheDocument()
      expect(screen.getByText('有効なメールアドレスを入力してください')).toBeInTheDocument()
    })
  })

  it('会社が作成される', async () => {
    renderWithAuth(<CreateCompanyPage />)
    // フォームに入力
    await act(async () => {
      await userEvent.type(screen.getByLabelText('会社名'), 'テスト株式会社')
      await userEvent.type(screen.getByLabelText('メールアドレス'), 'test@example.com')
    })
    // ステータス選択（textContent/className/data-stateでトリガー特定）
    const allButtons = screen.getAllByRole('button')
    const statusTrigger = allButtons.find(btn => {
      const txt = btn.textContent || ''
      return txt.includes('ステータス') || txt.includes('受注') || txt.includes('営業中') || txt.includes('失注') || btn.getAttribute('data-state') === 'closed' || (btn.className && btn.className.includes('select'))
    })
    expect(statusTrigger).toBeTruthy()
    await act(async () => {
      await userEvent.click(statusTrigger!)
    })
    // ステータスオプションを選択
    // Radix UIのポータル描画対策: DOMから直接取得
    let statusOption: HTMLElement | null = null;
    await waitFor(() => {
      statusOption = Array.from(document.querySelectorAll('div,button,span,li')).find(
        (el): el is HTMLElement => !!el.textContent && el.textContent.includes('受注')
      ) || null;
      expect(statusOption).toBeTruthy();
    });
    await act(async () => {
      statusOption!.click();
    });
    // ドロップダウンを閉じる
    await act(async () => {
      await userEvent.keyboard('{Escape}')
    })
    // 送信ボタンをtype=submitで直接取得
    const submitButton = document.querySelector('button[type="submit"]') as HTMLButtonElement
    expect(submitButton).toBeTruthy()
    await act(async () => {
      await userEvent.click(submitButton)
    })
    // APIが呼ばれたことを確認
    await waitFor(() => {
      expect(companyApi.create).toHaveBeenCalledWith({
        name: 'テスト株式会社',
        email: 'test@example.com',
        status: 'active',
        phone: '',
        address: '',
        website: '',
        description: '',
        industry: '',
        employee_count: undefined,
      })
    })
  })
}) 