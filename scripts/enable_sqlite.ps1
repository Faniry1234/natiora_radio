<#
Enable SQLite extensions in common php.ini files and attempt to restart Apache.
Run PowerShell as Administrator.
#>
function Find-PHPIni {
    $results = @()
    try {
        $out = & php --ini 2>&1
        foreach ($line in $out) {
            if ($line -match 'Loaded Configuration File') {
                $parts = $line -split ':' ,2
                if ($parts.Length -ge 2) { $path = $parts[1].Trim(); if ($path -and (Test-Path $path)) { $results += $path } }
            }
        }
    } catch {}
    $common = @(
        'C:\xampp\php\php.ini',
        'C:\wamp64\bin\php\php.ini',
        'C:\php\php.ini',
        'C:\Program Files\PHP\php.ini',
        'C:\Program Files (x86)\PHP\php.ini'
    )
    foreach ($p in $common) { if ((Test-Path $p) -and -not ($results -contains $p)) { $results += $p } }
    return $results | Select-Object -Unique
}

function Enable-ExtensionsInPhpIni($phpIniPath) {
    Write-Host "Processing: $phpIniPath"
    $bak = "$phpIniPath.bak_$(Get-Date -Format 'yyyyMMddHHmmss')"
    Copy-Item -Path $phpIniPath -Destination $bak -Force
    $content = Get-Content -Raw -Encoding UTF8 $phpIniPath
    $orig = $content
    # uncomment common sqlite extensions
    $content = $content -replace "(?m)^\s*;\s*extension=php_pdo_sqlite.dll", "extension=php_pdo_sqlite.dll"
    $content = $content -replace "(?m)^\s*;\s*extension=php_sqlite3.dll", "extension=php_sqlite3.dll"
    $content = $content -replace "(?m)^\s*;\s*extension=pdo_sqlite", "extension=pdo_sqlite"
    $content = $content -replace "(?m)^\s*;\s*extension=sqlite3", "extension=sqlite3"
    # If DLL names not present, try adding them near other extension lines
    if ($content -eq $orig) {
        Write-Host "No changes by uncomment rules; attempting to append extension lines if not present." -ForegroundColor Yellow
        if ($content -notmatch 'php_pdo_sqlite') { $content += "`r`nextension=php_pdo_sqlite.dll`r`n" }
        if ($content -notmatch 'php_sqlite3') { $content += "extension=php_sqlite3.dll`r`n" }
    }
    Set-Content -Path $phpIniPath -Value $content -Encoding UTF8
    Write-Host "Backed up original to: $bak"
}

function Restart-ApacheServices {
    $candidates = Get-Service | Where-Object { $_.Name -match 'apache|httpd|wamp' -or $_.DisplayName -match 'apache|httpd|wamp' }
    if (-not $candidates) { Write-Host 'No Apache-like services found to restart.'; return }
    foreach ($s in $candidates) {
        try {
            Write-Host "Restarting service: $($s.Name) ($($s.DisplayName))"
            Restart-Service -Name $s.Name -Force -ErrorAction Stop
            Write-Host "Restarted $($s.Name)"
        } catch {
            Write-Host "Failed to restart $($s.Name): $_" -ForegroundColor Red
        }
    }
}

# Main
$inis = Find-PHPIni
if (-not $inis) {
    Write-Host 'No php.ini candidates found. Please run info.php in browser and provide the Loaded Configuration File path.' -ForegroundColor Red
    exit 1
}
Write-Host "Found php.ini candidates:`n" -NoNewline
$inis | ForEach-Object { Write-Host " - $_" }

$confirm = Read-Host 'Proceed to enable sqlite extensions in these files and restart Apache services? (Y/N)'
if ($confirm -notin @('Y','y','Yes','yes')) { Write-Host 'Aborted by user.'; exit 0 }

foreach ($ini in $inis) { Enable-ExtensionsInPhpIni $ini }
Restart-ApacheServices

Write-Host "Done. Please open http://localhost:8000/info.php and verify pdo_sqlite and sqlite3 are listed." -ForegroundColor Green
