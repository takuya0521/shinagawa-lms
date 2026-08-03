$ErrorActionPreference = 'Stop'

$targets = @(
    'resources/css/student-dashboard.css',
    'resources/views/layouts/student-dashboard.blade.php'
)

foreach ($target in $targets) {
    if (Test-Path $target) {
        Remove-Item $target -Force
        Write-Host "削除しました: $target"
    } else {
        Write-Host "対象なし: $target"
    }
}

Write-Host '削除処理が完了しました。'
