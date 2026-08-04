<?php

namespace Tests\Feature;

use App\Models\InterviewRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 面談記録モデルの関連を確認するテスト。
 *
 * 面談記録と生徒・教員・ユーザー間のEloquentリレーションを検証する。
 */
final class InterviewModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 面談記録モデルのリレーションと属性キャストが利用できることを確認する。
     *
     * 前提: 面談記録など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 取得値が期待値と一致する、対象値が設定されることを確認する。
     */
    public function test_interview_record_relations_and_casts_are_available(): void
    {
        $interviewRecord = InterviewRecord::factory()->create([
            'interview_date' => '2026-07-29',
        ]);

        $this->assertNotNull($interviewRecord->student);
        $this->assertNotNull($interviewRecord->teacher);
        $this->assertNotNull($interviewRecord->creator);
        $this->assertSame(
            '2026-07-29',
            $interviewRecord->interview_date->format('Y-m-d'),
        );
    }

    /**
     * 生徒・教員・ユーザーから面談記録へ関連付けられることを確認する。
     *
     * 前提: 面談記録など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 対象条件が真になることを確認する。
     */
    public function test_student_teacher_and_user_have_interview_relations(): void
    {
        $interviewRecord = InterviewRecord::factory()->create();

        $this->assertTrue(
            $interviewRecord->student
                ->interviewRecords()
                ->whereKey($interviewRecord->id)
                ->exists(),
        );

        $this->assertTrue(
            $interviewRecord->teacher
                ?->interviewRecords()
                ->whereKey($interviewRecord->id)
                ->exists() ?? false,
        );

        $this->assertTrue(
            $interviewRecord->creator
                ->createdInterviewRecords()
                ->whereKey($interviewRecord->id)
                ->exists(),
        );
    }
}
