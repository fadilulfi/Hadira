$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $root

$routes = Join-Path $root 'app\Config\Routes.php'
$sidebar = Join-Path $root 'app\Views\templates\sidebar.php'
$controller = Join-Path $root 'app\Controllers\Teacher\AbsensiKelas.php'

Write-Host '=============================================='
Write-Host 'INTEGRASI PANEL GURU - ABSENSI JAM PELAJARAN'
Write-Host '=============================================='

if (-not (Test-Path -LiteralPath $routes)) { throw "Tidak ditemukan: $routes" }
if (-not (Test-Path -LiteralPath $sidebar)) { throw "Tidak ditemukan: $sidebar" }

$stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$backupDir = Join-Path $root "teacher_module_backup_$stamp"
New-Item -ItemType Directory -Path $backupDir | Out-Null
Copy-Item -LiteralPath $routes -Destination (Join-Path $backupDir 'Routes.php')
Copy-Item -LiteralPath $sidebar -Destination (Join-Path $backupDir 'sidebar.php')

$enc = New-Object System.Text.UTF8Encoding($false)

# ---------------- ROUTES ----------------
$routeText = [IO.File]::ReadAllText($routes)
if ($routeText.IndexOf("teacher/absensi-kelas", [StringComparison]::OrdinalIgnoreCase) -ge 0) {
    Write-Host '[SKIP] Route Absensi Kelas sudah ada.' -ForegroundColor Yellow
} else {
    # Cari baris pembuka group teacher tanpa regex agar aman terhadap tanda $ dan backslash PowerShell.
    $groupMarker = "$routes->group('teacher'"
    $groupPos = $routeText.IndexOf($groupMarker, [StringComparison]::OrdinalIgnoreCase)
    if ($groupPos -lt 0) {
        throw "Pembuka blok route teacher tidak ditemukan di Routes.php. Backup: $backupDir"
    }

    $lineEnd = $routeText.IndexOf("`n", $groupPos)
    if ($lineEnd -lt 0) { $lineEnd = $routeText.Length }

    $newRoutes = @"
    // === ABSENSI KELAS GURU (MODUL BARU) ===
    `$routes->get('absensi-kelas', 'Teacher\AbsensiKelas::index');
    `$routes->get('absensi-kelas/(:any)', 'Teacher\AbsensiKelas::daftar/`$1');
    `$routes->post('absensi-kelas/simpan', 'Teacher\AbsensiKelas::simpan');
    `$routes->get('absensi-kelas/current.json', 'Teacher\AbsensiKelas::currentJson');
"@

    $routeText = $routeText.Substring(0, $lineEnd + 1) + $newRoutes.TrimStart() + $routeText.Substring($lineEnd + 1)
    [IO.File]::WriteAllText($routes, $routeText, $enc)
    Write-Host '[OK] Route Absensi Kelas ditambahkan.' -ForegroundColor Green
}

# ---------------- SIDEBAR ----------------
$sidebarText = [IO.File]::ReadAllText($sidebar)
if ($sidebarText.IndexOf("teacher/absensi-kelas", [StringComparison]::OrdinalIgnoreCase) -ge 0) {
    Write-Host '[SKIP] Menu Absensi Jam Pelajaran sudah ada.' -ForegroundColor Yellow
} else {
    $menuLine = "       ['title' => 'Absensi Jam Pelajaran', 'url' => 'teacher/absensi-kelas', 'icon' => 'fact_check', 'context' => 'teacher-absensi-kelas'],"

    # Cari baris menu dashboard guru/wali kelas. Tidak memakai regex.
    $dashboardMarkers = @(
        "'context' => 'teacher-dashboard'",
        "'url' => 'teacher/dashboard'",
        "'url' => 'teacher'"
    )
    $inserted = $false
    foreach ($marker in $dashboardMarkers) {
        $p = $sidebarText.IndexOf($marker, [StringComparison]::OrdinalIgnoreCase)
        if ($p -ge 0) {
            $lineStart = $sidebarText.LastIndexOf("`n", $p)
            if ($lineStart -lt 0) { $lineStart = 0 } else { $lineStart += 1 }
            $lineEnd = $sidebarText.IndexOf("`n", $p)
            if ($lineEnd -lt 0) { $lineEnd = $sidebarText.Length }
            $line = $sidebarText.Substring($lineStart, $lineEnd - $lineStart)
            $indent = ($line -replace '^([ \t]*).*$', '$1')
            $menu = $indent + "['title' => 'Absensi Jam Pelajaran', 'url' => 'teacher/absensi-kelas', 'icon' => 'fact_check', 'context' => 'teacher-absensi-kelas'],"
            $sidebarText = $sidebarText.Substring(0, $lineEnd + 1) + $menu + "`r`n" + $sidebarText.Substring($lineEnd + 1)
            [IO.File]::WriteAllText($sidebar, $sidebarText, $enc)
            Write-Host '[OK] Menu Absensi Jam Pelajaran ditambahkan.' -ForegroundColor Green
            $inserted = $true
            break
        }
    }
    if (-not $inserted) {
        Write-Host '[PERINGATAN] Menu Dashboard Guru tidak ditemukan. Route tetap dipasang; menu bisa ditambahkan manual.' -ForegroundColor Yellow
    }
}

# ---------------- SYNTAX CHECK ----------------
$php = 'C:\xampp\php\php.exe'
if (Test-Path -LiteralPath $php) {
    & $php -l $routes
    if ($LASTEXITCODE -ne 0) { throw 'Syntax error pada Routes.php. Backup tersedia.' }
    & $php -l $sidebar
    if ($LASTEXITCODE -ne 0) { throw 'Syntax error pada sidebar.php. Backup tersedia.' }
    if (Test-Path -LiteralPath $controller) {
        & $php -l $controller
        if ($LASTEXITCODE -ne 0) { throw 'Syntax error pada AbsensiKelas.php.' }
    } else {
        Write-Host '[PERINGATAN] Controller AbsensiKelas.php tidak ditemukan.' -ForegroundColor Yellow
    }
}

Write-Host ''
Write-Host '==============================================' -ForegroundColor Green
Write-Host 'INTEGRASI PANEL GURU SELESAI' -ForegroundColor Green
Write-Host "Backup: $backupDir" -ForegroundColor Green
Write-Host '==============================================' -ForegroundColor Green
Write-Host 'Selanjutnya jalankan: C:\xampp\php\php.exe spark cache:clear'
