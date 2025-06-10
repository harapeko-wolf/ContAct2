#!/bin/sh

echo "Starting ContAct2 Cron Service..."

# Cronジョブを追加
echo "* * * * * cd /var/www/html && /usr/local/bin/php artisan schedule:run >> /var/log/cron.log 2>&1" > /etc/crontabs/root

echo "Cron job added:"
cat /etc/crontabs/root

echo "Starting cron daemon..."
crond -f 