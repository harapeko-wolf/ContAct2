<?php

namespace App\Http\Controllers\Api\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

trait AuthorizationTrait
{
    /**
     * 現在のユーザーが管理者かチェック
     */
    protected function isAdmin($user = null): bool
    {
        $user = $user ?: Auth::user();
        return $user && $user->isAdmin();
    }

    /**
     * 現在のユーザーが認証済みかチェック（JsonResponseを返す）
     */
    protected function ensureAuthenticated(): ?JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return $this->unauthorizedResponse();
        }
        return null;
    }

    /**
     * 管理者権限を要求（JsonResponseを返すか、nullで通す）
     */
    protected function requireAdmin($user = null): ?JsonResponse
    {
        $user = $user ?: Auth::user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        if (!$this->isAdmin($user)) {
            return $this->forbiddenResponse('管理者権限が必要です');
        }

        return null;
    }

    /**
     * ユーザーが会社にアクセス可能かチェック
     * 管理者は全ての会社にアクセス可能、一般ユーザーは所有する会社のみ
     */
    protected function canUserAccessCompany($company, $user = null): bool
    {
        $user = $user ?: Auth::user();

        if (!$user || !$company) {
            return false;
        }

        // 管理者は全ての会社にアクセス可能
        if ($this->isAdmin($user)) {
            return true;
        }

        // 一般ユーザーは自分が所有する会社のみアクセス可能
        return $company->user_id === $user->id;
    }

    /**
     * 会社アクセス権限をチェックし、権限がない場合はエラーレスポンスを返す
     */
    protected function ensureCompanyAccess($company, $user = null): ?JsonResponse
    {
        if (!$this->canUserAccessCompany($company, $user)) {
            return $this->notFoundResponse('リソースが見つかりませんでした');
        }

        return null;
    }

    /**
     * ユーザーが会社のドキュメントにアクセス可能かチェック
     * 管理者は全ての会社のドキュメントにアクセス可能、一般ユーザーは自分の会社のドキュメントのみ
     */
    protected function canUserAccessCompanyDocument($companyId, $user = null): bool
    {
        $user = $user ?: Auth::user();

        if (!$user) {
            return false;
        }

        // 管理者は全ての会社のドキュメントにアクセス可能
        if ($this->isAdmin($user)) {
            return true;
        }

        // 一般ユーザーは自分の会社のドキュメントのみアクセス可能
        return $companyId === $user->company_id;
    }

    /**
     * 会社のドキュメントアクセス権限をチェックし、権限がない場合はエラーレスポンスを返す
     */
    protected function ensureCompanyDocumentAccess($companyId, $user = null): ?JsonResponse
    {
        if (!$this->canUserAccessCompanyDocument($companyId, $user)) {
            return $this->forbiddenResponse();
        }

        return null;
    }

    /**
     * リソースの所有者チェック
     * 管理者は全リソースにアクセス可能、一般ユーザーは自分のリソースのみ
     */
    protected function canUserAccessResource($resourceUserId, $user = null): bool
    {
        $user = $user ?: Auth::user();

        if (!$user) {
            return false;
        }

        // 管理者は全リソースにアクセス可能
        if ($this->isAdmin($user)) {
            return true;
        }

        // 一般ユーザーは自分のリソースのみアクセス可能
        return $resourceUserId === $user->id;
    }

    /**
     * リソースアクセス権限をチェックし、権限がない場合はエラーレスポンスを返す
     */
    protected function ensureResourceAccess($resourceUserId, $user = null): ?JsonResponse
    {
        if (!$this->canUserAccessResource($resourceUserId, $user)) {
            return $this->notFoundResponse('リソースが見つかりませんでした');
        }

        return null;
    }

    /**
     * システム設定変更権限をチェック（管理者のみ）
     */
    protected function ensureSystemSettingsAccess($user = null): ?JsonResponse
    {
        return $this->requireAdmin($user);
    }
}
