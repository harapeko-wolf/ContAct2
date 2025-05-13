# 3. 詳細設計書

## 3.1. 画面一覧・画面遷移図
### 3.1.1. 営業担当者向け画面
- ログイン画面
  - メールアドレス/パスワード認証
  - パスワードリセット機能
- ダッシュボード画面
  - 資料閲覧状況の概要
  - ホットリード一覧
  - 最近のアクティビティ
- 会社管理画面
  - 会社情報の登録・編集
  - ユーザー管理
- 資料一覧画面
  - 資料の検索・フィルタリング
  - 閲覧状況の概要表示
- 資料アップロード画面
  - ドラッグ&ドロップ対応
  - アップロード進捗表示
- 資料詳細・共有リンク発行画面
  - 資料情報の編集
  - 共有リンクの生成・管理
  - アンケート設定
  - TimeRex連携設定

### 3.1.2. 顧客向け画面
- PDF閲覧画面
  - PDFビューアー
  - ページトラッキング
- アンケート表示画面
  - 4択アンケート
  - 回答送信
- TimeRexリンク表示画面
  - 予約ページへの誘導
  - 予約完了後のリダイレクト

## 3.2. 各画面の詳細設計
### 3.2.1. 共通レイアウト
- ヘッダー
  - ロゴ
  - ナビゲーションメニュー
  - ユーザーメニュー
- サイドバー
  - メニュー項目
  - 折りたたみ機能
- フッター
  - コピーライト
  - リンク

### 3.2.2. レスポンシブデザイン
- モバイルファースト
- ブレークポイント
  - スマートフォン: 〜768px
  - タブレット: 769px〜1024px
  - デスクトップ: 1025px〜

## 3.3. モジュール別詳細設計
### 3.3.1. ユーザー認証モジュール
- Laravel Sanctumによる認証
- セッション管理
- パスワードリセット機能

### 3.3.2. 資料管理モジュール
- PDFアップロード処理
- ストレージ管理
- メタデータ管理

### 3.3.3. 共有リンクモジュール
- UUID生成
- 有効期限管理
- アクセス制御

### 3.3.4. 閲覧トラッキングモジュール
- ページビュー記録
- 滞在時間計測
- イベントログ

### 3.3.5. アンケートモジュール
- アンケート設定
- 回答収集
- 結果集計

### 3.3.6. TimeRex連携モジュール
- Webhook受信
- 予約情報管理
- ステータス更新

### 3.3.7. 通知モジュール
- メール送信
- 通知設定
- テンプレート管理

## 3.4. APIエンドポイント詳細仕様
### 3.4.1. 認証API
- POST /api/auth/login
- POST /api/auth/logout
- POST /api/auth/refresh
- POST /api/auth/forgot-password
- POST /api/auth/reset-password

### 3.4.2. 資料管理API
- GET /api/documents
- POST /api/documents
- GET /api/documents/{id}
- PUT /api/documents/{id}
- DELETE /api/documents/{id}
- POST /api/documents/{id}/share
- GET /api/documents/{id}/stats

### 3.4.3. 閲覧トラッキングAPI
- POST /api/tracking/view
- POST /api/tracking/survey
- GET /api/tracking/stats

### 3.4.4. TimeRex連携API
- POST /api/timerex/webhook
- GET /api/timerex/bookings
- PUT /api/timerex/bookings/{id}

## 3.5. データベーステーブル定義
### 3.5.1. users
- id: uuid (PK)
- company_id: uuid (FK)
- name: string
- email: string (unique)
- password: string
- created_at: timestamp
- updated_at: timestamp

### 3.5.2. companies
- id: uuid (PK)
- name: string
- created_at: timestamp
- updated_at: timestamp

### 3.5.3. documents
- id: uuid (PK)
- company_id: uuid (FK)
- title: string
- file_path: string
- created_at: timestamp
- updated_at: timestamp

### 3.5.4. document_shared_links
- id: uuid (PK)
- document_id: uuid (FK)
- token: string (unique)
- expires_at: timestamp
- created_at: timestamp
- updated_at: timestamp

### 3.5.5. document_views
- id: uuid (PK)
- document_id: uuid (FK)
- shared_link_id: uuid (FK)
- page_number: integer
- view_duration: integer
- created_at: timestamp

### 3.5.6. document_surveys
- id: uuid (PK)
- document_id: uuid (FK)
- question: string
- options: json
- created_at: timestamp
- updated_at: timestamp

### 3.5.7. document_survey_responses
- id: uuid (PK)
- survey_id: uuid (FK)
- shared_link_id: uuid (FK)
- answer: string
- created_at: timestamp

### 3.5.8. timerex_bookings
- id: uuid (PK)
- document_id: uuid (FK)
- shared_link_id: uuid (FK)
- booking_id: string
- status: string
- created_at: timestamp
- updated_at: timestamp

## 3.6. バッチ処理・スケジュールジョブ
### 3.6.1. フォローアップメール送信ジョブ
- 実行タイミング: 毎時
- 処理内容:
  1. 読了済みで予約なしの顧客を抽出
  2. フォローアップメール送信
  3. 送信記録の保存

### 3.6.2. データクリーンアップジョブ
- 実行タイミング: 毎日
- 処理内容:
  1. 期限切れ共有リンクの削除
  2. 古い閲覧ログのアーカイブ
  3. 一時ファイルの削除

## 3.7. エラーハンドリング設計
### 3.7.1. エラーコード体系
- 1000-1999: 認証エラー
- 2000-2999: バリデーションエラー
- 3000-3999: ビジネスロジックエラー
- 4000-4999: 外部サービス連携エラー
- 5000-5999: システムエラー

### 3.7.2. エラーレスポンス形式
```json
{
  "error": {
    "code": 3001,
    "message": "エラーメッセージ",
    "details": {
      "field": "エラー詳細"
    }
  }
}
```

## 3.8. テスト計画
### 3.8.1. 単体テスト
- 各モジュールの機能テスト
- バリデーションルールのテスト
- エラーハンドリングのテスト

### 3.8.2. 結合テスト
- APIエンドポイントのテスト
- モジュール間連携のテスト
- データベース操作のテスト

### 3.8.3. E2Eテスト
- ユーザーフロー全体のテスト
- ブラウザ互換性テスト
- パフォーマンステスト 