<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\LessonStatus;
use App\Models\AttendanceRecord;
use App\Models\LessonSession;
use App\Models\Student;
use App\Models\TimetableSlot;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 授業実施・出欠記録モデルの関連と制約を確認するテスト。
 *
 * Eloquentリレーション、型変換、授業日・生徒単位の一意制約を検証する。
 */
final class AttendanceModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 授業実施モデルのリレーションと属性キャストが利用できることを確認する。
     *
     * 前提: 時間割枠、ユーザー、授業実施など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 取得値が期待値と一致する、対象条件が真になることを確認する。
     */
    public function test_lesson_session_relationships_and_casts_are_available(): void
    {
        $slot = TimetableSlot::factory()->create();
        $creator = User::factory()->create();

        $lessonSession = LessonSession::factory()->create([
            'timetable_slot_id' => $slot->id,
            'created_by' => $creator->id,
            'lesson_date' => '2026-07-27',
            'status' => LessonStatus::Completed,
        ]);

        $this->assertTrue($lessonSession->timetableSlot->is($slot));
        $this->assertTrue($lessonSession->creator->is($creator));
        $this->assertSame('2026-07-27', $lessonSession->lesson_date->format('Y-m-d'));
        $this->assertSame(LessonStatus::Completed, $lessonSession->status);
    }

    /**
     * 出欠記録モデルのリレーションと属性キャストが利用できることを確認する。
     *
     * 前提: 授業実施、生徒、ユーザー、出欠記録など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 取得値が期待値と一致する、対象条件が真になることを確認する。
     */
    public function test_attendance_record_relationships_and_casts_are_available(): void
    {
        $lessonSession = LessonSession::factory()->create();
        $student = Student::factory()->create();
        $recorder = User::factory()->create();
        $corrector = User::factory()->create();

        $record = AttendanceRecord::factory()->create([
            'lesson_session_id' => $lessonSession->id,
            'student_id' => $student->id,
            'recorded_by' => $recorder->id,
            'corrected_by' => $corrector->id,
            'attendance_status' => AttendanceStatus::Late,
        ]);

        $this->assertTrue($record->lessonSession->is($lessonSession));
        $this->assertTrue($record->student->is($student));
        $this->assertTrue($record->recorder->is($recorder));
        $this->assertTrue($record->corrector->is($corrector));
        $this->assertSame(AttendanceStatus::Late, $record->attendance_status);
    }

    /**
     * 同じ時間割枠・実施日に授業実施を重複登録できないことを確認する。
     *
     * 前提: 授業実施など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 想定した例外または制約違反が発生することを確認する。
     */
    public function test_same_slot_and_date_cannot_create_duplicate_lesson_session(): void
    {
        $lessonSession = LessonSession::factory()->create([
            'lesson_date' => '2026-07-27',
        ]);

        $this->expectException(QueryException::class);

        LessonSession::factory()->create([
            'timetable_slot_id' => $lessonSession->timetable_slot_id,
            'lesson_date' => '2026-07-27',
        ]);
    }

    /**
     * 同じ授業実施・生徒に出欠記録を重複登録できないことを確認する。
     *
     * 前提: 出欠記録など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 想定した例外または制約違反が発生することを確認する。
     */
    public function test_same_session_and_student_cannot_create_duplicate_attendance_record(): void
    {
        $record = AttendanceRecord::factory()->create();

        $this->expectException(QueryException::class);

        AttendanceRecord::factory()->create([
            'lesson_session_id' => $record->lesson_session_id,
            'student_id' => $record->student_id,
        ]);
    }
}
