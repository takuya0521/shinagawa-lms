<?php

namespace Tests\Feature\Admin;

use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 管理者向け授業管理機能を確認するフィーチャーテスト。
 *
 * 画面表示、登録・更新、検索・絞り込み、マスタ選択制御、URL検証、権限制御を検証する。
 */
final class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者が授業一覧を閲覧できることを確認する。
     *
     * 前提: 授業など、検証に必要なテストデータを準備する。
     * 処理: `admin.courses.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_admin_can_view_course_list(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $course = Course::factory()->create([
            'course_name' => '数学Ⅰ',
            'academic_year' => 2026,
            'grade' => Grade::First,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.courses.index'))
            ->assertOk()
            ->assertSeeText(
                $course->course_name,
            )
            ->assertSeeText(
                $course->subject->subject_name,
            )
            ->assertSeeText(
                $course->classGroup->class_name,
            )
            ->assertSeeText(
                Grade::First->label(),
            );
    }

    /**
     * 管理者が授業登録画面を表示できることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `admin.courses.create`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
    public function test_admin_can_view_course_create_page(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $this
            ->actingAs($admin)
            ->get(route('admin.courses.create'))
            ->assertOk()
            ->assertSeeText('授業登録')
            ->assertSeeText('授業名')
            ->assertSeeText('担当教員');
    }

    /**
     * 管理者が授業編集画面を表示できることを確認する。
     *
     * 前提: 授業など、検証に必要なテストデータを準備する。
     * 処理: `admin.courses.edit`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、必要な内容がレスポンスに含まれることを確認する。
     */
    public function test_admin_can_view_course_edit_page(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $course = Course::factory()->create([
            'course_name' => '編集対象授業',
        ]);

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.courses.edit',
                    $course,
                ),
            )
            ->assertOk()
            ->assertSeeText('授業編集')
            ->assertSee($course->course_name);
    }

    /**
     * 管理者が授業を登録できることを確認する。
     *
     * 前提: 科目、クラス、教員など、検証に必要なテストデータを準備する。
     * 処理: `admin.courses.store`へPOSTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_admin_can_create_course(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $subject = Subject::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        $teacher = Teacher::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(
                route('admin.courses.store'),
                $this->coursePayload(
                    $subject,
                    $classGroup,
                    $teacher,
                    [
                        'course_name' => ' 数学Ⅰ ',
                        'google_classroom_url' => ' https://classroom.google.com/c/example ',
                        'google_classroom_id' => ' CLASSROOM-001 ',
                    ],
                ),
            );

        $response
            ->assertRedirect(
                route('admin.courses.index'),
            )
            ->assertSessionHas(
                'status',
                '授業を登録しました。',
            );

        $this->assertDatabaseHas('courses', [
            'academic_year' => 2026,
            'grade' => Grade::First->value,
            'class_group_id' => $classGroup->id,
            'subject_id' => $subject->id,
            'course_name' => '数学Ⅰ',
            'teacher_id' => $teacher->id,
            'google_classroom_url' => 'https://classroom.google.com/c/example',
            'google_classroom_id' => 'CLASSROOM-001',
            'status' => MasterStatus::Active->value,
        ]);
    }

    /**
     * 同一対象の授業を重複登録できないことを確認する。
     *
     * 前提: 授業など、検証に必要なテストデータを準備する。
     * 処理: `admin.courses.store`へPOSTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、不正入力に対するバリデーションエラーが返ることを確認する。
     */
    public function test_duplicate_course_cannot_be_created(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $course = Course::factory()->create([
            'academic_year' => 2026,
            'grade' => Grade::First,
            'course_name' => '数学Ⅰ',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.courses.create'))
            ->post(route('admin.courses.store'), [
                'academic_year' => $course->academic_year,
                'grade' => $course->grade->value,
                'class_group_id' => $course->class_group_id,
                'subject_id' => $course->subject_id,
                'course_name' => $course->course_name,
                'teacher_id' => null,
                'google_classroom_url' => null,
                'google_classroom_id' => null,
                'status' => MasterStatus::Active->value,
            ]);

        $response
            ->assertRedirect(
                route('admin.courses.create'),
            )
            ->assertSessionHasErrors(
                'course_name',
            );
    }

    /**
     * 授業登録時に無効な科目を選択できないことを確認する。
     *
     * 前提: 科目、クラスなど、検証に必要なテストデータを準備する。
     * 処理: `admin.courses.store`へPOSTリクエストを送信する。
     * 期待結果: 不正入力に対するバリデーションエラーが返ることを確認する。
     */
    public function test_inactive_subject_cannot_be_selected_when_creating_course(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $subject = Subject::factory()->create([
            'status' => MasterStatus::Inactive,
        ]);

        $classGroup = ClassGroup::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.courses.create'))
            ->post(
                route('admin.courses.store'),
                $this->coursePayload(
                    $subject,
                    $classGroup,
                ),
            );

        $response->assertSessionHasErrors(
            'subject_id',
        );
    }

    /**
     * 授業登録時に無効なクラスを選択できないことを確認する。
     *
     * 前提: 科目、クラスなど、検証に必要なテストデータを準備する。
     * 処理: `admin.courses.store`へPOSTリクエストを送信する。
     * 期待結果: 不正入力に対するバリデーションエラーが返ることを確認する。
     */
    public function test_inactive_class_group_cannot_be_selected_when_creating_course(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $subject = Subject::factory()->create();
        $classGroup = ClassGroup::factory()->create([
            'status' => MasterStatus::Inactive,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.courses.create'))
            ->post(
                route('admin.courses.store'),
                $this->coursePayload(
                    $subject,
                    $classGroup,
                ),
            );

        $response->assertSessionHasErrors(
            'class_group_id',
        );
    }

    /**
     * 授業登録時に無効な教員を選択できないことを確認する。
     *
     * 前提: 科目、クラス、教員など、検証に必要なテストデータを準備する。
     * 処理: `admin.courses.store`へPOSTリクエストを送信する。
     * 期待結果: 不正入力に対するバリデーションエラーが返ることを確認する。
     */
    public function test_inactive_teacher_cannot_be_selected_when_creating_course(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $subject = Subject::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        $teacher = Teacher::factory()->create([
            'status' => MasterStatus::Inactive,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.courses.create'))
            ->post(
                route('admin.courses.store'),
                $this->coursePayload(
                    $subject,
                    $classGroup,
                    $teacher,
                ),
            );

        $response->assertSessionHasErrors(
            'teacher_id',
        );
    }

    /**
     * 授業登録時に利用停止中の教員を選択できないことを確認する。
     *
     * 前提: 科目、クラス、教員など、検証に必要なテストデータを準備する。
     * 処理: `admin.courses.store`へPOSTリクエストを送信する。
     * 期待結果: 不正入力に対するバリデーションエラーが返ることを確認する。
     */
    public function test_suspended_teacher_cannot_be_selected_when_creating_course(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $subject = Subject::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        $teacher = Teacher::factory()->create();

        $teacher->user->update([
            'status' => UserStatus::Suspended,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.courses.create'))
            ->post(
                route('admin.courses.store'),
                $this->coursePayload(
                    $subject,
                    $classGroup,
                    $teacher,
                ),
            );

        $response->assertSessionHasErrors(
            'teacher_id',
        );
    }

    /**
     * 授業登録時に論理削除済みの教員を選択できないことを確認する。
     *
     * 前提: 科目、クラス、教員など、検証に必要なテストデータを準備する。
     * 処理: `admin.courses.store`へPOSTリクエストを送信する。
     * 期待結果: 不正入力に対するバリデーションエラーが返ることを確認する。
     */
    public function test_soft_deleted_teacher_cannot_be_selected_when_creating_course(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $subject = Subject::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        $teacher = Teacher::factory()->create();

        $teacher->delete();

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.courses.create'))
            ->post(
                route('admin.courses.store'),
                $this->coursePayload(
                    $subject,
                    $classGroup,
                    $teacher,
                ),
            );

        $response->assertSessionHasErrors(
            'teacher_id',
        );
    }

    /**
     * Google Classroom以外の不正なURLを授業へ登録できないことを確認する。
     *
     * 前提: 科目、クラスなど、検証に必要なテストデータを準備する。
     * 処理: `admin.courses.store`へPOSTリクエストを送信する。
     * 期待結果: 不正入力に対するバリデーションエラーが返ることを確認する。
     */
    public function test_invalid_google_classroom_url_cannot_be_registered(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $subject = Subject::factory()->create();
        $classGroup = ClassGroup::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.courses.create'))
            ->post(
                route('admin.courses.store'),
                $this->coursePayload(
                    $subject,
                    $classGroup,
                    null,
                    [
                        'google_classroom_url' => 'not-a-url',
                    ],
                ),
            );

        $response->assertSessionHasErrors(
            'google_classroom_url',
        );
    }

    /**
     * 管理者が授業情報を更新できることを確認する。
     *
     * 前提: 授業など、検証に必要なテストデータを準備する。
     * 処理: `admin.courses.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_admin_can_update_course(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $course = Course::factory()->create([
            'course_name' => '数学Ⅰ',
            'status' => MasterStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(
                route(
                    'admin.courses.update',
                    $course,
                ),
                [
                    'academic_year' => 2027,
                    'grade' => Grade::Second->value,
                    'class_group_id' => $course->class_group_id,
                    'subject_id' => $course->subject_id,
                    'course_name' => '数学Ⅱ',
                    'teacher_id' => null,
                    'google_classroom_url' => 'https://classroom.google.com/c/updated',
                    'google_classroom_id' => 'CLASSROOM-UPDATED',
                    'status' => MasterStatus::Inactive->value,
                ],
            );

        $response
            ->assertRedirect(
                route('admin.courses.index'),
            )
            ->assertSessionHas(
                'status',
                '授業を更新しました。',
            );

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'academic_year' => 2027,
            'grade' => Grade::Second->value,
            'course_name' => '数学Ⅱ',
            'teacher_id' => null,
            'google_classroom_id' => 'CLASSROOM-UPDATED',
            'status' => MasterStatus::Inactive->value,
        ]);
    }

    /**
     * 授業更新時は現在設定中の無効マスタを保持できることを確認する。
     *
     * 前提: 科目、クラス、教員、授業など、検証に必要なテストデータを準備する。
     * 処理: `admin.courses.update`へPUTリクエストを送信する。
     * 期待結果: 想定した画面へリダイレクトされる、データベースに期待する内容が保存されることを確認する。
     */
    public function test_current_inactive_masters_can_be_retained_when_updating(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $subject = Subject::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        $teacher = Teacher::factory()->create();

        $course = Course::factory()->create([
            'subject_id' => $subject->id,
            'class_group_id' => $classGroup->id,
            'teacher_id' => $teacher->id,
            'course_name' => '既存授業',
        ]);

        $subject->update([
            'status' => MasterStatus::Inactive,
        ]);

        $classGroup->update([
            'status' => MasterStatus::Inactive,
        ]);

        $teacher->update([
            'status' => MasterStatus::Inactive,
        ]);

        $teacher->user->update([
            'status' => UserStatus::Suspended,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(
                route(
                    'admin.courses.update',
                    $course,
                ),
                [
                    'academic_year' => $course->academic_year,
                    'grade' => $course->grade->value,
                    'class_group_id' => $classGroup->id,
                    'subject_id' => $subject->id,
                    'course_name' => '既存授業更新',
                    'teacher_id' => $teacher->id,
                    'google_classroom_url' => null,
                    'google_classroom_id' => null,
                    'status' => MasterStatus::Active->value,
                ],
            );

        $response->assertRedirect(
            route('admin.courses.index'),
        );

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'subject_id' => $subject->id,
            'class_group_id' => $classGroup->id,
            'teacher_id' => $teacher->id,
            'course_name' => '既存授業更新',
        ]);
    }

    /**
     * 管理者がキーワードで授業を検索できることを確認する。
     *
     * 前提: 授業など、検証に必要なテストデータを準備する。
     * 処理: `admin.courses.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_admin_can_search_courses_by_keyword(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $matchingCourse = Course::factory()->create([
            'course_name' => '検索対象数学',
        ]);

        $otherCourse = Course::factory()->create([
            'course_name' => '英語表現',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.courses.index', [
                'keyword' => '検索対象数学',
            ]));

        $response
            ->assertOk()
            ->assertSeeText(
                $matchingCourse->course_name,
            )
            ->assertDontSeeText(
                $otherCourse->course_name,
            );
    }

    /**
     * 管理者が年度・学年・クラス等で授業を絞り込めることを確認する。
     *
     * 前提: クラス、授業など、検証に必要なテストデータを準備する。
     * 処理: `admin.courses.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_admin_can_filter_courses(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $targetClassGroup = ClassGroup::factory()->create();
        $otherClassGroup = ClassGroup::factory()->create();

        $matchingCourse = Course::factory()->create([
            'academic_year' => 2026,
            'grade' => Grade::First,
            'class_group_id' => $targetClassGroup->id,
            'course_name' => '対象授業',
            'status' => MasterStatus::Inactive,
        ]);

        $otherCourse = Course::factory()->create([
            'academic_year' => 2027,
            'grade' => Grade::Second,
            'class_group_id' => $otherClassGroup->id,
            'course_name' => '対象外授業',
            'status' => MasterStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.courses.index', [
                'academic_year' => 2026,
                'grade' => Grade::First->value,
                'class_group_id' => $targetClassGroup->id,
                'status' => MasterStatus::Inactive->value,
            ]));

        $response
            ->assertOk()
            ->assertSeeText(
                $matchingCourse->course_name,
            )
            ->assertDontSeeText(
                $otherCourse->course_name,
            );
    }

    /**
     * 教員が授業管理機能を利用できないことを確認する。
     *
     * 前提: 授業など、検証に必要なテストデータを準備する。
     * 処理: `admin.courses.index`へGETリクエスト、`admin.courses.create`へGETリクエスト、`admin.courses.store`へPOSTリクエスト、関連する後続リクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
    public function test_teacher_cannot_manage_courses(): void
    {
        $teacherUser = $this->createUser(
            UserRole::Teacher,
        );

        $course = Course::factory()->create();

        $payload = [
            'academic_year' => $course->academic_year,
            'grade' => $course->grade->value,
            'class_group_id' => $course->class_group_id,
            'subject_id' => $course->subject_id,
            'course_name' => '権限外授業',
            'teacher_id' => null,
            'google_classroom_url' => null,
            'google_classroom_id' => null,
            'status' => MasterStatus::Active->value,
        ];

        $this
            ->actingAs($teacherUser)
            ->get(route('admin.courses.index'))
            ->assertForbidden();

        $this
            ->actingAs($teacherUser)
            ->get(route('admin.courses.create'))
            ->assertForbidden();

        $this
            ->actingAs($teacherUser)
            ->post(route('admin.courses.store'), $payload)
            ->assertForbidden();

        $this
            ->actingAs($teacherUser)
            ->get(
                route(
                    'admin.courses.edit',
                    $course,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($teacherUser)
            ->put(
                route(
                    'admin.courses.update',
                    $course,
                ),
                $payload,
            )
            ->assertForbidden();
    }

    /**
     * 授業登録・更新リクエストで使用する標準入力値を組み立てて返す。
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function coursePayload(
        Subject $subject,
        ClassGroup $classGroup,
        ?Teacher $teacher = null,
        array $overrides = [],
    ): array {
        return [
            'academic_year' => 2026,
            'grade' => Grade::First->value,
            'class_group_id' => $classGroup->id,
            'subject_id' => $subject->id,
            'course_name' => '数学Ⅰ',
            'teacher_id' => $teacher?->id,
            'google_classroom_url' => null,
            'google_classroom_id' => null,
            'status' => MasterStatus::Active->value,
            ...$overrides,
        ];
    }

    /**
     * 指定したロールと状態を持つテスト用ユーザーを作成して返す。
     */
    private function createUser(
        UserRole $role,
    ): User {
        return User::factory()->create([
            'role' => $role,
            'status' => UserStatus::Active,
        ]);
    }
}
