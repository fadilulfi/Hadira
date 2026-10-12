<?php
$user = auth()->user();
$context = $ctx ?? 'dashboard';

$isSuperAdmin = $user && $user->inGroup('superadmin');
$isAdmin = $user && $user->inGroup('admin');
$isGuru = $user && $user->inGroup('guru');
$isBK   = $user && $user->inGroup('bk');
$isWaka = $user && $user->inGroup('waka');
$isScanner = $user && $user->inGroup('scanner');
$isKepsek = $user && $user->inGroup('kepsek');

if ($isSuperAdmin) {
    $sidebarColor = 'azure';
} elseif ($isBK) {
    $sidebarColor = 'rose';
} elseif ($isWaka) {
    $sidebarColor = 'purple';
} elseif ($isGuru) {
    $sidebarColor = 'green';
} elseif (in_array($context, ['absen-siswa', 'siswa', 'kelas'], true)) {
    $sidebarColor = 'purple';
} elseif ($context === 'absen-guru') {
    $sidebarColor = 'green';
} elseif (in_array($context, ['qr', 'backup'], true)) {
    $sidebarColor = 'danger';
} else {
    $sidebarColor = 'azure';
}

if ($isSuperAdmin) $roleLabel = 'Super Administrator';
elseif ($isBK) $roleLabel = 'Guru BK';
elseif ($isWaka) $roleLabel = 'Waka Kurikulum';
elseif ($isAdmin) $roleLabel = 'Admin Monitoring';
elseif ($isGuru) $roleLabel = 'Guru';
elseif ($isKepsek) $roleLabel = 'Kepala Sekolah';
elseif ($isScanner) $roleLabel = 'Petugas Scanner';
else $roleLabel = 'Operator Petugas Absensi';

$guruMenu = [
    ['title' => 'Dashboard Guru',          'url' => 'teacher/dashboard',    'icon' => 'dashboard',       'context' => 'teacher-dashboard'],
    ['title' => 'Absensi Jam Pelajaran', 'url' => 'teacher/absensi-kelas', 'icon' => 'fact_check',     'context' => 'teacher-absensi-kelas'],
    ['title' => 'Jadwal Saya',            'url' => 'teacher/jadwal',       'icon' => 'calendar_month', 'context' => 'teacher-jadwal'],
    ['title' => 'Pengajuan Izin',         'url' => 'teacher/perizinan',    'icon' => 'mail',           'context' => 'teacher-perizinan'],
    ['title' => 'Laporan Kelas',          'url' => 'teacher/laporan',      'icon' => 'print',          'context' => 'teacher-laporan'],
    ['title' => 'QR Code Siswa',           'url' => 'teacher/qr',           'icon' => 'qr_code',       'context' => 'teacher-qr'],
];

$bkMenu = [
    ['title' => 'Dashboard BK',          'url' => 'bk/dashboard',  'icon' => 'dashboard',        'context' => 'bk-dashboard'],
    ['title' => 'Monitoring Kehadiran', 'url' => 'bk/monitoring', 'icon' => 'monitoring',       'context' => 'class-attendance'],
    ['title' => 'Data Izin & Sakit',    'url' => 'bk/perizinan',  'icon' => 'medical_services', 'context' => 'bk-perizinan'],
    ['title' => 'Rekap Kehadiran',      'url' => 'bk/laporan',    'icon' => 'assessment',       'context' => 'bk-laporan'],
];

$wakaMenu = [
    ['title' => 'Dashboard Waka',      'url' => 'waka/dashboard',  'icon' => 'dashboard',       'context' => 'waka-dashboard'],
    ['title' => 'Jadwal Pelajaran',    'url' => 'waka/jadwal',     'icon' => 'calendar_month', 'context' => 'waka-jadwal'],
    ['title' => 'Mata Pelajaran',      'url' => 'waka/mapel',      'icon' => 'menu_book',      'context' => 'waka-mapel'],
    ['title' => 'Jam Pelajaran',       'url' => 'waka/jam',        'icon' => 'schedule',       'context' => 'waka-jam'],
    ['title' => 'Monitoring Absensi',  'url' => 'waka/monitoring', 'icon' => 'monitoring',      'context' => 'waka-monitoring'],
    ['title' => 'Data Izin & Sakit',   'url' => 'waka/perizinan', 'icon' => 'medical_services', 'context' => 'waka-perizinan'],
    ['title' => 'Rekap Absensi',       'url' => 'waka/laporan',    'icon' => 'assessment',      'context' => 'waka-laporan'],
];

$adminMenus = [
   ['title' => 'Dashboard',            'url' => 'admin/dashboard',        'icon' => 'dashboard',  'context' => 'admin-dashboard',  'perm' => 'admin.access'],
   ['title' => 'Absen Masuk',          'url' => 'scan/masuk',              'icon' => 'login',      'context' => 'scan-masuk',         'perm' => 'admin.access'],
   ['title' => 'Absen Pulang',         'url' => 'scan/pulang',             'icon' => 'logout',     'context' => 'scan-pulang',        'perm' => 'admin.access'],
   ['title' => 'Absensi Siswa',        'url' => 'admin/absen-siswa',      'icon' => 'checklist',  'context' => 'absen-siswa',       'perm' => 'attendance.edit'],
   ['title' => 'Absensi Guru',         'url' => 'admin/absen-guru',       'icon' => 'checklist',  'context' => 'absen-guru',        'perm' => 'attendance.edit'],
   ['title' => 'Data Perizinan',       'url' => 'admin/perizinan',        'icon' => 'mail',       'context' => 'perizinan',        'perm' => 'attendance.edit'],
   ['title' => 'Hari Libur',           'url' => 'admin/holiday',          'icon' => 'event_busy', 'context' => 'holiday',           'perm' => 'settings.manage'],
   ['title' => 'Data Siswa',           'url' => 'admin/siswa',             'icon' => 'person',     'context' => 'siswa',             'perm' => 'students.manage'],
   ['title' => 'Data Guru',            'url' => 'admin/guru',               'icon' => 'person_4',   'context' => 'guru',              'perm' => 'teachers.manage'],
   ['title' => 'Data Kelas & Jurusan', 'url' => 'admin/kelas',              'icon' => 'school',     'context' => 'kelas',             'perm' => 'classes.manage'],
   ['title' => 'Generate QR Code',     'url' => 'admin/generate',           'icon' => 'qr_code',    'context' => 'admin-qr',          'perm' => 'qr.generate'],
   ['title' => 'Generate Laporan',     'url' => 'admin/laporan',            'icon' => 'print',      'context' => 'laporan',           'perm' => 'attendance.view'],
   ['title' => 'Data Petugas',         'url' => 'admin/petugas',             'icon' => 'computer',   'context' => 'petugas',            'perm' => 'petugas.manage'],
   ['title' => 'Pengaturan',           'url' => 'admin/general-settings',    'icon' => 'settings',   'context' => 'general_settings',  'perm' => 'settings.manage'],
   ['title' => 'Audit Log',            'url' => 'admin/audit-log',           'icon' => 'history',    'context' => 'audit-log',          'perm' => 'admin.access'],
   ['title' => 'Backup & Restore',     'url' => 'admin/backup',              'icon' => 'backup',     'context' => 'backup',             'perm' => 'backup.manage'],
];

$adminMonitoringMenus = [
   ['title' => 'Dashboard',        'url' => 'admin/dashboard',   'icon' => 'dashboard', 'context' => 'admin-dashboard', 'perm' => 'dashboard.view-admin'],
   ['title' => 'Absensi Siswa',    'url' => 'admin/absen-siswa', 'icon' => 'checklist', 'context' => 'absen-siswa',      'perm' => 'attendance.monitor'],
   ['title' => 'Absensi Guru',     'url' => 'admin/absen-guru',  'icon' => 'checklist', 'context' => 'absen-guru',       'perm' => 'attendance.monitor'],
   ['title' => 'Generate Laporan', 'url' => 'admin/laporan',     'icon' => 'print',     'context' => 'laporan',          'perm' => 'attendance.view'],
];

$adminItems = [];
$adminSectionLabel = 'Administrasi';
if ($isSuperAdmin) {
    foreach ($adminMenus as $m) if ($user->can($m['perm'])) $adminItems[] = $m;
} elseif ($isAdmin || $isKepsek) {
    $adminSectionLabel = 'Monitoring Absensi';
    foreach ($adminMonitoringMenus as $m) if ($user->can($m['perm'])) $adminItems[] = $m;
}

if ($isSuperAdmin) {
    $rolePanels = [
        ['label' => 'Panel Guru', 'items' => $guruMenu],
        ['label' => 'Panel BK', 'items' => $bkMenu],
        ['label' => 'Panel Waka Kurikulum', 'items' => $wakaMenu],
    ];
} elseif ($isBK) {
    $rolePanels = [['label' => 'Menu BK', 'items' => $bkMenu]];
} elseif ($isWaka) {
    $rolePanels = [['label' => 'Menu Waka Kurikulum', 'items' => $wakaMenu]];
} elseif ($isAdmin || $isKepsek) {
    $rolePanels = [];
} elseif ($isGuru) {
    $rolePanels = [['label' => 'Menu Guru', 'items' => $guruMenu]];
} else {
    $rolePanels = [];
}
?>
<div class="sidebar" data-color="<?= $sidebarColor; ?>" data-image="<?= base_url('assets/img/sidebar/sidebar-3.jpg'); ?>">
   <div class="logo">
      <a class="simple-text logo-normal">
         <b><?= esc($roleLabel); ?></b><br>
         <small><?= esc($generalSettings->school_name); ?></small>
      </a>
   </div>
   <div class="sidebar-wrapper">
      <ul class="nav">
<?php foreach ($rolePanels as $panel): ?>
         <li class="nav-item"><p class="nav-link py-1"><?= esc($panel['label']); ?></p></li>
         <?php foreach ($panel['items'] as $item): ?>
         <?php
            // Keep the parent menu active while viewing a specific class-attendance schedule.
            $isActive = $context === $item['context']
                || ($item['context'] === 'teacher-absensi-kelas' && $context === 'teacher-absensi-kelas-form');
         ?>
         <li class="nav-item <?= $isActive ? 'active' : ''; ?>">
            <a class="nav-link font-weight-bold" href="<?= base_url($item['url']); ?>" data-hadira-nav="true" data-nav-context="<?= esc($item['context']); ?>" <?= $isActive ? 'aria-current="page"' : ''; ?>>
               <i class="material-icons"><?= $item['icon']; ?></i>
               <p><?= esc($item['title']); ?></p>
            </a>
         </li>
         <?php endforeach; ?>
<?php endforeach; ?>
<?php if ($adminItems !== []): ?>
         <li class="nav-item"><p class="nav-link py-1"><?= esc($adminSectionLabel); ?></p></li>
         <?php foreach ($adminItems as $item): ?>
         <li class="nav-item <?= $context === $item['context'] ? 'active' : ''; ?>">
            <a class="nav-link font-weight-bold" href="<?= base_url($item['url']); ?>" data-hadira-nav="true" data-nav-context="<?= esc($item['context']); ?>">
               <i class="material-icons"><?= $item['icon']; ?></i>
               <p><?= esc($item['title']); ?></p>
            </a>
         </li>
         <?php endforeach; ?>
<?php endif; ?>
<?php if ($rolePanels === [] && $adminItems === [] && $isScanner): ?>
         <li class="nav-item <?= $context === 'scan' ? 'active' : ''; ?>">
            <a class="nav-link font-weight-bold" href="<?= base_url('scan'); ?>" data-hadira-nav="true" data-nav-context="scan">
               <i class="material-icons">qr_code</i><p>Scan QR</p>
            </a>
         </li>
<?php endif; ?>
      </ul>
   </div>
</div>
