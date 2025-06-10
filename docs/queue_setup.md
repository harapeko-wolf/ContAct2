# フォローアップメール設定手順

## 概要
フォローアップメール機能では、ページを離れても15分後にメール送信するためにCronスケジュールを使用しています。
**キューは使用せず、シンプルなCron方式で実装**

## 開発環境（Docker）

### 1. Cronスケジュールの実行
```bash
# 手動でフォローアップメール処理を実行
docker compose exec backend php artisan followup:process-emails

# Cronスケジュールを手動実行（開発用）
docker compose exec backend php artisan schedule:run
```

### 2. ログの確認
```bash
# フォローアップメール処理のログを確認
docker compose exec backend tail -f storage/logs/laravel.log
```

## 本番環境

### 1. Supervisorでキューワーカーを常駐化
```ini
# /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work --daemon --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/laravel-worker.log
```

### 2. Cronでスケジュールタスクを設定
```bash
# crontab -e
* * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1
```

## 設定の確認

### Cronの動作確認
```bash
# 手動でスケジュール実行
php artisan schedule:run

# フォローアップメール処理を直接実行
php artisan followup:process-emails
```

## フォローアップメールの動作

1. **PDF最後のページ到達** → タイマー開始API実行
2. **データベースに保存** → `followup_emails`テーブルに記録
3. **1分ごとにCron実行** → `followup:process-emails`コマンド
4. **15分経過チェック** → `scheduled_for < now()`の条件
5. **TimeRex予約チェック** → 予約済みならスキップ
6. **メール送信** → 条件が揃えば1回だけ送信

## トラブルシューティング

### キューワーカーが動作しない
```bash
# キューワーカーのプロセス確認
ps aux | grep "queue:work"

# ログ確認
tail -f storage/logs/laravel.log
```

### ジョブが失敗する
```bash
# 失敗したジョブを確認
php artisan queue:failed

# 失敗したジョブを再実行
php artisan queue:retry all
```

### 開発環境でのテスト
```bash
# 同期実行（テスト用）
QUEUE_CONNECTION=sync php artisan test
``` 