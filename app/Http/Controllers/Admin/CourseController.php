<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateCourseAction;
use App\Actions\Admin\UpdateCourseAction;
use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Course;
use App\Models\User;
use App\Queries\Admin\CourseFormDataQuery;
use App\Queries\Admin\CourseIndexDataQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理者向け授業管理画面を制御する。
 */
final class CourseController extends Controller
{
    /**
     * 授業一覧を表示する。
     *
     * @param Request $request 検索条件を含むHTTPリクエスト
     * @param CourseIndexDataQuery $courseIndexDataQuery 一覧画面表示データの検索処理
     * @return View 授業一覧画面
     */
    public function index(
        Request $request,
        CourseIndexDataQuery $courseIndexDataQuery,
    ): View {
        $keyword = trim((string) $request->query('keyword', ''));
        $academicYear = $this->positiveIntegerOrNull(
            $request->integer('academic_year'),
        );
        $grade = Grade::tryFrom(
            trim((string) $request->query('grade', '')),
        );
        $classGroupId = $this->positiveIntegerOrNull(
            $request->integer('class_group_id'),
        );
        $status = MasterStatus::tryFrom(
            (string) $request->query('status', ''),
        );

        $data = $courseIndexDataQuery->execute(
            keyword: $keyword,
            academicYear: $academicYear,
            grade: $grade,
            classGroupId: $classGroupId,
            status: $status,
        );

        return view('admin.courses.index', $data->toViewData());
    }

    /**
     * 授業登録画面を表示する。
     *
     * @param CourseFormDataQuery $courseFormDataQuery フォーム選択肢の検索処理
     * @return View 授業登録画面
     */
    public function create(
        CourseFormDataQuery $courseFormDataQuery,
    ): View {
        return view(
            'admin.courses.create',
            $courseFormDataQuery->execute()->toViewData(),
        );
    }

    /**
     * 授業を登録する。
     *
     * @param StoreCourseRequest $request 検証済みの授業入力
     * @param CreateCourseAction $createCourseAction 授業登録処理
     * @return RedirectResponse 授業一覧へのリダイレクト
     */
    public function store(
        StoreCourseRequest $request,
        CreateCourseAction $createCourseAction,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $createCourseAction->execute(
            attributes: $request->validated(),
            actor: $actor,
            ipAddress: $request->ip(),
        );

        return redirect()
            ->route('admin.courses.index')
            ->with('status', '授業を登録しました。');
    }

    /**
     * 授業編集画面を表示する。
     *
     * @param Course $course 編集対象の授業
     * @param CourseFormDataQuery $courseFormDataQuery フォーム選択肢の検索処理
     * @return View 授業編集画面
     */
    public function edit(
        Course $course,
        CourseFormDataQuery $courseFormDataQuery,
    ): View {
        $course->load([
            'subject',
            'classGroup',
            'teacher.user',
        ]);

        return view('admin.courses.edit', [
            ...$courseFormDataQuery->execute($course)->toViewData(),
            'course' => $course,
        ]);
    }

    /**
     * 授業を更新する。
     *
     * @param UpdateCourseRequest $request 検証済みの授業入力
     * @param Course $course 更新対象の授業
     * @param UpdateCourseAction $updateCourseAction 授業更新処理
     * @return RedirectResponse 授業一覧へのリダイレクト
     */
    public function update(
        UpdateCourseRequest $request,
        Course $course,
        UpdateCourseAction $updateCourseAction,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $updateCourseAction->execute(
            course: $course,
            attributes: $request->validated(),
            actor: $actor,
            ipAddress: $request->ip(),
        );

        return redirect()
            ->route('admin.courses.index')
            ->with('status', '授業を更新しました。');
    }

    /**
     * 正の整数だけを検索条件として採用する。
     *
     * @param int $value 入力された整数値
     * @return int|null 正の整数。0以下の場合はnull
     */
    private function positiveIntegerOrNull(int $value): ?int
    {
        return $value > 0 ? $value : null;
    }
}
