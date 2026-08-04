<?php

namespace App\Http\Requests\Admin;

use App\Enums\AnnouncementNoticeType;
use App\Enums\AnnouncementStatus;
use App\Enums\AnnouncementTargetType;
use App\Enums\Grade;
use App\Enums\UserRole;
use App\Models\ClassGroup;
use App\Services\AnnouncementSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAnnouncementRequest extends FormRequest
{
    /**
     * このリクエストを実行できるか判定する。
     *
     * @return bool 判定結果
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 入力値へ適用する検証規則を返す。
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:50000'],
            'notice_type' => ['required', Rule::enum(AnnouncementNoticeType::class)],
            'is_important' => ['required', 'boolean'],
            'publish_start_at' => ['nullable', 'date_format:Y-m-d\\TH:i'],
            'publish_end_at' => [
                'nullable',
                'date_format:Y-m-d\\TH:i',
            ],
            'status' => ['required', Rule::enum(AnnouncementStatus::class)],
            'target_values' => ['required', 'array', 'min:1'],
            'target_values.*' => ['required', 'string', 'max:150', 'distinct'],
        ];
    }

    /**
     * 対象コードと実在データの整合性を追加検証する。
     */
    /**
     * 基本検証後に実行する追加検証処理を返す。
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $targets = $this->input('target_values', []);

                if (! is_array($targets)) {
                    return;
                }

                $publishStartAt = $this->input('publish_start_at');
                $publishEndAt = $this->input('publish_end_at');

                if (is_string($publishStartAt)
                    && $publishStartAt !== ''
                    && is_string($publishEndAt)
                    && $publishEndAt !== ''
                    && $publishEndAt < $publishStartAt) {
                    $validator->errors()->add(
                        'publish_end_at',
                        '掲載終了日時には、掲載開始日時以降の日時を指定してください。',
                    );
                }

                if (in_array(AnnouncementTargetType::All->value, $targets, true)
                    && count($targets) > 1) {
                    $validator->errors()->add(
                        'target_values',
                        '「全員」とその他の対象は同時に指定できません。',
                    );
                }

                foreach ($targets as $index => $target) {
                    if (! is_string($target)
                        || ! $this->targetExists($target)) {
                        $validator->errors()->add(
                            "target_values.{$index}",
                            '指定された公開対象が正しくありません。',
                        );
                    }
                }
            },
        ];
    }

    /**
     * お知らせへ保存する値を返す。
     *
     * @return array{title: string, body: string, notice_type: string, is_important: bool, publish_start_at: string|null, publish_end_at: string|null, status: string}
     */
    public function announcementAttributes(): array
    {
        $validated = $this->validated();

        $sanitizer = app(AnnouncementSanitizer::class);

        return [
            'title' => $sanitizer->sanitizePlainText(
                (string) $validated['title'],
            ),
            // 掲示板本文はHTMLを保存せず、表示時にもエスケープする。
            'body' => $sanitizer->sanitizePlainText(
                (string) $validated['body'],
            ),
            'notice_type' => (string) $validated['notice_type'],
            'is_important' => (bool) $validated['is_important'],
            'publish_start_at' => $this->nullableString(
                $validated,
                'publish_start_at',
            ),
            'publish_end_at' => $this->nullableString(
                $validated,
                'publish_end_at',
            ),
            'status' => (string) $validated['status'],
        ];
    }

    /**
     * お知らせ対象へ保存する値を返す。
     *
     * @return list<array{target_type: string, target_value: string|null}>
     */
    public function targetAttributes(): array
    {
        $validated = $this->validated();
        $targets = $validated['target_values'] ?? [];

        if (! is_array($targets)) {
            return [];
        }

        $attributes = [];

        foreach ($targets as $target) {
            if (! is_string($target)) {
                continue;
            }

            if ($target === AnnouncementTargetType::All->value) {
                $attributes[] = [
                    'target_type' => AnnouncementTargetType::All->value,
                    'target_value' => null,
                ];

                continue;
            }

            [$targetType, $targetValue] = array_pad(
                explode(':', $target, 2),
                2,
                null,
            );

            $attributes[] = [
                'target_type' => $targetType,
                'target_value' => $targetValue,
            ];
        }

        return $attributes;
    }

    /**
     * 検証エラーで使用する項目名を返す。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'タイトル',
            'body' => '本文',
            'notice_type' => 'お知らせ種別',
            'is_important' => '重要表示',
            'publish_start_at' => '掲載開始日時',
            'publish_end_at' => '掲載終了日時',
            'status' => '公開状態',
            'target_values' => '公開対象',
        ];
    }

    /**
     * 入力検証前にリクエスト値を正規化する。
     *
     * @return void 戻り値なし
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title', '')),
            'body' => trim((string) $this->input('body', '')),
            'is_important' => $this->boolean('is_important'),
            'publish_start_at' => $this->nullableTrimmed('publish_start_at'),
            'publish_end_at' => $this->nullableTrimmed('publish_end_at'),
        ]);
    }

    /**
     * 指定された公開対象が存在するか確認する。
     *
     * @param  string  $target  公開対象指定値
     * @return bool 判定結果
     */
    private function targetExists(string $target): bool
    {
        if ($target === AnnouncementTargetType::All->value) {
            return true;
        }

        [$targetType, $targetValue] = array_pad(
            explode(':', $target, 2),
            2,
            null,
        );

        if ($targetValue === null || $targetValue === '') {
            return false;
        }

        return match ($targetType) {
            AnnouncementTargetType::Role->value => UserRole::tryFrom($targetValue) !== null,
            AnnouncementTargetType::Grade->value => Grade::tryFrom($targetValue) !== null,
            AnnouncementTargetType::ClassGroup->value => ctype_digit($targetValue)
                && ClassGroup::query()->whereKey((int) $targetValue)->exists(),
            default => false,
        };
    }

    /**
     * 指定値を空文字を除外した文字列として取得する。
     *
     * @param  array<string, mixed>  $validated
     * @param  string  $key  取得対象のキー
     * @return ?string 取得した文字列。未指定時はnull
     */
    private function nullableString(
        array $validated,
        string $key,
    ): ?string {
        $value = $validated[$key] ?? null;

        return is_string($value) && $value !== ''
            ? $value
            : null;
    }

    /**
     * 指定値を前後の空白を除去した文字列として取得する。
     *
     * @param  string  $key  取得対象のキー
     * @return ?string 取得した文字列。未指定時はnull
     */
    private function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value !== '' ? $value : null;
    }
}
