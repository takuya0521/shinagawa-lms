<?php

$root = dirname(__DIR__);
$viewsRoot = $root.'/resources/views';

$screenIds = [
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

$errors = [];
$visibleIdFiles = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsRoot));
foreach ($iterator as $file) {
    if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($viewsRoot) + 1));
    $contents = file_get_contents($file->getPathname());
    if (! is_string($contents)) {
        $errors[] = "読み込み不可: {$relative}";

        continue;
    }

    preg_match_all('/>([CAST]-\d{3})(?:（[^<]*）)?</u', $contents, $matches);
    $actualIds = array_values(array_unique($matches[1]));
    if ($actualIds !== []) {
        $visibleIdFiles[$relative] = $actualIds;
    }

    if ($relative !== 'layouts/app.blade.php'
        && preg_match("/session\('(status|success)'\)/", $contents) === 1) {
        $errors[] = "共通フィードバックと重複: {$relative}";
    }
}

ksort($visibleIdFiles);
ksort($screenIds);
if (array_keys($visibleIdFiles) !== array_keys($screenIds)) {
    $errors[] = '画面ID表示ファイル一覧が設計マップと一致しません。';
}

foreach ($screenIds as $relative => $expectedId) {
    $actualIds = $visibleIdFiles[$relative] ?? [];
    if ($actualIds !== [$expectedId]) {
        $errors[] = sprintf(
            '画面ID不一致: %s expected=%s actual=%s',
            $relative,
            $expectedId,
            implode(',', $actualIds),
        );
    }
}

$externalController = file_get_contents($root.'/app/Http/Controllers/Student/ExternalServiceController.php');
if (! is_string($externalController)
    || substr_count($externalController, "'screenId' => 'S-006'") !== 1
    || substr_count($externalController, "'screenId' => 'S-007'") !== 1
    || substr_count($externalController, "'screenId' => 'S-008'") !== 1) {
    $errors[] = '生徒外部サービス画面のID定義がS-006/S-007/S-008と一致しません。';
}

if (is_file($viewsRoot.'/layouts/student-dashboard.blade.php')
    || is_file($root.'/resources/css/student-dashboard.css')) {
    $errors[] = '未使用のstudent-dashboard資産が残っています。';
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors).PHP_EOL);
    exit(1);
}

fwrite(
    STDOUT,
    sprintf(
        "画面・共通表示整合チェック完了: 画面ID表示 %d件 / 重複フィードバック 0件 / 外部サービスID 正常\n",
        count($screenIds),
    ),
);
