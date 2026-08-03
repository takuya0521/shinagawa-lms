<?php

namespace Tests\Feature\Student;

use App\Enums\ExternalLinkScopeType;
use App\Enums\ExternalLinkType;
use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\ExternalLink;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class ExternalLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_student_sees_links_matching_global_role_class_course_and_student_scopes(): void
    {
        Carbon::setTestNow('2026-07-29 10:00:00');
        $classGroup = ClassGroup::factory()->create();
        $otherClassGroup = ClassGroup::factory()->create();
        $student = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
        ]);
        $course = Course::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'academic_year' => 2026,
        ]);

        $this->link('全体Chat', ExternalLinkType::Chat, ExternalLinkScopeType::Global, null);
        $this->link('生徒Drive', ExternalLinkType::Drive, ExternalLinkScopeType::Role, UserRole::Student->scopeId());
        $this->link('クラスMeet', ExternalLinkType::Meet, ExternalLinkScopeType::ClassGroup, $classGroup->id);
        $this->link('授業Drive', ExternalLinkType::Drive, ExternalLinkScopeType::Course, $course->id);
        $this->link('個人Chat', ExternalLinkType::Chat, ExternalLinkScopeType::Student, $student->id);
        $this->link('他クラスDrive', ExternalLinkType::Drive, ExternalLinkScopeType::ClassGroup, $otherClassGroup->id);
        $this->link(
            '無効Chat',
            ExternalLinkType::Chat,
            ExternalLinkScopeType::Global,
            null,
            MasterStatus::Inactive,
        );

        $this
            ->actingAs($student->user)
            ->get(route('student.external-resources'))
            ->assertOk()
            ->assertSeeText('S-008')
            ->assertSeeText('全体Chat')
            ->assertSeeText('生徒Drive')
            ->assertSeeText('クラスMeet')
            ->assertSeeText('授業Drive')
            ->assertSeeText('個人Chat')
            ->assertDontSeeText('他クラスDrive')
            ->assertDontSeeText('無効Chat');
    }

    public function test_student_can_view_calendar_and_interview_form_links(): void
    {
        $student = Student::factory()->create();
        $calendar = $this->link(
            '学校年間予定',
            ExternalLinkType::Calendar,
            ExternalLinkScopeType::Global,
            null,
            MasterStatus::Active,
            'https://calendar.google.com/calendar/embed?src=school',
        );
        $form = $this->link(
            '面談希望フォーム',
            ExternalLinkType::Forms,
            ExternalLinkScopeType::Role,
            UserRole::Student->scopeId(),
            MasterStatus::Active,
            'https://docs.google.com/forms/d/example/viewform',
        );

        $this
            ->actingAs($student->user)
            ->get(route('student.annual-schedule'))
            ->assertOk()
            ->assertSeeText($calendar->link_name)
            ->assertSee($calendar->url, false);

        $this
            ->actingAs($student->user)
            ->get(route('student.interview-request'))
            ->assertOk()
            ->assertSeeText($form->link_name)
            ->assertSee($form->url, false);
    }

    public function test_teacher_cannot_access_student_external_services(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user)
            ->get(route('student.annual-schedule'))
            ->assertForbidden();
        $this->actingAs($teacher->user)
            ->get(route('student.interview-request'))
            ->assertForbidden();
    }

    private function link(
        string $name,
        ExternalLinkType $linkType,
        ExternalLinkScopeType $scopeType,
        ?int $scopeId,
        MasterStatus $status = MasterStatus::Active,
        ?string $url = null,
    ): ExternalLink {
        return ExternalLink::factory()->create([
            'link_name' => $name,
            'link_type' => $linkType,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'status' => $status,
            'url' => $url ?? 'https://example.com/'.strtolower(str_replace(' ', '-', $name)),
        ]);
    }
}
