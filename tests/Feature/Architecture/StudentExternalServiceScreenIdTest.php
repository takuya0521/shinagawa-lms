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

/**
 * 生徒向け外部サービス共通画面の画面IDを確認するアーキテクチャテスト。
 *
 * 年間予定、面談申込、外部サービスの各ルートで設計書どおりの画面IDが表示されることを検証する。
 */
final class StudentExternalServiceScreenIdTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 生徒向け外部サービス共通画面がルートごとに定義された画面IDを表示することを確認する。
     *
     * 前提: 生徒、外部リンクなど、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示されることを確認する。
     */
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
     * 生徒向け外部サービス画面のルート、画面ID、リンク種別、URLを返す。
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
