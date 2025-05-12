FROM node:20-alpine

WORKDIR /app

# 依存関係のインストール
COPY frontend/package*.json ./
RUN npm install

# アプリケーションのコピー
COPY frontend/ .

# テスト環境の設定
ENV NODE_ENV=test

# テスト実行用のエントリーポイント
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["npm", "run", "dev"] 