# ContAct2

[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/harapeko-wolf/ContAct2)

## 開発ルール

### コードフォーマット
- インデント: 2スペース
- タブの使用: 禁止
- 改行コード: LF
- 1行の最大文字数: 100文字

### TypeScript設定
- 厳格な型チェックを有効化
- 暗黙的なany型を禁止
- nullチェックを厳格化
- 関数の型チェックを厳格化
- bind/call/applyの型チェックを厳格化
- プロパティの初期化チェックを厳格化
- 暗黙的なthisの使用を禁止
- 常に厳格モードを使用

### PHP設定
- PSR-12規約に準拠
- 厳格な型チェックを有効化

### Git設定
#### ブランチ命名規則
- 新機能開発: `feature/*`
- バグ修正: `fix/*`
- 緊急修正: `hotfix/*`
- リリース: `release/*`

#### コミットメッセージ
- 形式: `<type>(<scope>): <description>`
- タイプ:
  - feat: 新機能
  - fix: バグ修正
  - docs: ドキュメント
  - style: スタイル
  - refactor: リファクタリング
  - test: テスト
  - chore: その他
- 最大長: 72文字

### セキュリティ設定
- コード内でのシークレット情報の使用を禁止
- ハードコードされた認証情報を禁止

### ドキュメント設定
- READMEファイルを必須
- APIドキュメントを必須
- コードコメントを必須

### テスト設定
- テストを必須
- 最小テストカバレッジ: 80%

### 依存関係設定
- 開発依存関係の使用を許可
- バージョン指定を厳格化

## 開発環境のセットアップ

### 必要条件
- Docker
- Docker Compose

### 環境構築
```bash
# リポジトリのクローン
git clone https://github.com/your-username/ContAct2.git
cd ContAct2

# 環境変数の設定
cp .env.example .env

# Dockerコンテナの起動
docker compose up -d

# バックエンドの依存関係インストール
docker compose exec backend composer install

# フロントエンドの依存関係インストール
docker compose exec frontend npm install

# データベースのマイグレーション
docker compose exec backend php artisan migrate
```

## 開発サーバーの起動

### バックエンド（Laravel）
```bash
docker compose exec backend php artisan serve
```

### フロントエンド（Next.js）
```bash
docker compose exec frontend npm run dev
```

## アクセス情報

- フロントエンド: http://localhost:3000
- バックエンドAPI: http://localhost:80
- phpMyAdmin: http://localhost:8080
  - ユーザー名: contact2_user
  - パスワード: contact2_password

## その他のコマンド

### バックエンド
```bash
# データベースのマイグレーション
docker compose exec backend php artisan migrate

# データベースのシード
docker compose exec backend php artisan db:seed

# キャッシュのクリア
docker compose exec backend php artisan cache:clear
```

### フロントエンド
```bash
# ビルド
docker compose exec frontend npm run build

# リンターの実行
docker compose exec frontend npm run lint

# 型チェック
docker compose exec frontend npm run type-check
```

## コンテナ情報

- フロントエンド: Next.js (ポート3000)
- バックエンド: Laravel (ポート80)
- データベース: MySQL 8.4 (ポート3306)
- phpMyAdmin: (ポート8080)
- Nginx: (ポート80)