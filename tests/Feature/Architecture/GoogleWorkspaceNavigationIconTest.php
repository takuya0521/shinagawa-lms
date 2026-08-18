<?php

namespace Tests\Feature\Architecture;

use Tests\TestCase;

/**
 * サイドバーのGoogle Workspace項目が各サービスの形状を保ったローズ色アイコンを使用することを確認する。
 */
final class GoogleWorkspaceNavigationIconTest extends TestCase
{
    /**
     * 全ロールのGoogle Workspace項目が専用アイコン名とサイドバー用画像アセットへ対応することを確認する。
     *
     * 前提: ロール別ナビゲーション設定とGoogleサービス画像が存在する。
     * 処理: Drive、Calendar、Chat、Meet、Formsのアイコン名と画像ファイルを確認する。
     * 期待結果: 汎用線画へ戻らず、各Googleサービスを識別できるローズ色アイコンを表示できる。
     */
    public function test_google_workspace_sidebar_uses_brand_icons(): void
    {
        $expectedIcons = [
            'Google Drive' => ['google-drive', 'images/google/sidebar/drive.svg'],
            'Google Calendar' => ['google-calendar', 'images/google/sidebar/calendar.svg'],
            'Google Chat' => ['google-chat', 'images/google/sidebar/chat.svg'],
            'Google Meet' => ['google-meet', 'images/google/sidebar/meet.svg'],
            'Google Forms' => ['google-forms', 'images/google/sidebar/forms.svg'],
        ];

        foreach (['admin', 'teacher', 'student'] as $role) {
            $items = collect(config("lms_navigation.roles.{$role}.sections"))
                ->flatMap(static fn (array $section): array => $section['items']);

            foreach ($expectedIcons as $label => [$icon, $asset]) {
                $item = $items->firstWhere('label', $label);

                $this->assertIsArray($item);
                $this->assertSame($icon, $item['icon']);
                $this->assertFileExists(public_path($asset));
            }
        }
    }
}
