# Dockerコマンド一覧

プロジェクトのルート（`docker-compose.yml`があるディレクトリ）で実行することを想定しています。

---

## 基本操作

| コマンド | 説明 |
|---|---|
| `docker-compose up -d --build` | コンテナをビルドしてバックグラウンドで起動（初回・構成変更時） |
| `docker-compose up` | コンテナをフォアグラウンドで起動（ログをそのまま表示） |
| `docker-compose down` | コンテナを停止・削除（ボリュームは残る） |
| `docker-compose restart` | コンテナを再起動 |
| `docker-compose ps` | 起動中のコンテナ一覧を表示 |
| `docker-compose logs` | すべてのサービスのログを表示 |
| `docker-compose logs サービス名` | 指定サービス（例：backend, frontend, mysqlなど）のログを表示 |

## バックエンド（Laravel）関連

| コマンド | 説明 |
|---|---|
| `docker-compose exec backend bash` | backendコンテナにシェルで入る |
| `docker-compose exec backend php artisan migrate` | マイグレーション実行 |
| `docker-compose exec backend php artisan migrate:fresh --seed` | 全テーブル再作成＋シーディング |
| `docker-compose exec backend php artisan db:seed` | シーディングのみ実行 |
| `docker-compose exec backend composer install` | PHP依存パッケージのインストール |
| `docker-compose exec backend php artisan` | artisanコマンドのヘルプ表示 |

## フロントエンド（Next.js）関連

| コマンド | 説明 |
|---|---|
| `docker-compose exec frontend bash` | frontendコンテナにシェルで入る |
| `docker-compose exec frontend npm install` | Node依存パッケージのインストール |
| `docker-compose exec frontend npm run dev` | Next.js開発サーバー起動（通常は自動起動） |

## データベース・phpMyAdmin

| コマンド | 説明 |
|---|---|
| `docker-compose exec mysql bash` | MySQLコンテナにシェルで入る |
| `docker-compose exec mysql mysql -u root -p` | MySQLにrootで接続（パスワード入力必要） |
| `http://localhost:8080` | phpMyAdminにブラウザでアクセス |

## その他

| コマンド | 説明 |
|---|---|
| `docker-compose build` | イメージのみビルド（起動しない） |
| `docker-compose stop` | コンテナを停止（削除はしない） |
| `docker-compose rm` | 停止中のコンテナを削除 |

---