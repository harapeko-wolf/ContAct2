# 環境分離設計ドキュメント（ステージング / 本番）

## 🛍️ 概要

本プロジェクト（MAツール）はプロトタイプ段階にあり、無料枠のAWS上でステージング環境と本番環境を**同一インフラ内で論理的に分離**して運用する。将来的な本番移行も見捨え、最低限の設計分離を行う。

---

## ✅ 分離の目的

| 項目       | 説明                  |
| -------- | ------------------- |
| 安全性の確保   | 誤操作による本番データ破壊リスクの踝減 |
| 開発効率の向上  | テストと本番リリースを明確に区別    |
| 将来拡張の容易化 | CI/CDや品質保証の導入が容易になる |

---

## 🏗️ 分離構成（同一AWSリソース内）

| リソース           | 分離方法                            | 備考                                     |
| -------------- | ------------------------------- | -------------------------------------- |
| RDS (MySQL)    | テーブル名に `stg_` / `prod_` を接頭辞に使用 | 例：`stg_users`, `prod_users`            |
| S3 バケット        | フォルダ単位で分離                       | 例：`s3://contact2-documents/stg/`       |
| Laravel 環境     | `.env.stg` / `.env.prod` を切り替え  | GitHub Actions で環境変数を設定                |
| GitHub Actions | `STAGE` 変数で処理分岐                 | ステージングと本番でデプロイ先を分ける                    |
| ドメイン構成         | サブドメインで分離                       | 例：`stg.example.com`, `www.example.com` |

---

## 🛠️ `.env` 分離サンプル

```env
# .env.stg
APP_ENV=staging
APP_URL=https://stg.example.com
DB_DATABASE=contact2
DB_TABLE_PREFIX=stg_
S3_BUCKET_PATH=stg/

# .env.prod
APP_ENV=production
APP_URL=https://www.example.com
DB_DATABASE=contact2
DB_TABLE_PREFIX=prod_
S3_BUCKET_PATH=prod/
```

---

## 🚀 GitHub Actions 設定例

```yaml
jobs:
  deploy:
    runs-on: ubuntu-latest
    env:
      STAGE: stg # または prod
    steps:
      - name: Checkout
        uses: actions/checkout@v3

      - name: Set ENV file
        run: cp .env.${{ env.STAGE }} .env

      - name: Build Docker image
        run: docker build -t contact2-app .

      - name: Login to ECR
        uses: aws-actions/amazon-ecr-login@v1

      - name: Push to ECR
        run: |
          docker tag contact2-app:latest ${{ secrets.ECR_REPO_URL }}:latest
          docker push ${{ secrets.ECR_REPO_URL }}:latest

      - name: Deploy to ECS
        run: ./deploy.sh ${{ env.STAGE }}
```

---

## 🧹 ドメイン設計例（Route 53）

| ドメイン              | 用途       |
| ----------------- | -------- |
| `stg.example.com` | ステージング環境 |
| `www.example.com` | 本番環境     |

---

## 💡 補足：分離しないリソース（無料枠のため）

| リソース     | 分離しない理由             |
| -------- | ------------------- |
| VPC      | 無料枠節約のため、共用で運用      |
| ECS クラスタ | タスク定義名で論理的に分離して運用可能 |

---

## 🚀 将来の本番移行時の推奨アクション

* RDS を別インスタンスに分離（例：`t3.small` 以上）
* ECS サービスを完全に分割、Auto Scaling 対応
* CloudFront を本番用に再構成（キャッシュやSSL対応）
* ACM 証明書・Route 53 による独立ドメイン管理


