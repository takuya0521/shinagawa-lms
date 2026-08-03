<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\AssignCourseTeacherAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseTeacherAssignmentIndexRequest;
use App\Http\Requests\Admin\UpdateCourseTeacherAssignmentRequest;
use App\Models\Course;
use App\Models\User;
use App\Queries\Admin\CourseTeacherAssignmentIndexDataQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 担当教員設定画面の表示と更新を制御する。
 */
final class CourseTeacherAssignmentController extends Controller
{
    /**
     * 担当教員設定画面を表示する。
     *
     * @param CourseTeacherAssignmentIndexRequest $request 検証済み検索条件を含むリクエスト
     * @param CourseTeacherAssignmentIndexDataQuery $indexDataQuery 一覧画面の表示データ取得処理
     * @return View 担当教員設定画面
     */
    public function index(
        CourseTeacherAssignmentIndexRequest $request,
        CourseTeacherAssignmentIndexDataQuery $indexDataQuery,
    ): View {
        return view(
            'admin.course-teacher-assignments.index',
            $indexDataQuery
                ->execute($request->filters())
                ->toViewData(),
        );
    }

    /**
     * 授業の担当教員を設定または解除する。
     *
     * @param UpdateCourseTeacherAssignmentRequest $request 検証済みの担当教員と検索条件を含むリクエスト
     * @param Course $course 担当教員を変更する授業
     * @param AssignCourseTeacherAction $assignCourseTeacherAction 担当教員変更処理
     * @return RedirectResponse 処理結果を付与した一覧画面へのリダイレクト
     */
    public function update(
        UpdateCourseTeacherAssignmentRequest $request,
        Course $course,
        AssignCourseTeacherAction $assignCourseTeacherAction,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();

        $result = $assignCourseTeacherAction->execute(
            $course,
            $request->teacherId(),
            $actor,
            $request->ip(),
        );

        $redirect = redirect()->route(
            'admin.course-teacher-assignments.index',
            $request->redirectFilters(),
        );

        if (! $result->changed) {
            return $redirect->with(
                'status',
                '担当教員は変更されていません。',
            );
        }

        if ($result->isUnassigned()) {
            $redirect->with(
                'status',
                "「{$result->course->course_name}」の担当教員を解除しました。",
            );

            if ($result->hasPendingWork()) {
                $redirect->with(
                    'warning',
                    sprintf(
                        '担当解除した授業に未完了の授業実施日が%d件、下書き評価が%d件あります。後任への引継ぎを確認してください。',
                        $result->pendingLessonSessions,
                        $result->draftEvaluations,
                    ),
                );
            }

            return $redirect;
        }

        return $redirect->with(
            'status',
            sprintf(
                '「%s」の担当教員を「%s」に設定しました。',
                $result->course->course_name,
                $result->course->teacher->user->name,
            ),
        );
    }
}
