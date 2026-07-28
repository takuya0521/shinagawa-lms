<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MasterStatus;
use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudentIndexRequest;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Queries\Admin\StudentListQuery;
use Illuminate\Contracts\View\View;

final class StudentController extends Controller
{
    /**
     * 管理者向け生徒一覧を表示する。
     */
    public function index(
        StudentIndexRequest $request,
        StudentListQuery $query,
    ): View {
        $students = $query->execute(
            keyword: $request->keyword(),
            grade: $request->grade(),
            affiliation: $request->affiliation(),
            classGroupId: $request->classGroupId(),
            status: $request->status(),
        );

        $classGroups = ClassGroup::query()
            ->where('status', MasterStatus::Active->value)
            ->orderBy('class_code')
            ->get();

        return view('admin.students.index', [
            'students' => $students,
            'classGroups' => $classGroups,
            'statuses' => StudentStatus::cases(),
        ]);
    }

    /**
     * 管理者向け生徒詳細を表示する。
     */
    public function show(Student $student): View
    {
        $student->loadMissing([
            'user',
            'classGroup',
        ]);

        return view('admin.students.show', [
            'student' => $student,
        ]);
    }
}
