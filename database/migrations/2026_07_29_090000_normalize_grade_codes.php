<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 生徒と授業の既存学年値を共通コードへ統一する。
     */
    public function up(): void
    {
        $gradeMap = [
            '1' => '1',
            '1年' => '1',
            '高1' => '1',
            '高校1年' => '1',
            '１' => '1',
            '１年' => '1',
            '2' => '2',
            '2年' => '2',
            '高2' => '2',
            '高校2年' => '2',
            '２' => '2',
            '２年' => '2',
            '3' => '3',
            '3年' => '3',
            '高3' => '3',
            '高校3年' => '3',
            '３' => '3',
            '３年' => '3',
        ];

        /** @var list<array{table: string, id: int, grade: string}> $updates */
        $updates = [];

        foreach (['students', 'courses'] as $table) {
            $records = DB::table($table)
                ->select([
                    'id',
                    'grade',
                ])
                ->orderBy('id')
                ->get();

            foreach ($records as $record) {
                $currentGrade = trim(
                    (string) $record->grade,
                );

                $normalizedGrade = $gradeMap[$currentGrade]
                    ?? null;

                if ($normalizedGrade === null) {
                    throw new RuntimeException(
                        sprintf(
                            '%sテーブルのID %dに未対応の学年「%s」が登録されています。1・2・3のいずれかへ修正してください。',
                            $table,
                            (int) $record->id,
                            $currentGrade,
                        ),
                    );
                }

                if ($currentGrade === $normalizedGrade) {
                    continue;
                }

                $updates[] = [
                    'table' => $table,
                    'id' => (int) $record->id,
                    'grade' => $normalizedGrade,
                ];
            }
        }

        foreach ($updates as $update) {
            DB::table($update['table'])
                ->where('id', $update['id'])
                ->update([
                    'grade' => $update['grade'],
                ]);
        }
    }

    /**
     * 学年コードの正規化は元の表記へ戻せないため処理しない。
     */
    public function down(): void
    {
        // 正規化前の表記は保持していないため、ロールバック対象外とする。
    }
};
