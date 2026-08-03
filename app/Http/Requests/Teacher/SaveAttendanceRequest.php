<?php

namespace App\Http\Requests\Teacher;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveAttendanceRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', 'distinct', Rule::exists('students', 'id')],
            'records.*.attendance_status' => ['required', Rule::enum(AttendanceStatus::class)],
            'records.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * 検証済みの出欠入力行を取得する。
     *
     * @return list<array{student_id: int, attendance_status: string, note: string|null}>
     */
    public function records(): array
    {
        $records = $this->validated('records');

        if (! is_array($records)) {
            return [];
        }

        return array_values(array_map(
            static function (mixed $record): array {
                $row = is_array($record) ? $record : [];
                $note = $row['note'] ?? null;

                return [
                    'student_id' => (int) ($row['student_id'] ?? 0),
                    'attendance_status' => (string) ($row['attendance_status'] ?? ''),
                    'note' => is_string($note) && $note !== '' ? $note : null,
                ];
            },
            $records,
        ));
    }

    /**
     * 入力検証前にリクエスト値を正規化する。
     *
     * @return void 戻り値なし
     */
    protected function prepareForValidation(): void
    {
        $records = $this->input('records');

        if (! is_array($records)) {
            return;
        }

        foreach ($records as $index => $record) {
            if (! is_array($record)) {
                continue;
            }

            $note = $record['note'] ?? null;

            if (is_string($note)) {
                $records[$index]['note'] = trim($note) !== '' ? trim($note) : null;
            }
        }

        $this->merge(['records' => $records]);
    }
}
