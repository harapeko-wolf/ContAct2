FROM php:8.4-fpm

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    cron \
    && rm -rf /var/lib/apt/lists/*

# PHPの拡張機能をインストール
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Composerのインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# アプリケーションのコピー
COPY backend/ .

# PHP設定のコピー
COPY docker/backend/php.ini /usr/local/etc/php/conf.d/custom.ini

# Nginx設定のコピー
COPY docker/backend/nginx.conf /etc/nginx/sites-available/default

# 依存関係のインストール
RUN composer install

# 権限の設定
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 起動スクリプトの作成
COPY docker/backend/start.sh /usr/local/bin/start.sh
COPY docker/backend/start-cron.sh /usr/local/bin/start-cron.sh
RUN chmod +x /usr/local/bin/start.sh /usr/local/bin/start-cron.sh

CMD ["/usr/local/bin/start.sh"] 