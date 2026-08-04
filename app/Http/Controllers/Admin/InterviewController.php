<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Interview\CreateInterviewRecordAction;
use App\Actions\Interview\UpdateInterviewRecordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InterviewIndexRequest;
use App\Http\Requests\Admin\StoreInterviewRequest;
use App\Http\Requests\Admin\UpdateInterviewRequest;
use App\Models\InterviewRecord;
use App\Models\User;
use App\Queries\Admin\InterviewFormDataQuery;
use App\Queries\Admin\InterviewRecordListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理者向け面談記録画面の表示と登録・更新を制御する。
 */
final class InterviewController extends Controller
{
    /**
     * 全生徒の面談記録一覧を表示する。
     *
     * @param  InterviewIndexRequest  $request  検証済み検索条件を含むリクエスト
     * @param  InterviewRecordListQuery  $interviewRecordListQuery  面談記録一覧の検索処理
     * @param  InterviewFormDataQuery  $formDataQuery  面談記録フォームの表示データ取得処理
     * @return View 面談記録一覧画面
     */
    public function index(
        InterviewIndexRequest $request,
        InterviewRecordListQuery $interviewRecordListQuery,
        InterviewFormDataQuery $formDataQuery,
    ): View {
        return view('admin.interviews.index', [
            'interviewRecords' => $interviewRecordListQuery->execute(
                $request->dateFrom(),
                $request->dateTo(),
                $request->keyword(),
                $request->nullableId('student_id'),
                $request->nullableId('teacher_id'),
                $request->interviewType(),
            ),
            ...$formDataQuery->execute()->toViewData(),
            'dateFrom' => $request->dateFrom(),
            'dateTo' => $request->dateTo(),
            'keyword' => $request->keyword(),
            'selectedStudentId' => $request->nullableId('student_id'),
            'selectedTeacherId' => $request->nullableId('teacher_id'),
            'selectedInterviewType' => $request->interviewType(),
        ]);
    }

    /**
     * 管理者向け面談記録登録画面を表示する。
     *
     * @param  Request  $request  初期選択する生徒IDと教員IDを含むリクエスト
     * @param  InterviewFormDataQuery  $formDataQuery  面談記録フォームの表示データ取得処理
     * @return View 面談記録登録画面
     */
    public function create(
        Request $request,
        InterviewFormDataQuery $formDataQuery,
    ): View {
        $selectedStudentId = $request->integer('student_id');
        $selectedTeacherId = $request->integer('teacher_id');

        return view('admin.interviews.create', [
            ...$formDataQuery->execute()->toViewData(),
            'interviewRecord' => new InterviewRecord,
            'selectedStudentId' => $selectedStudentId > 0
                ? $selectedStudentId
                : null,
            'selectedTeacherId' => $selectedTeacherId > 0
                ? $selectedTeacherId
                : null,
        ]);
    }

    /**
     * 管理者として面談記録を登録する。
     *
     * @param  StoreInterviewRequest  $request  検証済み面談記録を含むリクエスト
     * @param  CreateInterviewRecordAction  $createInterviewRecordAction  面談記録登録処理
     * @return RedirectResponse 登録後の面談記録編集画面へのリダイレクト
     */
    public function store(
        StoreInterviewRequest $request,
        CreateInterviewRecordAction $createInterviewRecordAction,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $interviewRecord = $createInterviewRecordAction->execute(
            $request->interviewAttributes(),
            $user,
            $request->ip(),
        );

        return redirect()
            ->route('admin.interviews.edit', $interviewRecord)
            ->with('success', '面談記録を登録しました。');
    }

    /**
     * 管理者向け面談記録詳細・編集画面を表示する。
     *
     * @param  InterviewRecord  $interviewRecord  編集対象の面談記録
     * @param  InterviewFormDataQuery  $formDataQuery  面談記録フォームの表示データ取得処理
     * @return View 面談記録編集画面
     */
    public function edit(
        InterviewRecord $interviewRecord,
        InterviewFormDataQuery $formDataQuery,
    ): View {
        $interviewRecord->loadMissing([
            'student.classGroup',
            'teacher.user',
            'creator',
            'updater',
        ]);

        return view('admin.interviews.edit', [
            ...$formDataQuery->execute()->toViewData(),
            'interviewRecord' => $interviewRecord,
            'selectedStudentId' => $interviewRecord->student_id,
            'selectedTeacherId' => $interviewRecord->teacher_id,
        ]);
    }

    /**
     * 管理者として面談記録を更新する。
     *
     * @param  UpdateInterviewRequest  $request  検証済み面談記録を含むリクエスト
     * @param  InterviewRecord  $interviewRecord  更新対象の面談記録
     * @param  UpdateInterviewRecordAction  $updateInterviewRecordAction  面談記録更新処理
     * @return RedirectResponse 更新後の面談記録編集画面へのリダイレクト
     */
    public function update(
        UpdateInterviewRequest $request,
        InterviewRecord $interviewRecord,
        UpdateInterviewRecordAction $updateInterviewRecordAction,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $updateInterviewRecordAction->execute(
            $interviewRecord,
            $request->interviewAttributes(),
            $user,
            $request->ip(),
        );

        return redirect()
            ->route('admin.interviews.edit', $interviewRecord)
            ->with('success', '面談記録を更新しました。');
    }
}
