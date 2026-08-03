<?php

namespace Tests\Feature;

use App\Models\InterviewRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InterviewModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_interview_record_relations_and_casts_are_available(): void
    {
        $interviewRecord = InterviewRecord::factory()->create([
            'interview_date' => '2026-07-29',
        ]);

        $this->assertNotNull($interviewRecord->student);
        $this->assertNotNull($interviewRecord->teacher);
        $this->assertNotNull($interviewRecord->creator);
        $this->assertSame(
            '2026-07-29',
            $interviewRecord->interview_date->format('Y-m-d'),
        );
    }

    public function test_student_teacher_and_user_have_interview_relations(): void
    {
        $interviewRecord = InterviewRecord::factory()->create();

        $this->assertTrue(
            $interviewRecord->student
                ->interviewRecords()
                ->whereKey($interviewRecord->id)
                ->exists(),
        );

        $this->assertTrue(
            $interviewRecord->teacher
                ?->interviewRecords()
                ->whereKey($interviewRecord->id)
                ->exists() ?? false,
        );

        $this->assertTrue(
            $interviewRecord->creator
                ->createdInterviewRecords()
                ->whereKey($interviewRecord->id)
                ->exists(),
        );
    }
}
