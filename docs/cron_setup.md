# Docker環境でのCron自動設定

## 概要
フォローアップメール機能は**Docker環境で自動的にCronが動作**します。手動設定は不要です。

## 🚀 **自動化済み設定**

### docker-compose.ymlの設定（2025年6月更新版）
```yaml
cron:
  build:
    context: .
    dockerfile: docker/backend/Dockerfile
  volumes:
    - ./backend:/var/www/html
  environment:
    - APP_ENV=${APP_ENV:-local}
    - APP_KEY=${APP_KEY}
    - APP_DEBUG=${APP_DEBUG:-true}
    - APP_LOCALE=${APP_LOCALE:-ja}
    - APP_FALLBACK_LOCALE=${APP_FALLBACK_LOCALE:-ja}
    - APP_FAKER_LOCALE=${APP_FAKER_LOCALE:-ja_JP}
    - DB_CONNECTION=${DB_CONNECTION:-mysql}
    - DB_HOST=${DB_HOST:-mysql}
    - DB_PORT=${DB_PORT:-3306}
    - DB_DATABASE=${DB_DATABASE:-contact2_db}
    - DB_USERNAME=${DB_USERNAME:-contact2_user}
    - DB_PASSWORD=${DB_PASSWORD:-contact2_password}
    - MAIL_MAILER=${MAIL_MAILER:-smtp}
    - MAIL_HOST=${MAIL_HOST}
    - MAIL_PORT=${MAIL_PORT}
    - MAIL_USERNAME=${MAIL_USERNAME}
    - MAIL_PASSWORD=${MAIL_PASSWORD}
    - MAIL_ENCRYPTION=${MAIL_ENCRYPTION}
    - MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS}
    - MAIL_FROM_NAME=${MAIL_FROM_NAME}
  depends_on:
    - mysql
    - backend
  command: ["sh", "/usr/local/bin/start-cron.sh"]
```

### start-cron.sh（Debian/Ubuntu対応版）
```bash
#!/bin/bash

echo "Starting ContAct2 Cron Service..."

# ログファイルの作成
touch /var/log/cron.log

# Debian/Ubuntu形式のcrontab設定
echo "* * * * * cd /var/www/html && /usr/local/bin/php artisan schedule:run >> /var/log/cron.log 2>&1" | crontab -

echo "Cron job added:"
crontab -l

echo "Starting cron daemon..."
service cron start

# 最初に一度手動実行（テスト用）
echo "Running initial schedule test..."
cd /var/www/html && /usr/local/bin/php artisan schedule:run

# Cronログをフォロー（コンテナを起動し続けるため）
echo "Following cron log..."
tail -f /var/log/cron.log
```

## 🔄 **起動・確認方法**

### 1. コンテナ起動
```bash
# すべてのコンテナを起動（Cronも自動起動）
docker compose up -d

# Cronコンテナのみ再起動
docker compose restart cron
```

### 2. Cron動作確認
```bash
# Cronコンテナのログ確認
docker compose logs -f cron

# フォローアップメール処理のログ確認
docker compose exec cron tail -f /var/log/cron.log

# 手動でスケジュール実行テスト
docker compose exec cron php artisan schedule:run
```

### 3. フォローアップメール処理の確認
```bash
# 直接実行してテスト
docker compose exec cron php artisan followup:process-emails

# 日本語設定確認
docker compose exec backend php artisan tinker --execute="echo 'APP_LOCALE: ' . config('app.locale');"

# Laravelのログ確認
docker compose exec backend tail -f storage/logs/laravel.log
```

### 4. 言語設定確認
```bash
# 環境変数確認
docker compose exec backend env | grep APP_

# 言語ファイル確認
docker compose exec backend ls -la lang/

# 設定キャッシュクリア
docker compose exec backend php artisan config:clear
docker compose exec backend php artisan cache:clear
```

## 📊 **動作フロー**

1. **Docker起動** → Cronコンテナが自動起動
2. **毎分実行** → `php artisan schedule:run`
3. **フォローアップ処理** → `followup:process-emails`
4. **メール送信** → 15分経過したもの（日本語メール）

## 🛠️ **本番環境**

### AWS/VPS等での設定
```bash
# crontab -e で手動設定（本番のみ）
* * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1
```

### Docker本番環境
```yaml
# docker-compose.prod.yml
cron:
  restart: unless-stopped
  environment:
    - APP_ENV=production
    - APP_LOCALE=ja
    - APP_FALLBACK_LOCALE=ja
  command: ["sh", "/usr/local/bin/start-cron.sh"]
```

## 🔍 **トラブルシューティング**

### Cronが動作しない
```bash
# Cronコンテナの状態確認
docker compose ps cron

# Cronコンテナ内に入って確認
docker compose exec cron sh
crontab -l  # Cron設定確認
service cron status  # Cronデーモン状態確認
```

### フォローアップメールが送信されない
```bash
# データベース確認
docker compose exec backend php artisan tinker
>>> App\Models\FollowupEmail::where('status', 'scheduled')->get();

# 設定確認
>>> App\Models\AppSetting::get('email.followup_enabled');
>>> App\Models\AppSetting::get('email.followup_delay_minutes');

# メール設定確認
>>> config('mail');
```

### 言語設定が反映されない
```bash
# 環境変数確認
docker compose exec backend env | grep APP_

# 言語ファイル確認
docker compose exec backend ls -la lang/

# 設定キャッシュクリア
docker compose exec backend php artisan config:clear
docker compose exec backend php artisan cache:clear
```

## ✅ **最新機能（2025年6月版）**

### 新機能・改善点
- ✅ **日本語完全対応**: メール・バリデーション・ログすべて日本語化
- ✅ **N+1問題修正**: 会社一覧・スコア計算の大幅なパフォーマンス向上
- ✅ **Debian/Ubuntu対応**: より安定したCron動作
- ✅ **環境変数統一**: docker-compose.ymlでの言語設定完全対応
- ✅ **エラーハンドリング強化**: 詳細ログとエラー追跡

### パフォーマンス最適化
- **バッチ処理**: 会社一覧でのスコア計算をバッチ化
- **一括取得**: フィードバック・ビューデータを一括取得
- **キャッシュ活用**: 設定データのキャッシュ最適化

## 🎯 **結論**

**手動設定は不要！最新版での改善点:**
- ✅ `docker compose up -d` で自動起動
- ✅ 毎分自動でフォローアップメール処理
- ✅ ページを離れても15分後にメール送信
- ✅ **日本語メール対応完了**
- ✅ **パフォーマンス大幅改善**
- ✅ 本番環境でも同様に動作 