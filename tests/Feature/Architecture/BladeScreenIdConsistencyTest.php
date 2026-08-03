<?php

namespace Tests\Feature\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class BladeScreenIdConsistencyTest extends TestCase
{
    #[DataProvider('screenIdProvider')]
    public function test_blade_displays_the_screen_id_defined_by_the_design(
        string $relativePath,
        string $expectedScreenId,
    ): void {
        $path = resource_path('views/'.$relativePath);

        self::assertFileExists($path);

        $contents = file_get_contents($path);
        self::assertIsString($contents);

        preg_match_all('/>([CAST]-\d{3})(?:（[^<]*）)?</u', $contents, $matches);

        self::assertSame(
            [$expectedScreenId],
            array_values(array_unique($matches[1])),
            "画面IDが設計書と一致しません: {$relativePath}",
        );
    }

    public function test_every_blade_with_a_visible_screen_id_is_in_the_design_map(): void
    {
        $expectedPaths = array_keys(self::screenIdMap());
        $actualPaths = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views')),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (! is_string($contents)
                || preg_match('/>[CAST]-\d{3}(?:（[^<]*）)?</u', $contents) !== 1) {
                continue;
            }

            $actualPaths[] = str_replace(
                '\\',
                '/',
                substr($file->getPathname(), strlen(resource_path('views')) + 1),
            );
        }

        sort($expectedPaths);
        sort($actualPaths);

        self::assertSame($expectedPaths, $actualPaths);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function screenIdProvider(): iterable
    {
        foreach (self::screenIdMap() as $path => $screenId) {
            yield $screenId.' '.$path => [$path, $screenId];
        }
    }

    /**
     * @return array<string, string>
     */
    private static function screenIdMap(): array
    {
        return [
            'account/password/edit.blade.php' => 'C-002',
            'dashboard/admin.blade.php' => 'A-001',
            'dashboard/teacher.blade.php' => 'T-001',
            'admin/course-teacher-assignments/index.blade.php' => 'A-022',
            'admin/evaluations/index.blade.php' => 'A-029',
            'admin/evaluations/edit.blade.php' => 'A-030',
            'admin/interviews/index.blade.php' => 'A-031',
            'admin/interviews/create.blade.php' => 'A-032',
            'admin/interviews/edit.blade.php' => 'A-033',
            'admin/announcements/index.blade.php' => 'A-034',
            'admin/announcements/create.blade.php' => 'A-035',
            'admin/announcements/edit.blade.php' => 'A-036',
            'teacher/evaluations/index.blade.php' => 'T-006',
            'teacher/evaluations/entry.blade.php' => 'T-007',
            'teacher/interviews/index.blade.php' => 'T-008',
            'teacher/interviews/create.blade.php' => 'T-009',
            'teacher/interviews/edit.blade.php' => 'T-010',
            'teacher/announcements/index.blade.php' => 'T-011',
            'student/evaluations/index.blade.php' => 'S-003',
            'student/announcements/index.blade.php' => 'S-004',
        ];
    }
}
