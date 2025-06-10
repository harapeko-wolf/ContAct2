#!/bin/bash

echo "Starting ContAct2 Cron Service..."

# cronデーモンが利用可能か確認
if ! command -v cron &> /dev/null && ! command -v crond &> /dev/null; then
    echo "cronデーモンが見つかりません。cronサービスをインストールしてください。"
    exit 1
fi

# ログファイルの作成
touch /var/log/cron.log

# crontab設定前にcronサービスを開始
if command -v service &> /dev/null; then
    echo "systemdでcronサービスを開始..."
    service cron start 2>/dev/null || echo "cronサービス開始スキップ"
elif command -v rc-service &> /dev/null; then
    echo "OpenRCでcrondサービスを開始..."
    rc-service crond start 2>/dev/null || echo "crondサービス開始スキップ"
fi

# crontab設定
echo "* * * * * cd /var/www/html && /usr/local/bin/php artisan schedule:run >> /var/log/cron.log 2>&1" | crontab -

echo "Cron job added:"
crontab -l

# 最初に一度手動実行（テスト用）
echo "Running initial schedule test..."
cd /var/www/html && /usr/local/bin/php artisan schedule:run

# Cronログをフォロー（コンテナを起動し続けるため）
echo "Following cron log..."
tail -f /var/log/cron.log 