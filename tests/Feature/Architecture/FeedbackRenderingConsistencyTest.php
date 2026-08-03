<?php

namespace Tests\Feature\Architecture;

use Tests\TestCase;

final class FeedbackRenderingConsistencyTest extends TestCase
{
    public function test_status_and_success_flash_messages_are_rendered_only_by_the_common_layout(): void
    {
        $violations = [];
        $viewsRoot = resource_path('views');

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($viewsRoot),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relativePath = str_replace(
                '\\',
                '/',
                substr($file->getPathname(), strlen($viewsRoot) + 1),
            );

            if ($relativePath === 'layouts/app.blade.php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (! is_string($contents)) {
                continue;
            }

            if (preg_match("/session\('(status|success)'\)/", $contents) === 1) {
                $violations[] = $relativePath;
            }
        }

        self::assertSame([], $violations);
    }
}
