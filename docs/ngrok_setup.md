# ngrok設定ガイド - TimeRex Webhook開発

## 概要
ローカル開発環境でTimeRex Webhookをテストするためのngrokセットアップ手順です。

## ngrokのインストール

### macOS (Homebrew)
```bash
brew install ngrok/ngrok/ngrok
```

### その他のOS
[ngrok公式サイト](https://ngrok.com/download)からダウンロード

## ngrokアカウント設定

1. [ngrok.com](https://ngrok.com/)でアカウント作成
2. 認証トークンを取得
3. 認証トークンを設定
```bash
ngrok config add-authtoken YOUR_AUTHTOKEN
```

## ローカル開発環境での使用

### 1. Dockerコンテナ起動
```bash
docker compose up -d
```

### 2. ngrokでトンネル作成
```bash
# バックエンドAPIをngrokで公開
ngrok http 80
```

### 3. ngrok URLの確認
ngrok起動後、以下のような出力が表示されます：
```
Forwarding  https://abc123.ngrok.io -> http://localhost:80
```

### 4. TimeRex Webhook URL設定
TimeRex管理画面で以下のURLを設定：
```
https://abc123.ngrok.io/api/timerex/webhook
```

## 環境変数設定

### .envファイルに追加
```env
# TimeRex設定
TIMEREX_WEBHOOK_TOKEN=your_webhook_token_here
```

## テスト手順

### 1. ヘルスチェック
```bash
curl https://abc123.ngrok.io/api/timerex/webhook/health
```

### 2. 手動Webhookテスト
```bash
curl -X POST https://abc123.ngrok.io/api/timerex/webhook \
  -H "Content-Type: application/json" \
  -H "x-timerex-authorization: your_webhook_token_here" \
  -d '{
    "webhook_type": "event_confirmed",
    "calendar_url": "https://timerex.net/s/test/test?company_id=YOUR_COMPANY_UUID",
    "event": {
      "id": "test-event-id",
      "status": 1,
      "start_datetime": "2025-06-06T08:00:00+00:00",
      "end_datetime": "2025-06-06T09:00:00+00:00",
      "form": [
        {"field_type": "guest_name", "value": "テストユーザー"},
        {"field_type": "guest_email", "value": "test@example.com"}
      ]
    }
  }'
```

### 3. ログ確認
```bash
# Laravelログを確認
docker compose exec backend tail -f storage/logs/laravel.log

# ngrokリクエストログを確認
# ngrok Web Interface: http://localhost:4040
```

## トラブルシューティング

### ngrokが起動しない
- 認証トークンが正しく設定されているか確認
- ポート80が使用中でないか確認

### Webhookが受信されない
- ngrok URLが正しくTimeRexに設定されているか確認
- 認証トークンが一致しているか確認
- ファイアウォール設定を確認

### 会社IDが見つからない
- URLパラメータまたはguest_commentに正しい会社UUIDが設定されているか確認
- データベースに該当する会社が存在するか確認

## 本番環境への移行

本番環境では以下の設定が必要：
1. 固定ドメインの使用
2. SSL証明書の設定
3. IPアドレス制限の実装
4. 適切な認証トークンの設定 