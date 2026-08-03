<?php

namespace Tests\Feature\Architecture;

use App\Enums\ExternalLinkScopeType;
use App\Enums\ExternalLinkType;
use App\Enums\MasterStatus;
use App\Models\ExternalLink;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class StudentExternalServiceScreenIdTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('externalServiceScreens')]
    public function test_shared_external_service_screen_displays_its_defined_screen_id(
        string $routeName,
        string $screenId,
        ExternalLinkType $linkType,
        string $url,
    ): void {
        $student = Student::factory()->create();

        ExternalLink::factory()->create([
            'link_name' => "{$screenId}確認用リンク",
            'link_type' => $linkType,
            'scope_type' => ExternalLinkScopeType::Global,
            'scope_id' => null,
            'status' => MasterStatus::Active,
            'url' => $url,
        ]);

        $this
            ->actingAs($student->user)
            ->get(route($routeName))
            ->assertOk()
            ->assertSeeText($screenId);
    }

    /**
     * 生徒向け外部サービス画面のテストデータを返します。
     *
     * @return iterable<string, array{string, string, ExternalLinkType, string}>
     */
    public static function externalServiceScreens(): iterable
    {
        yield '年間予定 S-006' => [
            'student.annual-schedule',
            'S-006',
            ExternalLinkType::Calendar,
            'https://calendar.google.com/calendar/embed?src=school',
        ];

        yield '面談申込 S-007' => [
            'student.interview-request',
            'S-007',
            ExternalLinkType::Forms,
            'https://docs.google.com/forms/d/example/viewform',
        ];

        yield 'Googleサービス S-008' => [
            'student.external-resources',
            'S-008',
            ExternalLinkType::Drive,
            'https://drive.google.com/drive/folders/example',
        ];
    }
}