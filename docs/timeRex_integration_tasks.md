# TimeRex連携API実装タスク

## 概要
TimeRexからのWebhook（予約確定・キャンセル）を受信し、companiesテーブルに予約情報を保存する機能を実装する。

## 実装スコープ
- `POST /api/timerex/webhook` - Webhook受信API
- ngrokを使用したローカル開発環境でのテスト
- 単体テスト・統合テストの作成

## 🎉 実装ステータス: **100%完了**
- ✅ **Phase 1~3**: 完全実装完了
- ✅ **Phase 4**: テスト実装完了（7/7テスト成功）
- ✅ **Phase 5**: ドキュメント整備完了
- ✅ **ngrok実機テスト**: 実際のWebhook通信で動作確認済み
- ✅ **guest_commentテスト修正**: UUID形式検証の修正完了

## TimeRex Webhook仕様

### サポートするイベント
1. **event_confirmed**: 予約確定時
2. **event_cancelled**: 予約キャンセル時

### 認証
- ヘッダー: `x-timerex-authorization`
- 値: 事前に設定したトークン（環境変数で管理）

### ペイロード構造
```json
{
  "webhook_type": "event_confirmed|event_cancelled",
  "calendar_url_path": "1de15a5b",
  "team_url_path": "harapeko.inc_7618",
  "calendar_url": "https://timerex.net/s/harapeko.inc_7618/1de15a5b",
  "calendar_name": "Test",
  "event": {
    "id": "d7ecbe650326308aad6b",
    "status": 1, // 1:確定, 2:キャンセル
    "start_datetime": "2025-06-06T08:00:00+00:00",
    "end_datetime": "2025-06-06T09:00:00+00:00",
    "local_start_datetime": "2025-06-06T17:00:00+09:00",
    "local_end_datetime": "2025-06-06T18:00:00+09:00",
    "canceled_at": "2025-06-05T13:12:01+00:00", // キャンセル時のみ
    "form": [
      {
        "field_type": "company_name",
        "value": "YOLO"
      },
      {
        "field_type": "guest_name", 
        "value": "harapeko.inc"
      },
      {
        "field_type": "guest_email",
        "value": "harapeko.inc@gmail.com"
      },
      {
        "field_type": "guest_comment",
        "value": "" // 会社IDを入れる（フリープラン対応）
      }
    ]
  }
}
```

## 会社・ドキュメント識別方法

### URLパラメータ方式（推奨）
TimeRex予約URLに以下のパラメータを付与：
```
https://timerex.net/s/team_path/calendar_path?company_id=xxx&document_id=yyy
```

### guest_comment方式（フリープラン対応）
TimeRexのコメント欄に会社IDを入力してもらう：
```json
{
  "field_type": "guest_comment",
  "value": "company_uuid_here"
}
```

## データベース設計

### companiesテーブル拡張
```sql
ALTER TABLE companies ADD COLUMN timerex_bookings JSON NULL;
```

### JSON構造例
```json
{
  "total_bookings": 5,
  "total_cancellations": 1,
  "bookings": [
    {
      "event_id": "d7ecbe650326308aad6b",
      "status": "confirmed", // confirmed, cancelled
      "start_datetime": "2025-06-06T08:00:00+00:00",
      "end_datetime": "2025-06-06T09:00:00+00:00",
      "guest_name": "harapeko.inc",
      "guest_email": "harapeko.inc@gmail.com",
      "company_name": "YOLO",
      "document_id": "xxx-xxx-xxx", // 関連ドキュメント（あれば）
      "created_at": "2025-06-05T13:06:31+00:00",
      "canceled_at": null // キャンセル時は設定
    }
  ]
}
```

## 実装タスク

### Phase 1: データベース準備 ✅ **完了**
- [x] マイグレーション作成：companiesテーブルに`timerex_bookings`カラム追加
- [x] Companyモデル更新：JSON castingとアクセサメソッド追加

### Phase 2: Webhook API実装 ✅ **完了**
- [x] TimeRexWebhookコントローラー作成
- [x] TimeRexサービスクラス作成
- [x] Webhook認証ミドルウェア作成
- [x] ルート定義追加

### Phase 3: ngrok設定 ✅ **完了**
- [x] ngrokのインストールと設定
- [x] Webhook URLの設定方法ドキュメント作成
- [x] ローカル開発環境での動作確認

### Phase 4: テスト実装 ✅ **完了 (7/7テスト成功)**
- [x] Webhookコントローラーの単体テスト
- [x] TimeRexサービスの単体テスト
- [x] 統合テスト（実際のWebhookペイロードでの動作確認）
- [x] 軽微なテスト修正（guest_comment方式テスト）- UUID形式検証修正完了

### Phase 5: ドキュメント整備 ✅ **完了**
- [x] API仕様書更新
- [x] 運用手順書作成
- [x] エラーハンドリング実装

## セキュリティ考慮事項
- Webhook認証トークンの環境変数管理
- IPアドレス制限（本番環境）
- ペイロードサイズ制限
- レート制限の設定

## エラーハンドリング
- 不正な認証トークン
- 存在しない会社ID
- 無効なペイロード形式
- データベース保存エラー

## 監視・ログ
- Webhook受信ログ
- 処理成功・失敗のメトリクス
- エラー通知設定 