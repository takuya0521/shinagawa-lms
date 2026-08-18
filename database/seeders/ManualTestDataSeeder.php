<?php

namespace Database\Seeders;

use App\Enums\AnnouncementNoticeType;
use App\Enums\AnnouncementStatus;
use App\Enums\AnnouncementTargetType;
use App\Enums\AttendanceStatus;
use App\Enums\DayOfWeek;
use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use App\Enums\ExternalLinkScopeType;
use App\Enums\ExternalLinkType;
use App\Enums\Grade;
use App\Enums\LessonStatus;
use App\Enums\MasterStatus;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\ExternalLink;
use App\Models\FinalEvaluation;
use App\Models\InterviewRecord;
use App\Models\LessonSession;
use App\Models\OperationLog;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Support\AcademicYear;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * LMSの手動テストで使用するQA専用データを登録するSeeder。
 */
final class ManualTestDataSeeder extends Seeder
{
    private const PASSWORD = 'TestPass2026!';

    /**
     * 公式HPの公開情報とテスト仕様書に沿ったQAデータ一式を登録する。
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(
                '手動テストデータはlocalまたはtesting環境でのみ投入できます。',
            );
        }

        $academicYear = AcademicYear::forDate(now());

        $this->cleanupManualCourseData();
        $this->seedClassGroups();
        $this->seedSubjects();
        $this->seedUsersAndProfiles();
        $this->cleanupLegacyManualTestData();
        $courses = $this->seedCourses($academicYear);
        $slots = $this->seedTimetable($courses);
        $this->seedLessonsAndAttendance($slots);
        $this->seedEvaluations($courses, $academicYear);
        $this->seedInterviews();
        $this->seedAnnouncements();
        $this->seedExternalLinks($courses);
        $this->seedOperationLogs();
    }

    /**
     * 手動テストSeederが作成した授業関連データを安全に作り直せる状態へ戻す。
     */
    private function cleanupManualCourseData(): void
    {
        $courseIds = Course::withTrashed()
            ->where('google_classroom_id', 'like', 'QA-%')
            ->pluck('id');

        if ($courseIds->isEmpty()) {
            return;
        }

        $slotIds = TimetableSlot::query()
            ->whereIn('course_id', $courseIds)
            ->pluck('id');
        $lessonSessionIds = LessonSession::query()
            ->whereIn('timetable_slot_id', $slotIds)
            ->pluck('id');

        AttendanceRecord::query()
            ->whereIn('lesson_session_id', $lessonSessionIds)
            ->delete();
        LessonSession::query()
            ->whereIn('id', $lessonSessionIds)
            ->delete();
        TimetableSlot::query()
            ->whereIn('id', $slotIds)
            ->delete();
        FinalEvaluation::query()
            ->whereIn('course_id', $courseIds)
            ->delete();
        ExternalLink::query()
            ->where('scope_type', ExternalLinkScopeType::Course->value)
            ->whereIn('scope_id', $courseIds)
            ->delete();
        Course::withTrashed()
            ->whereIn('id', $courseIds)
            ->forceDelete();
    }

    /**
     * 公式HPの学年別・午前午後2部制に合わせたクラスを登録する。
     */
    private function seedClassGroups(): void
    {
        foreach ([
            ['1A', '1年Aクラス', '午前の部 10:00〜13:10 / 基礎力養成期', MasterStatus::Active],
            ['2A', '2年Aクラス', '午前の部 10:00〜13:10 / 実践力強化期', MasterStatus::Active],
            ['3A', '3年Aクラス', '午前の部 10:00〜13:10 / 進路実現期', MasterStatus::Active],
            ['1B', '1年Bクラス', '午後の部 13:30〜16:40 / 基礎力養成期', MasterStatus::Active],
            ['2B', '2年Bクラス', '午後の部 13:30〜16:40 / 実践力強化期', MasterStatus::Active],
            ['3B', '3年Bクラス', '午後の部 13:30〜16:40 / 進路実現期', MasterStatus::Active],
            ['QA-CLOSED', '無効クラス（QA）', '無効マスタ表示確認用', MasterStatus::Inactive],
        ] as [$code, $name, $description, $status]) {
            ClassGroup::query()->updateOrCreate(
                ['class_code' => $code],
                [
                    'class_name' => $name,
                    'description' => $description,
                    'status' => $status,
                ],
            );
        }
    }

    /**
     * 公式時間割の科目区分と状態確認用科目を登録する。
     */
    private function seedSubjects(): void
    {
        foreach ([
            ['ENG', '英語科目', MasterStatus::Active],
            ['AI', 'AI', MasterStatus::Active],
            ['CAREER', 'キャリア教育', MasterStatus::Active],
            ['HR', 'HR・振り返り', MasterStatus::Active],
            ['QA_INACTIVE', '無効科目（QA）', MasterStatus::Inactive],
        ] as [$code, $name, $status]) {
            Subject::query()->updateOrCreate(
                ['subject_code' => $code],
                [
                    'subject_name' => $name,
                    'status' => $status,
                ],
            );
        }

        Subject::query()
            ->where('subject_code', 'FINANCE')
            ->where('subject_name', '金融教育（キャリア教育に統合）')
            ->whereDoesntHave('courses')
            ->delete();
    }

    /**
     * ロール・状態・表示条件を確認するQAユーザーとプロフィールを登録する。
     */
    private function seedUsersAndProfiles(): void
    {
        $this->upsertUser('qa.admin@shinagawahs.test', 'QA 管理者A', UserRole::Admin, UserStatus::Active);
        $this->upsertUser('qa.admin2@shinagawahs.test', 'QA 管理者B', UserRole::Admin, UserStatus::Active);
        $this->upsertUser('qa.csv@shinagawahs.test', '=1+1', UserRole::Admin, UserStatus::Suspended);

        $teachers = [
            [
                'qa.teacher.english@shinagawahs.test',
                '英語教育 担当教員',
                '英語4技能、会話、旅行、動画制作、プレゼンテーション',
                UserStatus::Active,
                MasterStatus::Active,
            ],
            [
                'qa.teacher.ai@shinagawahs.test',
                'AIリテラシー 担当教員',
                'AI回答の検証、プロンプト作成、プレゼンテーション、探究',
                UserStatus::Active,
                MasterStatus::Active,
            ],
            [
                'qa.teacher.career@shinagawahs.test',
                'キャリア教育 担当教員',
                '自己理解、社会理解、金融教育、進路設計、探究',
                UserStatus::Active,
                MasterStatus::Active,
            ],
            [
                'qa.teacher.2a@shinagawahs.test',
                '2年Aクラス 担当教員（QA）',
                '2年Aクラスの手動テスト用担当',
                UserStatus::Active,
                MasterStatus::Active,
            ],
            [
                'qa.teacher.3a@shinagawahs.test',
                '3年Aクラス 担当教員（QA）',
                '3年Aクラスの手動テスト用担当',
                UserStatus::Active,
                MasterStatus::Active,
            ],
            [
                'qa.teacher.1b@shinagawahs.test',
                '1年Bクラス 担当教員（QA）',
                '1年Bクラスの手動テスト用担当',
                UserStatus::Active,
                MasterStatus::Active,
            ],
            [
                'qa.teacher.2b@shinagawahs.test',
                '2年Bクラス 担当教員（QA）',
                '2年Bクラスの手動テスト用担当',
                UserStatus::Active,
                MasterStatus::Active,
            ],
            [
                'qa.teacher.3b@shinagawahs.test',
                '3年Bクラス 担当教員（QA）',
                '3年Bクラスの手動テスト用担当',
                UserStatus::Active,
                MasterStatus::Active,
            ],
            [
                'qa.teacher.inactive@shinagawahs.test',
                '無効教員 QA',
                '無効マスタ表示確認用',
                UserStatus::Active,
                MasterStatus::Inactive,
            ],
            [
                'qa.teacher.suspended@shinagawahs.test',
                '利用停止教員 QA',
                '利用停止ユーザー確認用',
                UserStatus::Suspended,
                MasterStatus::Active,
            ],
        ];

        foreach ($teachers as [$email, $name, $subjectNotes, $userStatus, $teacherStatus]) {
            $user = $this->upsertUser($email, $name, UserRole::Teacher, $userStatus);
            $teacher = Teacher::withTrashed()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'subject_notes' => $subjectNotes,
                    'status' => $teacherStatus,
                ],
            );
            $teacher->restore();
        }

        $studentNames = [
            '青木 結衣', '石川 湊', '上田 ひなた', '遠藤 蒼',
            '岡田 凛', '加藤 陽菜', '木村 蓮', '小林 美月',
            '佐々木 悠真', '鈴木 葵', '高橋 陽翔', '田中 結菜',
            '中村 颯太', '西村 彩花', '橋本 樹', '林 心春',
            '藤田 大和', '松本 莉子', '三浦 陽向', '山口 紬',
            '吉田 朝陽', '渡辺 咲良',
            '長文表示確認用とても長い氏名のテスト生徒', '状態確認用 生徒',
        ];
        $partners = [
            '山梨学院高等学校',
            '日本航空学園',
            '八洲学園大学国際高等学校',
            null,
        ];
        $targets = [
            [Grade::First, '1A'],
            [Grade::First, '1B'],
            [Grade::Second, '2A'],
            [Grade::Second, '2B'],
            [Grade::Third, '3A'],
            [Grade::Third, '3B'],
        ];

        foreach ($studentNames as $index => $studentName) {
            $studentNumber = $index + 1;
            [$grade, $classCode] = $targets[intdiv($index, 4)];
            $userStatus = $index === 23 ? UserStatus::Suspended : UserStatus::Active;
            $studentStatus = match ($index) {
                21 => StudentStatus::Graduated,
                22 => StudentStatus::Withdrawn,
                23 => StudentStatus::Suspended,
                default => StudentStatus::Active,
            };
            $email = sprintf('qa.student%02d@shinagawahs.test', $studentNumber);
            $user = $this->upsertUser($email, $studentName, UserRole::Student, $userStatus);
            $classGroup = ClassGroup::query()->where('class_code', $classCode)->firstOrFail();
            $student = Student::withTrashed()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'student_no' => sprintf('QA-%d-%03d', $grade->value, $studentNumber),
                    'student_name' => $studentName,
                    'grade' => $grade,
                    'affiliation' => '品川高等学院',
                    'partner_school' => $partners[$index % count($partners)],
                    'class_group_id' => $classGroup->id,
                    'status' => $studentStatus,
                ],
            );
            $student->restore();
        }
    }

    /**
     * 現行シナリオと競合する旧手動テストデータを除去する。
     */
    private function cleanupLegacyManualTestData(): void
    {
        ExternalLink::query()
            ->whereIn('link_name', [
                '午前クラス共有資料',
                '英語教育 Classroom',
                '1年Aクラス共有資料',
                '基礎英語 Classroom',
            ])
            ->delete();

        Announcement::withTrashed()
            ->whereIn('title', [
                '午前クラス 探究発表・作品制作の準備',
                '午後クラス 個別面談日程の確認',
                '1年Aクラス 探究発表・作品制作の準備',
                '1年Bクラス 個別面談日程の確認',
            ])
            ->forceDelete();

        ClassGroup::query()
            ->whereIn('class_code', ['QA-AM', 'QA-PM'])
            ->whereDoesntHave('students')
            ->whereDoesntHave('courses')
            ->delete();
    }

    /**
     * 公式HPの曜日別時間割に登場する授業を現年度・前年度へ登録する。
     *
     * @return Collection<string, Course> 登録した授業
     */
    private function seedCourses(int $academicYear): Collection
    {
        $subjects = Subject::query()
            ->whereIn('subject_code', ['ENG', 'AI', 'CAREER', 'HR'])
            ->get()
            ->keyBy('subject_code');
        $classGroups = ClassGroup::query()
            ->whereIn('class_code', ['1A', '2A', '3A', '1B', '2B', '3B'])
            ->get()
            ->keyBy('class_code');
        $teachers = collect([
            '1A:ENG' => $this->teacherByEmail('qa.teacher.english@shinagawahs.test'),
            '1A:AI' => $this->teacherByEmail('qa.teacher.ai@shinagawahs.test'),
            '1A:CAREER' => $this->teacherByEmail('qa.teacher.career@shinagawahs.test'),
            '1A:HR' => $this->teacherByEmail('qa.teacher.career@shinagawahs.test'),
            '2A' => $this->teacherByEmail('qa.teacher.2a@shinagawahs.test'),
            '3A' => $this->teacherByEmail('qa.teacher.3a@shinagawahs.test'),
            '1B' => $this->teacherByEmail('qa.teacher.1b@shinagawahs.test'),
            '2B' => $this->teacherByEmail('qa.teacher.2b@shinagawahs.test'),
            '3B' => $this->teacherByEmail('qa.teacher.3b@shinagawahs.test'),
        ]);
        $courses = collect();

        foreach ($this->officialClassTargets() as [$classCode, $grade]) {
            foreach ($this->officialSchedule($grade) as [, , $subjectCode, $courseName]) {
                $courseKey = $this->courseKey($classCode, $grade, $courseName);

                if ($courses->has($courseKey)) {
                    continue;
                }

                $course = $this->upsertCourse(
                    academicYear: $academicYear,
                    grade: $grade,
                    classGroup: $classGroups->get($classCode),
                    subject: $subjects->get($subjectCode),
                    teacher: $teachers->get($classCode.':'.$subjectCode)
                        ?? $teachers->get($classCode),
                    courseName: $courseName,
                    status: MasterStatus::Active,
                    externalId: $this->manualCourseExternalId(
                        $academicYear,
                        $classCode,
                        $grade,
                        $subjectCode,
                        $courseName,
                    ),
                );
                $courses->put($courseKey, $course);
            }
        }

        foreach ($this->officialSchedule(Grade::First) as [, , $subjectCode, $courseName]) {
            $courseKey = $this->previousCourseKey('1A', Grade::First, $courseName);

            if ($courses->has($courseKey)) {
                continue;
            }

            $course = $this->upsertCourse(
                academicYear: $academicYear - 1,
                grade: Grade::First,
                classGroup: $classGroups->get('1A'),
                subject: $subjects->get($subjectCode),
                teacher: $teachers->get('1A:'.$subjectCode),
                courseName: $courseName,
                status: MasterStatus::Active,
                externalId: $this->manualCourseExternalId(
                    $academicYear - 1,
                    '1A',
                    Grade::First,
                    $subjectCode,
                    $courseName,
                ),
            );
            $courses->put($courseKey, $course);
        }

        $inactiveCourse = $this->upsertCourse(
            academicYear: $academicYear,
            grade: Grade::First,
            classGroup: $classGroups->get('1B'),
            subject: $subjects->get('AI'),
            teacher: $teachers->get('1B'),
            courseName: '無効授業確認用（QA）',
            status: MasterStatus::Inactive,
            externalId: sprintf('QA-INACTIVE-%d', $academicYear),
        );
        $courses->put('inactive', $inactiveCourse);

        $unassignedCourse = $this->upsertCourse(
            academicYear: $academicYear,
            grade: Grade::Third,
            classGroup: $classGroups->get('3B'),
            subject: $subjects->get('AI'),
            teacher: null,
            courseName: '担当未設定確認用（QA）',
            status: MasterStatus::Active,
            externalId: sprintf('QA-UNASSIGNED-%d', $academicYear),
        );
        $courses->put('unassigned', $unassignedCourse);

        return $courses;
    }

    /**
     * 公式HPの午前・午後それぞれ3時限の週間時間割を登録する。
     *
     * @param  Collection<string, Course>  $courses  登録済み授業
     * @return Collection<string, TimetableSlot> 登録した時間割
     */
    private function seedTimetable(Collection $courses): Collection
    {
        $morningPeriods = [
            1 => ['10:00:00', '10:50:00'],
            2 => ['11:00:00', '11:50:00'],
            3 => ['12:20:00', '13:10:00'],
        ];
        $afternoonPeriods = [
            1 => ['13:30:00', '14:20:00'],
            2 => ['14:30:00', '15:20:00'],
            3 => ['15:50:00', '16:40:00'],
        ];
        $slots = collect();

        foreach ($this->officialClassTargets() as [$classCode, $grade]) {
            $periods = str_ends_with($classCode, 'A')
                ? $morningPeriods
                : $afternoonPeriods;

            foreach ($this->officialSchedule($grade) as [$dayOfWeek, $periodNo, , $courseName]) {
                $course = $courses->get(
                    $this->courseKey($classCode, $grade, $courseName),
                );
                [$startTime, $endTime] = $periods[$periodNo];
                $slot = TimetableSlot::query()->updateOrCreate(
                    [
                        'course_id' => $course->id,
                        'day_of_week' => $dayOfWeek->value,
                        'period_no' => $periodNo,
                    ],
                    [
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => MasterStatus::Active,
                    ],
                );
                $slots->put(
                    $this->slotKey($classCode, $grade, $dayOfWeek, $periodNo),
                    $slot,
                );
            }
        }

        foreach ($this->officialSchedule(Grade::First) as [$dayOfWeek, $periodNo, , $courseName]) {
            $course = $courses->get(
                $this->previousCourseKey('1A', Grade::First, $courseName),
            );
            [$startTime, $endTime] = $morningPeriods[$periodNo];
            $slot = TimetableSlot::query()->updateOrCreate(
                [
                    'course_id' => $course->id,
                    'day_of_week' => $dayOfWeek->value,
                    'period_no' => $periodNo,
                ],
                [
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'status' => MasterStatus::Active,
                ],
            );
            $slots->put(
                $this->previousSlotKey('1A', Grade::First, $dayOfWeek, $periodNo),
                $slot,
            );
        }

        return $slots;
    }

    /**
     * 公式時間割の授業枠を使って出席・欠席・遅刻・早退データを登録する。
     *
     * @param  Collection<string, TimetableSlot>  $slots  登録済み時間割
     */
    private function seedLessonsAndAttendance(Collection $slots): void
    {
        $admin = $this->userByEmail('qa.admin@shinagawahs.test');
        $englishUser = $this->userByEmail('qa.teacher.english@shinagawahs.test');
        $aiUser = $this->userByEmail('qa.teacher.ai@shinagawahs.test');
        $careerUser = $this->userByEmail('qa.teacher.career@shinagawahs.test');
        $students = $this->studentsForTarget(Grade::First, '1A');
        $today = CarbonImmutable::today();

        if ($today->dayOfWeekIso <= DayOfWeek::Friday->value) {
            $todayDay = DayOfWeek::from($today->dayOfWeekIso);
            $todaySlot = $slots->get(
                $this->slotKey('1A', Grade::First, $todayDay, 1),
            );

            if ($todaySlot instanceof TimetableSlot) {
                $todaySession = LessonSession::query()->updateOrCreate(
                    [
                        'timetable_slot_id' => $todaySlot->id,
                        'lesson_date' => $today->toDateString(),
                    ],
                    [
                        'status' => LessonStatus::Scheduled,
                        'created_by' => $todaySlot->course->teacher->user_id,
                    ],
                );

                foreach ($students->take(2)->values() as $index => $student) {
                    AttendanceRecord::query()->updateOrCreate(
                        [
                            'lesson_session_id' => $todaySession->id,
                            'student_id' => $student->id,
                        ],
                        [
                            'attendance_status' => $index === 0
                                ? AttendanceStatus::Present
                                : AttendanceStatus::Late,
                            'recorded_by' => $todaySession->created_by,
                            'corrected_by' => null,
                            'note' => $index === 0
                                ? null
                                : '交通機関遅延の想定データ',
                        ],
                    );
                }
            }
        }

        $lessonSeeds = [
            [DayOfWeek::Monday, 1, 1, LessonStatus::Completed, $englishUser],
            [DayOfWeek::Wednesday, 1, 1, LessonStatus::Completed, $aiUser],
            [DayOfWeek::Monday, 3, 1, LessonStatus::Completed, $careerUser],
            [DayOfWeek::Tuesday, 1, 2, LessonStatus::Completed, $englishUser],
            [DayOfWeek::Wednesday, 1, 2, LessonStatus::Cancelled, $aiUser],
            [DayOfWeek::Thursday, 2, 2, LessonStatus::Completed, $englishUser],
        ];

        foreach (
            $lessonSeeds as $seedIndex => [$dayOfWeek, $periodNo, $weeksAgo, $lessonStatus, $teacherUser]
        ) {
            $slot = $slots->get(
                $this->slotKey('1A', Grade::First, $dayOfWeek, $periodNo),
            );
            $lessonDate = $this->previousWeekdayDate(
                $today,
                $slot->day_of_week->value,
                $weeksAgo,
            );
            $session = LessonSession::query()->updateOrCreate(
                [
                    'timetable_slot_id' => $slot->id,
                    'lesson_date' => $lessonDate->toDateString(),
                ],
                [
                    'status' => $lessonStatus,
                    'created_by' => $teacherUser->id,
                ],
            );

            if ($lessonStatus === LessonStatus::Cancelled) {
                continue;
            }

            foreach ($students as $studentIndex => $student) {
                $status = [
                    AttendanceStatus::Present,
                    AttendanceStatus::Absent,
                    AttendanceStatus::Late,
                    AttendanceStatus::EarlyLeave,
                ][($seedIndex + $studentIndex) % 4];
                $isCorrected = $seedIndex === 0 && $studentIndex === 1;
                AttendanceRecord::query()->updateOrCreate(
                    [
                        'lesson_session_id' => $session->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'attendance_status' => $status,
                        'recorded_by' => $teacherUser->id,
                        'corrected_by' => $isCorrected ? $admin->id : null,
                        'note' => $isCorrected ? '管理者修正確認用' : null,
                    ],
                );
            }
        }
    }

    /**
     * 確定・下書き・未入力状態を確認する年間評価を登録する。
     *
     * @param  Collection<string, Course>  $courses  登録済み授業
     */
    private function seedEvaluations(Collection $courses, int $academicYear): void
    {
        $gradeOneStudents = $this->studentsForTarget(Grade::First, '1A');
        $gradeTwoStudents = $this->studentsForTarget(Grade::Second, '2A');
        $gradeThreeStudents = $this->studentsForTarget(Grade::Third, '3A');
        $englishUser = $this->userByEmail('qa.teacher.english@shinagawahs.test');
        $aiUser = $this->userByEmail('qa.teacher.ai@shinagawahs.test');
        $careerUser = $this->userByEmail('qa.teacher.career@shinagawahs.test');

        $evaluationSeeds = [
            [
                $gradeOneStudents->get(0),
                $courses->get($this->courseKey('1A', Grade::First, '基礎英語')),
                86, 92, 75, 84.33, 4, $englishUser, EvaluationStatus::Confirmed,
            ],
            [
                $gradeOneStudents->get(0),
                $courses->get($this->courseKey('1A', Grade::First, 'AI基礎')),
                82, 88, 80, 83.33, null, $aiUser, EvaluationStatus::Draft,
            ],
            [
                $gradeOneStudents->get(1),
                $courses->get($this->courseKey('1A', Grade::First, '基礎英語')),
                72, 68, 74, 71.33, 3, $englishUser, EvaluationStatus::Confirmed,
            ],
            [
                $gradeOneStudents->get(2),
                $courses->get($this->courseKey('1A', Grade::First, 'キャリア教育')),
                95, 90, 92, 92.33, 5, $careerUser, EvaluationStatus::Confirmed,
            ],
            [
                $gradeTwoStudents->get(0),
                $courses->get($this->courseKey('2A', Grade::Second, '実践英語')),
                88, 85, 90, 87.67, 4, $englishUser, EvaluationStatus::Confirmed,
            ],
            [
                $gradeTwoStudents->get(1),
                $courses->get($this->courseKey('2A', Grade::Second, 'AI活用')),
                78, 80, 82, 80, null, $aiUser, EvaluationStatus::Draft,
            ],
            [
                $gradeThreeStudents->get(0),
                $courses->get($this->courseKey('3A', Grade::Third, 'キャリア教育')),
                91, 94, 93, 92.67, 5, $careerUser, EvaluationStatus::Confirmed,
            ],
        ];

        foreach (
            $evaluationSeeds as [
                $student,
                $course,
                $submission,
                $attendance,
                $attitude,
                $total,
                $gradeLevel,
                $evaluator,
                $status,
            ]
        ) {
            if (! $student instanceof Student || ! $course instanceof Course) {
                continue;
            }

            FinalEvaluation::query()->updateOrCreate(
                [
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'academic_year' => $academicYear,
                    'term_name' => EvaluationTerm::Annual->value,
                ],
                [
                    'submission_score' => $submission,
                    'attendance_score' => $attendance,
                    'attitude_score' => $attitude,
                    'total_score' => $total,
                    'grade_level' => $gradeLevel,
                    'evaluated_by' => $evaluator->id,
                    'status' => $status,
                ],
            );
        }
    }

    /**
     * 公式年間行事と学習内容に沿った面談記録を登録する。
     */
    private function seedInterviews(): void
    {
        $admin = $this->userByEmail('qa.admin@shinagawahs.test');
        $englishTeacher = $this->teacherByEmail('qa.teacher.english@shinagawahs.test');
        $aiTeacher = $this->teacherByEmail('qa.teacher.ai@shinagawahs.test');
        $careerTeacher = $this->teacherByEmail('qa.teacher.career@shinagawahs.test');
        $records = [
            [
                'qa.student01@shinagawahs.test', $englishTeacher, '学習計画面談', -70,
                '学習目標と英語の取り組み方を確認。',
                '次回までに自己紹介スピーチを準備する。',
            ],
            [
                'qa.student02@shinagawahs.test', $englishTeacher, '前期振り返り', -35,
                '前期の学習進捗と出欠状況を確認。', '夏季期間の学習計画を更新する。',
            ],
            [
                'qa.student09@shinagawahs.test', $aiTeacher, '進路ガイダンス', -20,
                '興味のある進路と情報収集方法を確認。',
                'AIを使って進路候補を比較する。',
            ],
            [
                'qa.student10@shinagawahs.test', $aiTeacher, '探究活動面談', -12,
                '社会課題研究のテーマについて相談。',
                'ファクトチェックを行い資料を改善する。',
            ],
            [
                'qa.student17@shinagawahs.test', $careerTeacher, '進路面談', -8,
                '卒業後の進路とキャリア観を確認。', '必要書類の下書きを作成する。',
            ],
            [
                'qa.student18@shinagawahs.test', $careerTeacher, '個別面談', -3,
                '面接練習と社会人マナーの確認。', '模擬面接の振り返りを記録する。',
            ],
        ];

        foreach ($records as [$studentEmail, $teacher, $type, $days, $memo, $nextAction]) {
            $student = $this->studentByEmail($studentEmail);
            InterviewRecord::query()->updateOrCreate(
                [
                    'student_id' => $student->id,
                    'teacher_id' => $teacher->id,
                    'interview_date' => CarbonImmutable::today()->addDays($days)->toDateString(),
                    'interview_type' => $type,
                ],
                [
                    'memo' => $memo,
                    'next_action' => $nextAction,
                    'drive_url' => 'https://drive.google.com/drive/my-drive',
                    'meet_url' => 'https://meet.google.com/',
                    'created_by' => $admin->id,
                    'updated_by' => null,
                ],
            );
        }
    }

    /**
     * 公開範囲・公開期間・重要状態を確認するお知らせを登録する。
     */
    private function seedAnnouncements(): void
    {
        $admin = $this->userByEmail('qa.admin@shinagawahs.test');
        $class1A = ClassGroup::query()->where('class_code', '1A')->firstOrFail();
        $class1B = ClassGroup::query()->where('class_code', '1B')->firstOrFail();
        $now = CarbonImmutable::now();
        $announcements = [
            [
                '8月 校外学習・体験活動について',
                '地域や企業、文化に触れる体験活動を予定しています。'.
                    '詳細は学校からの案内を確認してください。',
                AnnouncementNoticeType::School,
                true,
                AnnouncementStatus::Published,
                $now->subDays(3),
                $now->addDays(30),
                [[AnnouncementTargetType::All, null]],
            ],
            [
                '9月 進路ガイダンスのご案内',
                '進学や将来の仕事について情報を集める進路ガイダンスを実施します。',
                AnnouncementNoticeType::Grade,
                true,
                AnnouncementStatus::Published,
                $now->subDay(),
                $now->addDays(45),
                [[AnnouncementTargetType::Role, UserRole::Student->value]],
            ],
            [
                '1年生 英語教育の学習について',
                '1年生は「英語って楽しい！を見つけよう」をテーマに、'.
                    '会話やゲームを通して基礎を身につけます。',
                AnnouncementNoticeType::Grade,
                false,
                AnnouncementStatus::Published,
                $now->subDays(10),
                null,
                [[AnnouncementTargetType::Grade, Grade::First->value]],
            ],
            [
                '2年生 AIリテラシー探究のお知らせ',
                '課題発見と問いの立て方、情報収集、ファクトチェックを進めます。',
                AnnouncementNoticeType::Grade,
                false,
                AnnouncementStatus::Published,
                $now->subDays(8),
                null,
                [[AnnouncementTargetType::Grade, Grade::Second->value]],
            ],
            [
                '1年Aクラス 探究発表・作品制作の準備',
                '学んだことを整理し、'.
                    '自分の言葉で発信するための準備を進めてください。',
                AnnouncementNoticeType::School,
                false,
                AnnouncementStatus::Published,
                $now->subDays(5),
                null,
                [[AnnouncementTargetType::ClassGroup, (string) $class1A->id]],
            ],
            [
                '1年Bクラス 個別面談日程の確認',
                '学習状況と進路の方向性を確認する個別面談の日程を確認してください。',
                AnnouncementNoticeType::School,
                false,
                AnnouncementStatus::Published,
                $now->subDays(4),
                null,
                [[AnnouncementTargetType::ClassGroup, (string) $class1B->id]],
            ],
            [
                '教員向け キャリア学習準備',
                '11月のキャリア学習に向けて、'.
                    '社会で働く人や仕事に触れる教材を確認してください。',
                AnnouncementNoticeType::Office,
                false,
                AnnouncementStatus::Published,
                $now->subDays(2),
                null,
                [[AnnouncementTargetType::Role, UserRole::Teacher->value]],
            ],
            [
                '公開前確認用のお知らせ',
                '下書き状態の表示確認用データです。',
                AnnouncementNoticeType::Office,
                false,
                AnnouncementStatus::Draft,
                null,
                null,
                [[AnnouncementTargetType::All, null]],
            ],
            [
                '未来公開確認用のお知らせ',
                '未来日時の公開開始確認用データです。',
                AnnouncementNoticeType::School,
                false,
                AnnouncementStatus::Published,
                $now->addDays(10),
                $now->addDays(20),
                [[AnnouncementTargetType::All, null]],
            ],
            [
                '公開終了確認用のお知らせ',
                '公開期間終了後の表示確認用データです。',
                AnnouncementNoticeType::School,
                false,
                AnnouncementStatus::Published,
                $now->subDays(30),
                $now->subDays(2),
                [[AnnouncementTargetType::All, null]],
            ],
        ];

        foreach (
            $announcements as [$title, $body, $noticeType, $important, $status, $start, $end, $targets]
        ) {
            $announcement = Announcement::withTrashed()->updateOrCreate(
                ['title' => $title],
                [
                    'body' => $body,
                    'notice_type' => $noticeType,
                    'is_important' => $important,
                    'publish_start_at' => $start,
                    'publish_end_at' => $end,
                    'status' => $status,
                    'created_by' => $admin->id,
                    'updated_by' => null,
                ],
            );
            $announcement->restore();
            $announcement->targets()->delete();

            foreach ($targets as [$targetType, $targetValue]) {
                $announcement->targets()->create([
                    'target_type' => $targetType,
                    'target_value' => $targetValue,
                ]);
            }
        }
    }

    /**
     * 各公開範囲と無効状態を確認する外部リンクを登録する。
     *
     * @param  Collection<string, Course>  $courses  登録済み授業
     */
    private function seedExternalLinks(Collection $courses): void
    {
        $class1A = ClassGroup::query()->where('class_code', '1A')->firstOrFail();
        $student = $this->studentByEmail('qa.student01@shinagawahs.test');
        $englishCourse = $courses->get($this->courseKey('1A', Grade::First, '基礎英語'));
        $links = [
            [
                ExternalLinkType::Calendar, '年間行事（品川高等学院 公式HP）',
                'https://shinagawahs.jp/school-life/annual-events/',
                ExternalLinkScopeType::Global, null, 10, MasterStatus::Active,
            ],
            [
                ExternalLinkType::Forms, '個別相談・面談申込（品川高等学院 公式HP）',
                'https://shinagawahs.jp/consultation/',
                ExternalLinkScopeType::Role, UserRole::Student->scopeId(), 20, MasterStatus::Active,
            ],
            [
                ExternalLinkType::Drive, 'Google Drive', 'https://drive.google.com/drive/my-drive',
                ExternalLinkScopeType::Global, null, 30, MasterStatus::Active,
            ],
            [
                ExternalLinkType::Chat, 'Google Chat', 'https://chat.google.com/',
                ExternalLinkScopeType::Role, UserRole::Student->scopeId(), 40, MasterStatus::Active,
            ],
            [
                ExternalLinkType::Meet, 'Google Meet', 'https://meet.google.com/',
                ExternalLinkScopeType::Student, $student->id, 50, MasterStatus::Active,
            ],
            [
                ExternalLinkType::Drive, '1年Aクラス共有資料', 'https://drive.google.com/drive/my-drive',
                ExternalLinkScopeType::ClassGroup, $class1A->id, 60, MasterStatus::Active,
            ],
            [
                ExternalLinkType::Classroom, '基礎英語 Classroom', 'https://classroom.google.com/',
                ExternalLinkScopeType::Course, $englishCourse->id, 70, MasterStatus::Active,
            ],
            [
                ExternalLinkType::Drive, '無効リンク確認用', 'https://drive.google.com/',
                ExternalLinkScopeType::Global, null, 80, MasterStatus::Inactive,
            ],
        ];

        foreach ($links as [$type, $name, $url, $scopeType, $scopeId, $order, $status]) {
            ExternalLink::query()->updateOrCreate(
                ['link_name' => $name],
                [
                    'link_type' => $type,
                    'url' => $url,
                    'scope_type' => $scopeType,
                    'scope_id' => $scopeId,
                    'display_order' => $order,
                    'status' => $status,
                ],
            );
        }
    }

    /**
     * 一覧・検索・CSV出力を確認するQA専用操作ログを登録する。
     */
    private function seedOperationLogs(): void
    {
        OperationLog::query()
            ->where('action', 'like', 'QA_TEST_%')
            ->delete();

        $users = [
            $this->userByEmail('qa.admin@shinagawahs.test'),
            $this->userByEmail('qa.admin2@shinagawahs.test'),
            $this->userByEmail('qa.teacher.english@shinagawahs.test'),
            $this->userByEmail('qa.csv@shinagawahs.test'),
        ];
        $targets = ['users', 'students', 'courses', 'attendance_records', 'final_evaluations', 'announcements'];
        $today = CarbonImmutable::now();

        for ($index = 1; $index <= 60; $index++) {
            $user = $users[($index - 1) % count($users)];
            $targetTable = $targets[($index - 1) % count($targets)];
            $detail = [
                'manual_test_seed' => true,
                'sequence' => $index,
                'summary' => sprintf('QA手動テスト操作ログ %02d', $index),
            ];

            if ($index === 1) {
                $detail['csv_formula_check'] = '=SUM(A1:A2)';
                $detail['csv_plus_check'] = '+1+1';
                $detail['csv_minus_check'] = '-1+1';
                $detail['csv_at_check'] = '@SUM(A1:A2)';
            }

            $log = OperationLog::query()->create([
                'user_id' => $user->id,
                'action' => sprintf('QA_TEST_%s_%02d', strtoupper($targetTable), $index),
                'target_table' => $targetTable,
                'target_id' => $index,
                'detail' => $detail,
            ]);
            $log->forceFill([
                'created_at' => $today->subHours($index * 3),
            ])->saveQuietly();
        }
    }

    /**
     * 指定条件のQAユーザーを登録または更新する。
     */
    private function upsertUser(
        string $email,
        string $name,
        UserRole $role,
        UserStatus $status,
    ): User {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => self::PASSWORD,
                'role' => $role,
                'status' => $status,
            ],
        );
    }

    /**
     * 指定条件の授業を登録または更新し、論理削除済みなら復元する。
     */
    private function upsertCourse(
        int $academicYear,
        Grade $grade,
        ClassGroup $classGroup,
        Subject $subject,
        ?Teacher $teacher,
        string $courseName,
        MasterStatus $status,
        string $externalId,
    ): Course {
        $course = Course::withTrashed()->updateOrCreate(
            [
                'academic_year' => $academicYear,
                'class_group_id' => $classGroup->id,
                'grade' => $grade,
                'subject_id' => $subject->id,
                'course_name' => $courseName,
            ],
            [
                'teacher_id' => $teacher?->id,
                'google_classroom_url' => 'https://classroom.google.com/',
                'google_classroom_id' => $externalId,
                'status' => $status,
            ],
        );
        $course->restore();

        return $course;
    }

    /**
     * メールアドレスからQAユーザーを取得する。
     */
    private function userByEmail(string $email): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }

    /**
     * ユーザーのメールアドレスからQA教員を取得する。
     */
    private function teacherByEmail(string $email): Teacher
    {
        return Teacher::query()
            ->whereHas('user', static fn ($query) => $query->where('email', $email))
            ->firstOrFail();
    }

    /**
     * ユーザーのメールアドレスからQA生徒を取得する。
     */
    private function studentByEmail(string $email): Student
    {
        return Student::query()
            ->whereHas('user', static fn ($query) => $query->where('email', $email))
            ->firstOrFail();
    }

    /**
     * 指定学年・クラスの在籍QA生徒を取得する。
     *
     * @return Collection<int, Student> 対象生徒
     */
    private function studentsForTarget(Grade $grade, string $classCode): Collection
    {
        $classGroupId = ClassGroup::query()
            ->where('class_code', $classCode)
            ->value('id');

        return Student::query()
            ->where('grade', $grade->value)
            ->where('class_group_id', $classGroupId)
            ->where('status', StudentStatus::Active->value)
            ->orderBy('student_no')
            ->get();
    }

    /**
     * 公式HPに掲載されている6クラスと学年の対応を返す。
     *
     * @return array<int, array{0: string, 1: Grade}> クラスコードと学年
     */
    private function officialClassTargets(): array
    {
        return [
            ['1A', Grade::First],
            ['2A', Grade::Second],
            ['3A', Grade::Third],
            ['1B', Grade::First],
            ['2B', Grade::Second],
            ['3B', Grade::Third],
        ];
    }

    /**
     * 公式HPに掲載されている学年別週間時間割を返す。
     *
     * @return array<int, array{0: DayOfWeek, 1: int, 2: string, 3: string}> 時間割
     */
    private function officialSchedule(Grade $grade): array
    {
        return match ($grade) {
            Grade::First => [
                [DayOfWeek::Monday, 1, 'ENG', '基礎英語'],
                [DayOfWeek::Monday, 2, 'ENG', '英会話'],
                [DayOfWeek::Monday, 3, 'CAREER', 'キャリア教育'],
                [DayOfWeek::Tuesday, 1, 'ENG', '英語文法'],
                [DayOfWeek::Tuesday, 2, 'ENG', 'リーディング'],
                [DayOfWeek::Tuesday, 3, 'ENG', '英語表現'],
                [DayOfWeek::Wednesday, 1, 'AI', 'AI基礎'],
                [DayOfWeek::Wednesday, 2, 'ENG', '英会話実践'],
                [DayOfWeek::Wednesday, 3, 'ENG', 'リスニング'],
                [DayOfWeek::Thursday, 1, 'ENG', '基礎英語Ⅱ'],
                [DayOfWeek::Thursday, 2, 'ENG', 'スピーキング'],
                [DayOfWeek::Thursday, 3, 'ENG', '探究英語'],
                [DayOfWeek::Friday, 1, 'ENG', '英語プレゼンテーション'],
                [DayOfWeek::Friday, 2, 'ENG', 'ライティング'],
                [DayOfWeek::Friday, 3, 'HR', 'HR・振り返り'],
            ],
            Grade::Second => [
                [DayOfWeek::Monday, 1, 'ENG', '実践英語'],
                [DayOfWeek::Monday, 2, 'ENG', 'ディスカッション英語'],
                [DayOfWeek::Monday, 3, 'CAREER', 'キャリア教育'],
                [DayOfWeek::Tuesday, 1, 'ENG', 'ビジネス英語'],
                [DayOfWeek::Tuesday, 2, 'ENG', 'ライティング'],
                [DayOfWeek::Tuesday, 3, 'ENG', 'リーディング'],
                [DayOfWeek::Wednesday, 1, 'AI', 'AI活用'],
                [DayOfWeek::Wednesday, 2, 'ENG', '英会話実践'],
                [DayOfWeek::Wednesday, 3, 'ENG', '探究英語'],
                [DayOfWeek::Thursday, 1, 'ENG', '観光英語'],
                [DayOfWeek::Thursday, 2, 'ENG', 'プレゼンテーション演習'],
                [DayOfWeek::Thursday, 3, 'ENG', 'プロジェクト英語'],
                [DayOfWeek::Friday, 1, 'ENG', '英語プレゼンテーション'],
                [DayOfWeek::Friday, 2, 'ENG', 'メディア英語'],
                [DayOfWeek::Friday, 3, 'HR', 'HR・振り返り'],
            ],
            Grade::Third => [
                [DayOfWeek::Monday, 1, 'ENG', '進路英語'],
                [DayOfWeek::Monday, 2, 'ENG', '面接英語'],
                [DayOfWeek::Monday, 3, 'CAREER', 'キャリア教育'],
                [DayOfWeek::Tuesday, 1, 'ENG', 'ビジネス英語'],
                [DayOfWeek::Tuesday, 2, 'ENG', '英語コミュニケーション'],
                [DayOfWeek::Tuesday, 3, 'ENG', '英語表現'],
                [DayOfWeek::Wednesday, 1, 'AI', 'AI・DX活用'],
                [DayOfWeek::Wednesday, 2, 'ENG', '英会話実践'],
                [DayOfWeek::Wednesday, 3, 'ENG', 'リーディング演習'],
                [DayOfWeek::Thursday, 1, 'ENG', 'グローバル英語'],
                [DayOfWeek::Thursday, 2, 'ENG', '英文ライティング'],
                [DayOfWeek::Thursday, 3, 'ENG', '探究英語'],
                [DayOfWeek::Friday, 1, 'ENG', 'プレゼンテーション英語'],
                [DayOfWeek::Friday, 2, 'ENG', '卒業プロジェクト'],
                [DayOfWeek::Friday, 3, 'HR', 'HR・振り返り'],
            ],
        };
    }

    /**
     * 授業コレクションで使用する一意キーを生成する。
     */
    private function courseKey(string $classCode, Grade $grade, string $courseName): string
    {
        return sprintf('%s:%s:%s', $classCode, $grade->value, $courseName);
    }

    /**
     * 前年度授業コレクションで使用する一意キーを生成する。
     */
    private function previousCourseKey(
        string $classCode,
        Grade $grade,
        string $courseName,
    ): string {
        return sprintf('previous:%s', $this->courseKey($classCode, $grade, $courseName));
    }

    /**
     * 時間割コレクションで使用する一意キーを生成する。
     */
    private function slotKey(
        string $classCode,
        Grade $grade,
        DayOfWeek $dayOfWeek,
        int $periodNo,
    ): string {
        return sprintf(
            '%s:%s:%d:%d',
            $classCode,
            $grade->value,
            $dayOfWeek->value,
            $periodNo,
        );
    }

    /**
     * 前年度時間割コレクションで使用する一意キーを生成する。
     */
    private function previousSlotKey(
        string $classCode,
        Grade $grade,
        DayOfWeek $dayOfWeek,
        int $periodNo,
    ): string {
        return sprintf(
            'previous:%s',
            $this->slotKey($classCode, $grade, $dayOfWeek, $periodNo),
        );
    }

    /**
     * QA授業を識別するGoogle Classroom外部IDを生成する。
     */
    private function manualCourseExternalId(
        int $academicYear,
        string $classCode,
        Grade $grade,
        string $subjectCode,
        string $courseName,
    ): string {
        return sprintf(
            'QA-OFFICIAL-%d-%s-%s-%s-%s',
            $academicYear,
            $classCode,
            $grade->value,
            $subjectCode,
            substr(sha1($courseName), 0, 10),
        );
    }

    /**
     * 指定週数前にある対象曜日の日付を取得する。
     */
    private function previousWeekdayDate(
        CarbonImmutable $today,
        int $isoWeekday,
        int $weeksAgo,
    ): CarbonImmutable {
        $date = $today->subWeeks($weeksAgo);

        while ($date->dayOfWeekIso !== $isoWeekday) {
            $date = $date->subDay();
        }

        return $date;
    }
}
