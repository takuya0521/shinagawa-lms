<?php

namespace App\Http\Requests\GoogleWorkspace;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Google Workspace画面で共通利用する入力正規化を提供する。
 */
abstract class GoogleWorkspaceRequest extends FormRequest
{
    /**
     * 認証・利用停止・ロール確認はルートMiddlewareで実施する。
     *
     * @return bool 常にtrue
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 指定入力を前後空白除去し、空文字をnullへ変換する。
     *
     * @param  string  $key  入力キー
     * @return string|null 正規化済み文字列
     */
    protected function nullableTrimmed(string $key): ?string
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * 指定入力を前後空白除去した文字列として返す。
     *
     * @param  string  $key  入力キー
     * @return string 正規化済み文字列
     */
    protected function trimmed(string $key): string
    {
        return trim((string) $this->input($key, ''));
    }
}
