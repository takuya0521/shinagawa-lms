<?php

namespace App\Http\Requests\GoogleWorkspace;

use Closure;
use Illuminate\Validation\Rule;

/**
 * Google Chatスペースの作成・更新入力を検証する。
 */
final class ChatSpaceRequest extends GoogleWorkspaceRequest
{
    /**
     * Chatスペース保存時の入力規則を返す。
     *
     * @return array<string, list<mixed>> 入力規則
     */
    public function rules(): array
    {
        $createsSpace = $this->isMethod('POST');

        return [
            'space_type' => [
                Rule::requiredIf($createsSpace),
                'nullable',
                Rule::in(['SPACE', 'GROUP_CHAT', 'DIRECT_MESSAGE']),
            ],
            'display_name' => [
                Rule::requiredIf(! $createsSpace || $this->input('space_type', 'SPACE') === 'SPACE'),
                'nullable',
                'string',
                'max:128',
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'members' => [
                'nullable',
                'string',
                'max:10000',
                function (string $attribute, mixed $value, Closure $fail) use ($createsSpace): void {
                    if (! $createsSpace || ! is_string($value)) {
                        return;
                    }

                    $emails = $this->parseMemberEmails($value);
                    $parts = preg_split('/[\s,;]+/u', trim($value));
                    $enteredValues = is_array($parts)
                        ? array_values(array_filter(array_map('trim', $parts)))
                        : [];

                    if (count($emails) !== count(array_unique($enteredValues))) {
                        $fail('初期メンバーには正しいメールアドレスを指定してください。');

                        return;
                    }

                    $type = (string) $this->input('space_type', 'SPACE');
                    $requiredCount = $type === 'DIRECT_MESSAGE' ? 1 : ($type === 'GROUP_CHAT' ? 2 : 0);

                    if (count($emails) < $requiredCount) {
                        $fail($type === 'DIRECT_MESSAGE'
                            ? 'ダイレクトメッセージには相手のメールアドレスを1件指定してください。'
                            : 'グループチャットには自分以外のメールアドレスを2件以上指定してください。');

                        return;
                    }

                    if ($type === 'DIRECT_MESSAGE' && count($emails) !== 1) {
                        $fail('ダイレクトメッセージに指定できる相手は1件だけです。');
                    }

                    if (count($emails) > 49) {
                        $fail('初期メンバーは49件以内で指定してください。');
                    }
                },
            ],
        ];
    }

    /**
     * スペース名を返す。
     *
     * @return string スペース名
     */
    public function displayName(): string
    {
        return $this->nullableTrimmed('display_name') ?? '';
    }

    /**
     * 作成するChat会話種別を返す。
     *
     * @return string SPACE、GROUP_CHAT、DIRECT_MESSAGEのいずれか
     */
    public function spaceType(): string
    {
        return (string) $this->validated('space_type', 'SPACE');
    }

    /**
     * 初期メンバーとして指定されたメールアドレス一覧を返す。
     *
     * @return list<string> 重複を除いたメールアドレス一覧
     */
    public function memberEmails(): array
    {
        return $this->parseMemberEmails((string) $this->input('members', ''));
    }

    /**
     * スペース説明を返す。
     *
     * @return string|null スペース説明
     */
    public function description(): ?string
    {
        return $this->nullableTrimmed('description');
    }

    /**
     * 改行・カンマ・セミコロン区切りの入力をメールアドレス一覧へ変換する。
     *
     * @param  string  $value  メンバー入力
     * @return list<string> 正しい形式のメールアドレス一覧
     */
    private function parseMemberEmails(string $value): array
    {
        $parts = preg_split('/[\s,;]+/u', $value);

        if (! is_array($parts)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map('trim', $parts),
            static fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
        )));
    }
}
