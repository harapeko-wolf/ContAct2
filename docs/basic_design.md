# 2. 基本設計書

## 2.1. システムアーキテクチャ
- フロントエンド: Next.js（ユーザーインターフェース、APIリクエスト）
- バックエンド: Laravel（ビジネスロジック、データベースアクセス、外部サービス連携）
- データベース: MySQL（アプリケーションデータの永続化）
- ファイルストレージ: クラウドストレージ（AWS S3推奨）
- キャッシュ: Redis（任意）
- ジョブキュー: Redis/Database（任意、メール送信などの非同期処理）

## 2.2. データベース設計
- `.cursor/rules/database.mdc` を参照
- 主要テーブルと役割（UUID主キー）:
    - `users`: 営業担当者情報、認証情報
    - `companies`: 会社情報
    - `documents`: アップロードされたPDFの情報
    - `document_shared_links`: 生成された共有リンク情報
    - `document_views`: 顧客によるPDF閲覧ログ
    - `document_surveys`: アンケート項目マスタ
    - `document_survey_responses`: 顧客のアンケート回答
    - `timerex_bookings`: TimeRex経由での予約情報
    - `email_follow_ups`: 送信したフォローアップメールの記録
- 顧客識別子: 閲覧セッションごとに一意のIDを付与

## 2.3. API設計
- `.cursor/rules/api-design.mdc` を参照
- 主要エンドポイント:
    - 認証関連（Sanctum）
    - 会社管理（CRUD）
    - 資料管理（CRUD, PDFアップロード/ダウンロード）
    - 共有リンク管理
    - 閲覧ログ記録
    - アンケート回答
    - TimeRex Webhook
    - ダッシュボードデータ取得

## 2.4. PDFストレージ設計
- Laravel Filesystemでクラウドストレージ（AWS S3等）を利用
- アップロード時にUUIDで一意なファイル名を生成
- 署名付きURLでの配信を推奨

## 2.5. TimeRex連携設計
- 営業担当者がTimeRex予約ページURLを設定
- Webhookで予約完了イベントを受信し、予約情報を保存

## 2.6. 認証・認可設計
- Laravel SanctumによるSPA認証（Cookieベース）
- APIルートの保護: `auth:sanctum`ミドルウェア
- CSRF保護

## 2.7. フォローアップメール送信設計
- PDFを最後まで閲覧したが予約がない場合、一定時間後に自動でメール送信
- Laravelのスケジュールジョブで定期実行

## 2.8. セキュリティ設計
- 入力値バリデーション、XSS/SQLインジェクション対策
- 機密情報の.env管理

## 2.9. 運用設計
- Dockerによるデプロイ
- Sentryによるエラー監視
- Laravelログ機能
- DB・PDFの定期バックアップ（S3等） 