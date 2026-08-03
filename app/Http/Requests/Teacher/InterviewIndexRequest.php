<?php

namespace App\Http\Requests\Teacher;

use App\Http\Requests\BaseInterviewIndexRequest;

/**
 * 教員向け面談履歴一覧の検索条件を検証する。
 */
final class InterviewIndexRequest extends BaseInterviewIndexRequest
{
    /**
     * 教員向け面談履歴検索へ適用する検証規則を返す。
     *
     * @return array<string, list<mixed>> 検証規則
     */
    public function rules(): array
    {
        return $this->commonRules();
    }
}
