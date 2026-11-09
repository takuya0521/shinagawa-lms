<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** パスワードで使える文字をログイン・登録・変更で統一する。 */
final class PrintableAsciiPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 既存ログインと同じ範囲。半角スペースは許可し、制御文字は拒否する。
        if (! is_string($value) || preg_match('/[^\x20-\x7E]/', $value) === 1) {
            $fail('パスワードは半角文字で入力してください');
        }
    }
}
