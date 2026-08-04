<?php

namespace Tests\Feature\Teacher;

use App\Enums\AttendanceStatus;
use App\Enums\DayOfWeek;
use App\Enums\Grade;
use App\Enums\LessonStatus;
use App\Models\AttendanceRecord;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\LessonSession;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 教員向け出欠管理機能を確認するフィーチャーテスト。
 *
 * 授業実施生成、対象生徒表示、曜日検証、一括保存、他教員データ制御、一覧表示を検証する。
 */
final class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 教員が授業実施を生成し、対象生徒を表示できることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `teacher.attendance.edit`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、対象条件が真になることを確認する。
     */
    public function test_teacher_can_generate_session_and_view_target_students(): void
    {
        [$teacher, $slot, $students] = $this->attendanceContext();

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.attendance.edit', [
                'timetable_slot_id' => $slot->id,
                'lesson_date' => '2026-07-27',
            ]))
            ->assertOk()
            ->assertSeeText($students[0]->student_name)
            ->assertSeeText($students[1]->student_name);

        $lessonSessionExists = LessonSession::query()
            ->where(
                'timetable_slot_id',
                $slot->id,
            )
            ->whereDate(
                'lesson_date',
                '2026-07-27',
            )
            ->where(
                'status',
                LessonStatus::Scheduled->value,
            )
            ->where(
                'created_by',
                $teacher->user_id,
            )
            ->exists();

        $this->assertTrue(
            $lessonSessionExists,
            '授業実施日が想定どおり作成されていません。',
        );
    }

    /**
     * 授業実施日が時間割の曜日と一致する必要があることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `teacher.attendance.edit`へGETリクエストを送信する。
     * 期待結果: 不正入力に対するバリデーションエラーが返ることを確認する。
     */
    public function test_lesson_date_must_match_timetable_weekday(): void
    {
        [$teacher, $slot] = $this->attendanceContext();

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.attendance.edit', [
                'timetable_slot_id' => $slot->id,
                'lesson_date' => '2026-07-28',
            ]))
            ->assertSessionHasErrors('lesson_date');
    }

    /**
     * 教員が対象生徒全員の出欠を一括保存できることを確認する。
     *
     * 前提: 授業実施など、検証に必要なテストデータを準備する。
     * 処理: `teacher.attendance.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存される、関連レコード件数が期待どおりになる、取得値が期待値と一致することを確認する。
     */
    public function test_teacher_can_save_all_target_students_attendance(): void
    {
        [$teacher, $slot, $students] = $this->attendanceContext();

        $lessonSession = LessonSession::factory()->create([
            'timetable_slot_id' => $slot->id,
            'lesson_date' => '2026-07-27',
            'created_by' => $teacher->user_id,
            'status' => LessonStatus::Scheduled,
        ]);

        $this
            ->actingAs($teacher->user)
            ->put(route('teacher.attendance.update', $lessonSession), [
                'records' => [
                    [
                        'student_id' => $students[0]->id,
                        'attendance_status' => AttendanceStatus::Present->value,
                        'note' => null,
                    ],
                    [
                        'student_id' => $students[1]->id,
                        'attendance_status' => AttendanceStatus::Late->value,
                        'note' => '電車遅延',
                    ],
                ],
            ])
            ->assertRedirect(route('teacher.attendance.edit', [
                'timetable_slot_id' => $slot->id,
                'lesson_date' => '2026-07-27',
            ]));

        $this->assertDatabaseCount('attendance_records', 2);
        $this->assertDatabaseHas('attendance_records', [
            'lesson_session_id' => $lessonSession->id,
            'student_id' => $students[1]->id,
            'attendance_status' => AttendanceStatus::Late->value,
            'recorded_by' => $teacher->user_id,
            'note' => '電車遅延',
        ]);
        $this->assertSame(
            LessonStatus::Completed,
            $lessonSession->refresh()->status,
        );
    }

    /**
     * 教員が他教員の授業実施に対する出欠を更新できないことを確認する。
     *
     * 前提: 教員、授業実施など、検証に必要なテストデータを準備する。
     * 処理: `teacher.attendance.update`へPUTリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
    public function test_teacher_cannot_update_another_teachers_session(): void
    {
        $teacher = Teacher::factory()->create();
        [$otherTeacher, $slot, $students] = $this->attendanceContext();

        $lessonSession = LessonSession::factory()->create([
            'timetable_slot_id' => $slot->id,
            'lesson_date' => '2026-07-27',
            'created_by' => $otherTeacher->user_id,
        ]);

        $this
            ->actingAs($teacher->user)
            ->put(route('teacher.attendance.update', $lessonSession), [
                'records' => array_map(
                    static fn (Student $student): array => [
                        'student_id' => $student->id,
                        'attendance_status' => AttendanceStatus::Present->value,
                        'note' => null,
                    ],
                    $students,
                ),
            ])
            ->assertForbidden();
    }

    /**
     * 教員向け出欠一覧に生徒ごとの出欠記録が表示されることを確認する。
     *
     * 前提: 授業実施、出欠記録など、検証に必要なテストデータを準備する。
     * 処理: `teacher.attendance.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_teacher_attendance_list_shows_student_records(): void
    {
        [$teacher, $slot, $students] = $this->attendanceContext();
        $lessonSession = LessonSession::factory()->create([
            'timetable_slot_id' => $slot->id,
            'lesson_date' => '2026-07-27',
            'created_by' => $teacher->user_id,
            'status' => LessonStatus::Completed,
        ]);

        AttendanceRecord::factory()->create([
            'lesson_session_id' => $lessonSession->id,
            'student_id' => $students[0]->id,
            'recorded_by' => $teacher->user_id,
            'attendance_status' => AttendanceStatus::Present,
        ]);

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.attendance.index', [
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
            ]))
            ->assertOk()
            ->assertSeeText($students[0]->student_name)
            ->assertSeeText(AttendanceStatus::Present->label());
    }

    /**
     * 教員向け出欠テストに必要な担当授業、時間割、対象生徒を作成する。
     *
     * @return array{0: Teacher, 1: TimetableSlot, 2: list<Student>}
     */
    private function attendanceContext(): array
    {
        $teacher = Teacher::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
        ]);
        $slot = TimetableSlot::factory()->create([
            'course_id' => $course->id,
            'day_of_week' => DayOfWeek::Monday,
            'period_no' => 1,
        ]);
        $students = [
            Student::factory()->create([
                'class_group_id' => $classGroup->id,
                'grade' => Grade::First,
                'student_name' => '生徒A',
            ]),
            Student::factory()->create([
                'class_group_id' => $classGroup->id,
                'grade' => Grade::First,
                'student_name' => '生徒B',
            ]),
        ];

        return [$teacher, $slot, $students];
    }
}
