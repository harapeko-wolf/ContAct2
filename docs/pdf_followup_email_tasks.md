# PDFフォローアップメール機能実装タスク

## 概要
PDFを最後まで閲覧した後、モーダルが表示されてから15分以内にTimeRexで予約されなかった場合に、フォローアップメールを送信する機能を実装する。

## 要件
- PDFの最後のページ到達でモーダル表示
- モーダル表示から15分後にメール送信（TimeRex予約がない場合）
- 「後で検討する」ボタンでタイマーリセット（0から15分再カウント）
- app_settingsに設定値保存（デフォルト15分）
- 管理画面で設定変更可能
- メール送信先はcompaniesテーブルのemail（なければ送信しない）
- **受注済み会社（companies.status = 'active'）にはメール送信しない**
- **TimeRex予約済みの会社にはメール送信しない**
- Mailtrapでメール確認

## 実装タスク

### Phase 1: データベース・設定準備 ✅ **完了**
- [x] app_settingsにフォローアップメール設定を追加
- [x] マイグレーション・シーダー作成
- [x] フォローアップメール記録用テーブル作成（重複送信防止）

### Phase 2: バックエンド実装 ✅ **完了**
- [x] Laravel Mailableクラス作成（フォローアップメール）
- [x] メール送信サービスクラス作成
- [x] フォローアップメール管理機能実装
- [x] タイマー管理API実装
- [x] TimeRex予約チェック機能実装

### Phase 3: フロントエンド実装 ✅ **完了**
- [x] フォローアップタイマー管理機能追加
- [x] モーダル表示時のタイマー開始
- [x] 「後で検討する」ボタンでのタイマーリセット
- [x] TimeRex予約時のタイマー停止

### Phase 4: 管理画面実装 ✅ **完了**
- [x] app_settingsにフォローアップメール設定追加
- [x] 設定画面でのフォローアップメール時間設定
- [x] フォローアップメール送信履歴表示（設定画面に統合）

### Phase 5: テスト実装 ✅ **完了**
- [x] フォローアップメール送信のユニットテスト
- [x] タイマー管理機能のテスト
- [x] TimeRex予約チェック機能のテスト
- [x] E2Eテスト（モーダル表示からメール送信まで）

### Phase 6: ドキュメント更新・最適化 ✅ **完了**
- [x] API仕様書更新
- [x] 機能説明ドキュメント作成
- [x] 運用手順書作成
- [x] **N+1問題修正**（会社一覧、エンゲージメントスコア計算、PDF並び順更新）
- [x] **docker-compose.yml & Cron設定ドキュメント更新**

## 技術仕様

### 設定値
```json
{
  "key": "email.followup_delay_minutes",
  "value": 15,
  "description": "フォローアップメール送信までの遅延時間（分）",
  "type": "number",
  "is_public": false
}
```

### データベーステーブル

#### followup_emails テーブル
```sql
CREATE TABLE followup_emails (
    id CHAR(36) PRIMARY KEY,
    company_id CHAR(36) NOT NULL,
    document_id CHAR(36) NOT NULL,
    viewer_ip VARCHAR(45) NOT NULL,
    triggered_at TIMESTAMP NOT NULL,
    scheduled_for TIMESTAMP NOT NULL,
    sent_at TIMESTAMP NULL,
    status ENUM('scheduled', 'sent', 'cancelled', 'failed') DEFAULT 'scheduled',
    cancellation_reason VARCHAR(255) NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    INDEX idx_company_scheduled (company_id, scheduled_for),
    INDEX idx_status_scheduled (status, scheduled_for)
);
```

### API仕様

#### フォローアップタイマー開始
```
POST /api/companies/{companyId}/pdfs/{documentId}/followup-timer
{
  "viewer_ip": "192.168.1.1",
  "triggered_at": "2025-01-15T10:00:00Z"
}
```

#### フォローアップタイマー停止
```
DELETE /api/companies/{companyId}/pdfs/{documentId}/followup-timer
{
  "viewer_ip": "192.168.1.1",
  "reason": "user_dismissed|timerex_booked"
}
```

#### TimeRex予約チェック
```
GET /api/companies/{companyId}/timerex-bookings/recent
```

### メール設定
- 送信先: companies.email
- 件名: 「資料のご確認ありがとうございました - さらに詳しくご説明いたします」
- 本文: 営業フォローアップ用テンプレート
- 送信者: システム設定

### バックグラウンド処理
- Cronスケジュールを使用（毎分実行）
- 「後で検討する」選択時は既存タイマーをキャンセルして新規タイマー開始
- TimeRex予約確認とメール送信
- **受注済み会社（companies.status = 'active'）は自動的にスキップ**
- リセット毎にメール送信可能（重複を適切に管理）

## 送信停止条件
1. **TimeRex予約済み**: 30分以内の予約確定がある場合
2. **受注済み会社**: companies.status = 'active'（受注ステータス）の場合
3. **メールアドレス未設定**: companies.emailが空の場合
4. **機能無効**: app_settings.email.followup_enabled = false の場合

## 最適化・修正履歴

### N+1問題修正 ✅ **完了**
- **会社一覧API**: 各会社のスコア計算で発生していたN+1問題を修正
  - フィードバックデータとビューデータを一括取得
  - バッチ処理でスコア計算を最適化
- **エンゲージメントスコア計算**: ループ内でのクエリ実行を修正
  - ドキュメントの最大ページ数を事前に一括取得
  - ループ外でデータを準備してN+1問題を解決
- **PDF並び順更新**: 個別更新をトランザクション化
  - DB::transactionでパフォーマンス向上

### Docker & Cron設定 ✅ **完了**
- **docker-compose.yml**: 言語設定環境変数追加
- **start-cron.sh**: Debian/Ubuntu対応のCron設定に修正
- **自動化**: Docker環境でのCron自動起動設定完了

## 実装ステータス
- **Phase 1**: ✅ 完了
- **Phase 2**: ✅ 完了  
- **Phase 3**: ✅ 完了
- **Phase 4**: ✅ 完了
- **Phase 5**: ✅ 完了
- **Phase 6**: ✅ 完了（N+1問題修正・ドキュメント更新完了）

## メモ
- Mailtrapでメール確認
- .envにMailtrap設定済み
- システム全体で統一設定
- 重複送信防止機能必須
- **パフォーマンス最適化完了**
- **言語設定（日本語）完全対応**
- **Cron自動化・エラーハンドリング完了** 