# コントローラー肥大化改善タスク

## 📊 現状分析

### 問題のあるコントローラー
- **CompanyController**: 742行 - スコア計算、データ集約処理が混在
- **CompanyPdfController**: 324行 - ファイル管理、ストレージ操作が混在  
- **DocumentController**: 384行 - 文書管理、ログ記録、フィードバック処理が混在
- **AuthController**: 102行 - 比較的良好

---

## 🚀 改善タスク

### Phase 1: Service層の導入 (優先度: 高)

#### Task 1.1: CompanyScoreServiceの作成
- [ ] `app/Services/CompanyScoreService.php`を作成
- [ ] スコア計算ロジックをCompanyControllerから移動
  - [ ] `calculateCompanyScore()`
  - [ ] `calculateCompanyScoreBatch()`
  - [ ] `calculateSurveyScore()`
  - [ ] `calculateEngagementScore()`
  - [ ] `getScoringSettings()`
- [ ] 単体テストを作成: `tests/Unit/Services/CompanyScoreServiceTest.php`

#### Task 1.2: DocumentServiceの作成
- [ ] `app/Services/DocumentService.php`を作成
- [ ] ファイル操作ロジックを移動
  - [ ] `uploadDocument()`
  - [ ] `generatePreviewUrl()`
  - [ ] `generateDownloadUrl()`
  - [ ] `deleteDocument()`
- [ ] 単体テストを作成: `tests/Unit/Services/DocumentServiceTest.php`

#### Task 1.3: ViewLogServiceの作成
- [ ] `app/Services/ViewLogService.php`を作成
- [ ] 閲覧ログ処理を移動
  - [ ] `recordViewLog()`
  - [ ] `getViewStatistics()`
- [ ] 単体テストを作成: `tests/Unit/Services/ViewLogServiceTest.php`

#### Task 1.4: FeedbackServiceの作成
- [ ] `app/Services/FeedbackService.php`を作成
- [ ] フィードバック処理を移動
  - [ ] `submitFeedback()`
  - [ ] `getFeedbackStatistics()`
- [ ] 単体テストを作成: `tests/Unit/Services/FeedbackServiceTest.php`

---

### Phase 2: Repository層の導入 (優先度: 高)

#### Task 2.1: CompanyRepositoryの作成
- [ ] `app/Repositories/CompanyRepository.php`を作成
- [ ] 複雑なクエリ処理を移動
  - [ ] `getCompaniesWithScore()`
  - [ ] `getFeedbacksDataBatch()`
  - [ ] `getViewsDataBatch()`
- [ ] インターフェースを作成: `app/Repositories/Contracts/CompanyRepositoryInterface.php`
- [ ] ServiceProviderで依存性注入を設定

#### Task 2.2: DocumentRepositoryの作成
- [ ] `app/Repositories/DocumentRepository.php`を作成
- [ ] ドキュメント関連のクエリを移動
  - [ ] `getDocumentsWithViewStats()`
  - [ ] `getDocumentsByCompany()`
- [ ] インターフェースを作成: `app/Repositories/Contracts/DocumentRepositoryInterface.php`

---

### Phase 3: Form Request Classの導入 (優先度: 中)

#### Task 3.1: ドキュメント関連のRequest作成
- [ ] `app/Http/Requests/DocumentUploadRequest.php`
- [ ] `app/Http/Requests/DocumentUpdateRequest.php`
- [ ] `app/Http/Requests/ViewLogRequest.php`
- [ ] `app/Http/Requests/FeedbackSubmissionRequest.php`

#### Task 3.2: 会社関連のRequest作成
- [ ] `app/Http/Requests/CompanyCreateRequest.php`
- [ ] `app/Http/Requests/CompanyUpdateRequest.php`
- [ ] `app/Http/Requests/CompanyListRequest.php`

---

### Phase 4: Single Action Controllersへの分割 (優先度: 中)

#### Task 4.1: Company関連のAction分割
- [ ] `app/Http/Controllers/Api/Company/ListCompaniesAction.php`
- [ ] `app/Http/Controllers/Api/Company/ShowCompanyAction.php`
- [ ] `app/Http/Controllers/Api/Company/CreateCompanyAction.php`
- [ ] `app/Http/Controllers/Api/Company/UpdateCompanyAction.php`
- [ ] `app/Http/Controllers/Api/Company/DeleteCompanyAction.php`
- [ ] `app/Http/Controllers/Api/Company/GetCompanyScoreAction.php`

#### Task 4.2: Document関連のAction分割
- [ ] `app/Http/Controllers/Api/Document/UploadDocumentAction.php`
- [ ] `app/Http/Controllers/Api/Document/PreviewDocumentAction.php`
- [ ] `app/Http/Controllers/Api/Document/DownloadDocumentAction.php`
- [ ] `app/Http/Controllers/Api/Document/DeleteDocumentAction.php`
- [ ] `app/Http/Controllers/Api/Document/RecordViewLogAction.php`
- [ ] `app/Http/Controllers/Api/Document/SubmitFeedbackAction.php`

#### Task 4.3: ルーティングの更新
- [ ] `routes/api.php`を新しいAction Controllerに対応させる

---

### Phase 5: Resource Classの導入 (優先度: 中)

#### Task 5.1: Company関連のResource作成
- [ ] `app/Http/Resources/CompanyResource.php`
- [ ] `app/Http/Resources/CompanyScoreResource.php`
- [ ] `app/Http/Resources/CompanyCollectionResource.php`

#### Task 5.2: Document関連のResource作成
- [ ] `app/Http/Resources/DocumentResource.php`
- [ ] `app/Http/Resources/DocumentViewLogResource.php`
- [ ] `app/Http/Resources/DocumentFeedbackResource.php`

---

### Phase 6: Event/Listenerパターンの導入 (優先度: 低)

#### Task 6.1: Document関連のEvent/Listener
- [ ] `app/Events/DocumentUploaded.php`
- [ ] `app/Events/DocumentViewed.php`
- [ ] `app/Events/FeedbackSubmitted.php`
- [ ] `app/Listeners/ProcessDocumentMetadata.php`
- [ ] `app/Listeners/UpdateViewStatistics.php`
- [ ] `app/Listeners/SendFeedbackNotification.php`

#### Task 6.2: EventServiceProviderの更新
- [ ] `app/Providers/EventServiceProvider.php`にEvent/Listenerマッピングを追加

---

### Phase 7: 既存コントローラーのリファクタリング (優先度: 高)

#### Task 7.1: CompanyControllerのリファクタリング
- [ ] Service層を利用するように変更
- [ ] Repository層を利用するように変更
- [ ] Form Requestを利用するように変更
- [ ] Resourceクラスを利用するように変更
- [ ] コード行数を200行以下に削減

#### Task 7.2: CompanyPdfControllerのリファクタリング
- [ ] DocumentServiceを利用するように変更
- [ ] Form Requestを利用するように変更
- [ ] Resourceクラスを利用するように変更
- [ ] コード行数を150行以下に削減

#### Task 7.3: DocumentControllerのリファクタリング
- [ ] DocumentService、ViewLogService、FeedbackServiceを利用
- [ ] Form Requestを利用するように変更
- [ ] Resourceクラスを利用するように変更
- [ ] コード行数を200行以下に削減

---

### Phase 8: テストの追加・更新 (優先度: 高)

#### Task 8.1: 統合テストの更新
- [ ] `tests/Feature/CompanyTest.php`を新しい構造に対応
- [ ] `tests/Feature/DocumentTest.php`を新しい構造に対応
- [ ] `tests/Feature/AuthTest.php`を確認・更新

#### Task 8.2: 新しいテストの追加
- [ ] Service層のテスト
- [ ] Repository層のテスト
- [ ] Action Controllerのテスト
- [ ] Event/Listenerのテスト

---

## 📋 実装チェックリスト

### 準備作業
- [ ] ブランチ作成: `feature/controller-refactoring`
- [ ] 現在のテストが全て通ることを確認
- [ ] バックアップ用のタグ作成

### 実装順序
1. [ ] Phase 1: Service層の導入
2. [ ] Phase 2: Repository層の導入
3. [ ] Phase 7.1: CompanyControllerのリファクタリング（最も肥大化しているため）
4. [ ] Phase 3: Form Request Classの導入
5. [ ] Phase 5: Resource Classの導入
6. [ ] Phase 7.2, 7.3: 残りのコントローラーリファクタリング
7. [ ] Phase 4: Single Action Controllers（オプション）
8. [ ] Phase 6: Event/Listener（オプション）
9. [ ] Phase 8: テストの追加・更新

### 検証項目
- [ ] 全てのテストがパス
- [ ] API レスポンス形式が変わっていない
- [ ] パフォーマンスが劣化していない
- [ ] コード行数が目標値以下

---

## 🎯 成功指標

### コード品質
- [ ] CompanyController: 742行 → 200行以下
- [ ] CompanyPdfController: 324行 → 150行以下
- [ ] DocumentController: 384行 → 200行以下

### アーキテクチャ
- [ ] ビジネスロジックがService層に分離されている
- [ ] データアクセスがRepository層に分離されている
- [ ] バリデーションがForm Requestに分離されている
- [ ] レスポンス形成がResourceクラスに分離されている

### テスト
- [ ] サービス層のテストカバレッジ 90%以上
- [ ] コントローラーのテストカバレッジ 90%以上
- [ ] 統合テストが全てパス

---

## 🚨 注意事項

1. **段階的実装**: 一度に全てを変更せず、段階的にリファクタリング
2. **テスト駆動**: 各段階でテストを実行し、機能が壊れていないことを確認
3. **API互換性**: 既存のAPIレスポンス形式を維持
4. **パフォーマンス**: N+1問題など既存の最適化を維持
5. **ドキュメント更新**: API仕様書の更新も忘れずに

---

## 📅 推定工数

- Phase 1-2 (Service/Repository): 3-5日
- Phase 3 (Form Request): 1-2日  
- Phase 5 (Resource): 1-2日
- Phase 7 (リファクタリング): 2-3日
- Phase 8 (テスト): 2-3日

**合計: 9-15日** (1-3週間) 