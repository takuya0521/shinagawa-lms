<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateTimetableSlotAction;
use App\Actions\Admin\UpdateTimetableSlotAction;
use App\Enums\DayOfWeek;
use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTimetableSlotRequest;
use App\Http\Requests\Admin\UpdateTimetableSlotRequest;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Queries\Admin\TimetableSlotFormDataQuery;
use App\Queries\Admin\TimetableSlotIndexDataQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理者向け時間割管理画面を制御する。
 */
final class TimetableSlotController extends Controller
{
    /**
     * 時間割一覧を表示する。
     *
     * @param  Request  $request  検索条件を含むHTTPリクエスト
     * @param  TimetableSlotIndexDataQuery  $timetableSlotIndexDataQuery  一覧画面表示データの検索処理
     * @return View 時間割一覧画面
     */
    public function index(
        Request $request,
        TimetableSlotIndexDataQuery $timetableSlotIndexDataQuery,
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
        $dayOfWeek = DayOfWeek::tryFrom(
            $request->integer('day_of_week'),
        );
        $status = MasterStatus::tryFrom(
            (string) $request->query('status', ''),
        );

        $data = $timetableSlotIndexDataQuery->execute(
            keyword: $keyword,
            academicYear: $academicYear,
            grade: $grade,
            classGroupId: $classGroupId,
            dayOfWeek: $dayOfWeek,
            status: $status,
        );

        return view('admin.timetable-slots.index', $data->toViewData());
    }

    /**
     * 時間割登録画面を表示する。
     *
     * @param  Request  $request  初期選択値を含むHTTPリクエスト
     * @param  TimetableSlotFormDataQuery  $timetableSlotFormDataQuery  フォーム選択肢の検索処理
     * @return View 時間割登録画面
     */
    public function create(
        Request $request,
        TimetableSlotFormDataQuery $timetableSlotFormDataQuery,
    ): View {
        $preferredCourseId = $this->positiveIntegerOrNull(
            $request->integer('course_id'),
        );
        $preferredDayOfWeek = DayOfWeek::tryFrom(
            $request->integer('day_of_week'),
        );
        $preferredPeriodNo = $request->integer('period_no');

        if ($preferredPeriodNo < 1 || $preferredPeriodNo > 6) {
            $preferredPeriodNo = null;
        }

        $data = $timetableSlotFormDataQuery->execute(
            preferredCourseId: $preferredCourseId,
            preferredDayOfWeek: $preferredDayOfWeek,
            preferredPeriodNo: $preferredPeriodNo,
        );

        return view('admin.timetable-slots.create', $data->toViewData());
    }

    /**
     * 時間割枠を登録する。
     *
     * @param  StoreTimetableSlotRequest  $request  検証済みの時間割入力
     * @param  CreateTimetableSlotAction  $createTimetableSlotAction  時間割登録処理
     * @return RedirectResponse 時間割一覧へのリダイレクト
     */
    public function store(
        StoreTimetableSlotRequest $request,
        CreateTimetableSlotAction $createTimetableSlotAction,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $createTimetableSlotAction->execute(
            attributes: $request->validated(),
            actor: $actor,
            ipAddress: $request->ip(),
        );

        return redirect()
            ->route('admin.timetable-slots.index')
            ->with('status', '時間割を登録しました。');
    }

    /**
     * 時間割編集画面を表示する。
     *
     * @param  TimetableSlot  $timetableSlot  編集対象の時間割枠
     * @param  TimetableSlotFormDataQuery  $timetableSlotFormDataQuery  フォーム選択肢の検索処理
     * @return View 時間割編集画面
     */
    public function edit(
        TimetableSlot $timetableSlot,
        TimetableSlotFormDataQuery $timetableSlotFormDataQuery,
    ): View {
        $timetableSlot->load([
            'course.subject',
            'course.classGroup',
            'course.teacher.user',
        ]);
        $data = $timetableSlotFormDataQuery->execute($timetableSlot);

        return view('admin.timetable-slots.edit', [
            ...$data->toViewData(),
            'timetableSlot' => $timetableSlot,
        ]);
    }

    /**
     * 時間割枠を更新する。
     *
     * @param  UpdateTimetableSlotRequest  $request  検証済みの時間割入力
     * @param  TimetableSlot  $timetableSlot  更新対象の時間割枠
     * @param  UpdateTimetableSlotAction  $updateTimetableSlotAction  時間割更新処理
     * @return RedirectResponse 時間割一覧へのリダイレクト
     */
    public function update(
        UpdateTimetableSlotRequest $request,
        TimetableSlot $timetableSlot,
        UpdateTimetableSlotAction $updateTimetableSlotAction,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $updateTimetableSlotAction->execute(
            timetableSlot: $timetableSlot,
            attributes: $request->validated(),
            actor: $actor,
            ipAddress: $request->ip(),
        );

        return redirect()
            ->route('admin.timetable-slots.index')
            ->with('status', '時間割を更新しました。');
    }

    /**
     * 正の整数だけを検索条件または初期値として採用する。
     *
     * @param  int  $value  入力された整数値
     * @return int|null 正の整数。0以下の場合はnull
     */
    private function positiveIntegerOrNull(int $value): ?int
    {
        return $value > 0 ? $value : null;
    }
}
