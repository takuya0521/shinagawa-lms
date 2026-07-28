<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MasterStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TeacherIndexRequest;
use App\Queries\Admin\TeacherListQuery;
use Illuminate\Contracts\View\View;

final class TeacherController extends Controller
{
    /**
     * 管理者向け教員一覧を表示する。
     */
    public function index(
        TeacherIndexRequest $request,
        TeacherListQuery $query,
    ): View {
        $teachers = $query->execute(
            keyword: $request->keyword(),
            teacherStatus: $request->teacherStatus(),
            accountStatus: $request->accountStatus(),
        );

        return view('admin.teachers.index', [
            'teachers' => $teachers,
            'teacherStatuses' => MasterStatus::cases(),
            'accountStatuses' => UserStatus::cases(),
        ]);
    }
}
