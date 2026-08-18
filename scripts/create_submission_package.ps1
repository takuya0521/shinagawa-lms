param(
    [string]$OutputPath = "shinagawa-lms_submission.zip"
)

$ErrorActionPreference = "Stop"

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$outputFullPath = if ([System.IO.Path]::IsPathRooted($OutputPath)) {
    $OutputPath
} else {
    Join-Path $projectRoot $OutputPath
}

$tempRoot = Join-Path ([System.IO.Path]::GetTempPath()) ("shinagawa-lms-submission-" + [Guid]::NewGuid().ToString("N"))
$stagingRoot = Join-Path $tempRoot "shinagawa-lms"

$excludedDirectories = @(
    ".git",
    "node_modules",
    "vendor",
    "public/build",
    "public/storage",
    "storage/pail"
)

$excludedFiles = @(
    ".phpunit.cache",
    ".phpunit.result.cache",
    "auth.json",
    ".DS_Store",
    "Thumbs.db"
)

$secretVariableNames = @(
    "APP_KEY",
    "DB_PASSWORD",
    "GOOGLE_WORKSPACE_CLIENT_SECRET",
    "GOOGLE_DRIVE_CLIENT_SECRET",
    "AWS_SECRET_ACCESS_KEY",
    "SLACK_BOT_USER_OAUTH_TOKEN"
)

function Test-IsExcludedPath {
    param(
        [string]$RelativePath
    )

    $normalizedPath = $RelativePath.Replace("\", "/")

    foreach ($directory in $excludedDirectories) {
        $normalizedDirectory = $directory.Replace("\", "/").TrimEnd("/")
        if ($normalizedPath -eq $normalizedDirectory -or $normalizedPath.StartsWith($normalizedDirectory + "/")) {
            return $true
        }
    }

    if ($excludedFiles -contains $normalizedPath) {
        return $true
    }

    # .env系はexampleファイルだけを配布し、実値を持ち得るローカル設定は除外する。
    if ($normalizedPath -match '(^|/)\.env(?:\..+)?$' -and $normalizedPath -notmatch '(^|/)\.env(?:\..+)?\.example$') {
        return $true
    }

    if ($normalizedPath -match '^database/.+\.sqlite$') {
        return $true
    }

    # 実行時データは除外し、Laravelが必要とする.gitignoreだけを残す。
    if ($normalizedPath -match '^storage/logs/(?!\.gitignore$).+') {
        return $true
    }

    if ($normalizedPath -match '^storage/framework/cache/data/(?!\.gitignore$).+') {
        return $true
    }

    if ($normalizedPath -match '^storage/framework/sessions/(?!\.gitignore$).+') {
        return $true
    }

    if ($normalizedPath -match '^storage/framework/views/(?!\.gitignore$).+') {
        return $true
    }

    # bootstrap/cacheはComposer/Laravelの生成物を除外し、.gitignoreだけを残す。
    if ($normalizedPath -match '^bootstrap/cache/(?!\.gitignore$).+') {
        return $true
    }

    if ($normalizedPath -eq [System.IO.Path]::GetFileName($outputFullPath)) {
        return $true
    }

    return $false
}

function Add-RuntimePlaceholder {
    param(
        [string]$RelativePath
    )

    $placeholderPath = Join-Path $stagingRoot $RelativePath
    $placeholderDirectory = Split-Path $placeholderPath -Parent
    New-Item -ItemType Directory -Path $placeholderDirectory -Force | Out-Null

    if (-not (Test-Path $placeholderPath)) {
        $utf8WithoutBom = New-Object System.Text.UTF8Encoding($false)
        [System.IO.File]::WriteAllText($placeholderPath, "*`n!.gitignore`n", $utf8WithoutBom)
    }
}

function Test-ContainsSecretAssignment {
    param(
        [string]$Contents
    )

    $secretNamePattern = ($secretVariableNames | ForEach-Object { [Regex]::Escape($_) }) -join '|'
    $envPattern = '(?mi)^[^\S\r\n]*(' + $secretNamePattern + ')[^\S\r\n]*=[^\S\r\n]*(?<value>[^\r\n]*)$'
    $xmlPattern = '(?mi)<env\s+name="(' + $secretNamePattern + ')"\s+value="(?<value>[^"]+)"'

    foreach ($match in [Regex]::Matches($Contents, $envPattern)) {
        $value = $match.Groups['value'].Value.Trim().Trim('"').Trim("'")
        if ($value -ne '') {
            return $true
        }
    }

    foreach ($match in [Regex]::Matches($Contents, $xmlPattern)) {
        if ($match.Groups['value'].Value.Trim() -ne '') {
            return $true
        }
    }

    if ($Contents -match '-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----') {
        return $true
    }

    if ($Contents -match '(?i)"private_key"\s*:\s*"-----BEGIN') {
        return $true
    }

    return $false
}

try {
    New-Item -ItemType Directory -Path $stagingRoot -Force | Out-Null

    Get-ChildItem -Path $projectRoot -Recurse -File | ForEach-Object {
        $relativePath = [System.IO.Path]::GetRelativePath($projectRoot, $_.FullName)

        if (Test-IsExcludedPath -RelativePath $relativePath) {
            return
        }

        $destinationPath = Join-Path $stagingRoot $relativePath
        $destinationDirectory = Split-Path $destinationPath -Parent
        New-Item -ItemType Directory -Path $destinationDirectory -Force | Out-Null
        Copy-Item -LiteralPath $_.FullName -Destination $destinationPath -Force
    }

    # 空ディレクトリがZIP化で消えても、Laravelの実行時ディレクトリを必ず復元できるようにする。
    @(
        'storage/framework/cache/data/.gitignore',
        'storage/framework/sessions/.gitignore',
        'storage/framework/views/.gitignore',
        'storage/logs/.gitignore',
        'bootstrap/cache/.gitignore'
    ) | ForEach-Object {
        Add-RuntimePlaceholder -RelativePath $_
    }

    $secretViolations = @()
    Get-ChildItem -Path $stagingRoot -Recurse -File | ForEach-Object {
        $relativePath = [System.IO.Path]::GetRelativePath($stagingRoot, $_.FullName).Replace("\", "/")

        if ($_.Name -match '(?i)(service[-_]?account|credentials?|client[-_]?secret).*\.(json|pem|p12|pfx|key)$') {
            $secretViolations += $relativePath
            return
        }

        if ($_.Extension -notin @('.php', '.xml', '.env', '.example', '.yml', '.yaml', '.json', '.md', '.txt', '.js', '.css')) {
            return
        }

        $contents = Get-Content -LiteralPath $_.FullName -Raw -ErrorAction SilentlyContinue
        if ($null -eq $contents) {
            return
        }

        if (Test-ContainsSecretAssignment -Contents $contents) {
            $secretViolations += $relativePath
        }
    }

    $secretViolations = @($secretViolations | Sort-Object -Unique)
    if ($secretViolations.Count -gt 0) {
        throw "秘密情報または認証ファイルの可能性があるファイルを検出しました: $($secretViolations -join ', ')"
    }

    if (Test-Path $outputFullPath) {
        Remove-Item $outputFullPath -Force
    }

    Compress-Archive -Path $stagingRoot -DestinationPath $outputFullPath -CompressionLevel Optimal

    Write-Host "提出用ZIPを作成しました: $outputFullPath"
    Write-Host "除外対象: .env / .git / vendor / node_modules / public/build / SQLite / 実行時キャッシュ / 生成済みbootstrap cache"
    Write-Host "秘密情報チェック: APP_KEY / DB_PASSWORD / Google・AWS・Slack secret / private key"
}
finally {
    if (Test-Path $tempRoot) {
        Remove-Item $tempRoot -Recurse -Force
    }
}
