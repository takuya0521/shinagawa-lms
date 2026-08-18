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
 * 生徒向け外部サービス共通画面の提出用表示を確認するアーキテクチャテスト。
 *
 * 年間予定、面談申込、外部サービスの各ルートで画面タイトルとリンクを表示し、
 * 開発用の画面IDを表示しないことを検証する。
 */
final class StudentExternalServiceScreenIdTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 生徒向け外部サービス画面が必要な情報だけを表示することを確認する。
     *
     * 前提: 生徒、外部リンクなど、検証に必要なテストデータを準備する。
     * 処理: 対象ルートを開き、タイトル・リンク・画面IDの表示状態を確認する。
     * 期待結果: HTTP 200となり、タイトルとリンクは表示され、画面IDは表示されない。
     */
    #[DataProvider('externalServiceScreens')]
    public function test_shared_external_service_screen_hides_its_development_id(
        string $routeName,
        string $screenId,
        string $title,
        ExternalLinkType $linkType,
        string $url,
    ): void {
        $student = Student::factory()->create();
        $linkName = "{$title}確認用リンク";

        ExternalLink::factory()->create([
            'link_name' => $linkName,
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
            ->assertSeeText($title)
            ->assertSeeText($linkName)
            ->assertDontSeeText($screenId);
    }

    /**
     * 生徒向け外部サービス画面のルート、非表示ID、タイトル、リンク情報を返す。
     *
     * @return iterable<string, array{string, string, string, ExternalLinkType, string}>
     */
    public static function externalServiceScreens(): iterable
    {
        yield '年間予定' => [
            'student.annual-schedule',
            'S-006',
            '年間予定・学校行事',
            ExternalLinkType::Calendar,
            'https://calendar.google.com/calendar/embed?src=school',
        ];

        yield '面談申込' => [
            'student.interview-request',
            'S-007',
            '面談希望申込',
            ExternalLinkType::Forms,
            'https://docs.google.com/forms/d/example/viewform',
        ];

        yield 'Googleサービス' => [
            'student.external-resources',
            'S-008',
            'Googleサービス',
            ExternalLinkType::Drive,
            'https://drive.google.com/drive/folders/example',
        ];
    }
}
