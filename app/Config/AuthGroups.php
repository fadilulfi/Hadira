<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    public string $defaultGroup = 'user';

    public array $groups = [
        'superadmin' => [
            'title'       => 'Super Admin',
            'description' => 'Akses penuh ke seluruh fitur aplikasi.',
        ],
        'admin' => [
            'title'       => 'Admin Monitoring',
            'description' => 'Memantau absensi harian dan dapat mengoreksi absensi jam pelajaran yang sudah tersimpan.',
        ],
        'kepsek' => [
            'title'       => 'Kepala Sekolah',
            'description' => 'Melihat laporan absensi.',
        ],
        'scanner' => [
            'title'       => 'Scanner',
            'description' => 'Hanya dapat melakukan scan QR untuk presensi.',
        ],
        'guru' => [
            'title'       => 'Guru',
            'description' => 'Mengisi absensi kelas sesuai jadwal mengajar.',
        ],
        'bk' => [
            'title'       => 'Guru BK',
            'description' => 'Memantau kehadiran, izin, sakit, alpa, dan tindak lanjut siswa.',
        ],
        'waka' => [
            'title'       => 'Waka Kurikulum',
            'description' => 'Mengelola jadwal pelajaran dan memantau pelaksanaan absensi kelas.',
        ],
        'piket' => [
            'title'       => 'Petugas Piket',
            'description' => 'Menangani pengajuan izin dan sakit siswa.',
        ],
    ];

    public array $permissions = [
        // Legacy permissions — jangan dihapus agar sistem lama tetap berjalan.
        'dashboard.view-admin'       => 'Dapat melihat dashboard admin',
        'admin.access'               => 'Dapat mengakses area admin',
        'students.manage'            => 'Dapat mengelola data siswa',
        'teachers.manage'            => 'Dapat mengelola data guru',
        'classes.manage'             => 'Dapat mengelola data kelas dan jurusan',
        'attendance.edit'            => 'Dapat mengubah data presensi pada fitur guru yang berwenang',
        'attendance.view'            => 'Dapat melihat laporan presensi',
        'attendance.monitor'         => 'Dapat melihat detail absensi harian tanpa mengubahnya',
        'attendance.override'        => 'Dapat mengoreksi catatan absensi melalui panel Super Admin',
        'scan.access'                => 'Dapat mengakses pemindaian QR untuk presensi',
        'class_attendance.record'    => 'Dapat mencatat absensi jam pelajaran sebagai guru yang berwenang',
        'qr.generate'                => 'Dapat generate QR Code',
        'petugas.manage'             => 'Dapat mengelola akun petugas',
        'settings.manage'            => 'Dapat mengelola pengaturan aplikasi',
        'backup.manage'              => 'Dapat melakukan backup dan restore',
        'teacher.access'             => 'Dapat mengakses dashboard guru/wali kelas',

        // Role/panel baru.
        'guru.access'                => 'Dapat mengakses panel guru',
        'bk.access'                  => 'Dapat mengakses panel BK',
        'waka.access'                => 'Dapat mengakses panel Waka Kurikulum',
        'piket.access'               => 'Dapat mengakses panel Piket',
        'schedule.view'              => 'Dapat melihat jadwal pelajaran',
        'schedule.manage'            => 'Dapat mengelola jadwal pelajaran',
        'class_attendance.view'      => 'Dapat melihat absensi jam pelajaran',
        'class_attendance.manage'    => 'Dapat mengisi dan mengelola absensi jam pelajaran',
        'class_attendance.correct'   => 'Dapat mengoreksi daftar hadir jam pelajaran yang tersimpan',
        'absence_monitoring.view'    => 'Dapat memantau kehadiran siswa',
        'permission.manage'          => 'Dapat mengelola izin/sakit siswa',
    ];

    public array $matrix = [
        'superadmin' => [
            'dashboard.view-admin',
            'admin.*', 'students.*', 'teachers.*', 'classes.*',
            'attendance.*', 'qr.*', 'petugas.*', 'settings.*', 'backup.*', 'scan.*',
            'teacher.*', 'guru.*', 'bk.*', 'waka.*', 'piket.*',
            'schedule.*', 'class_attendance.*', 'absence_monitoring.*', 'permission.*',
        ],
        'admin' => [
            'dashboard.view-admin', 'admin.access',
            'attendance.monitor', 'attendance.view',
            'class_attendance.correct',
        ],
        'kepsek' => [
            'dashboard.view-admin', 'admin.access', 'attendance.monitor', 'attendance.view',
        ],
        'scanner' => [
            'admin.access', 'attendance.view', 'scan.access',
        ],
        'guru' => [
            'guru.access', 'teacher.access', 'attendance.edit', 'attendance.view',
            'schedule.view', 'class_attendance.view', 'class_attendance.manage',
            'class_attendance.record',
        ],
        'bk' => [
            'bk.access', 'schedule.view', 'class_attendance.view',
            'class_attendance.correct', 'absence_monitoring.view', 'permission.manage',
        ],
        'waka' => [
            'waka.access', 'schedule.view', 'schedule.manage',
            'class_attendance.view', 'class_attendance.correct',
            'absence_monitoring.view', 'permission.manage',
        ],
        'piket' => [
            'piket.access', 'permission.manage',
        ],
    ];
}
