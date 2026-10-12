<?php

use App\Libraries\enums\UserRole;

if (! function_exists('user')) {
    function user()
    {
        return auth()->user();
    }
}

/**
 * Get the highest-priority role group for the current user.
 *
 * Priority: superadmin > admin > kepsek > waka > bk > scanner > guru
 * Monitoring roles take precedence over a secondary Guru group.
 */
function user_role(): ?string
{
    $u = user();
    if ($u === null) {
        return null;
    }

    $groups = $u->getGroups();
    if (empty($groups)) {
        return null;
    }

    $priority = ['superadmin', 'admin', 'kepsek', 'waka', 'bk', 'scanner', 'guru'];

    foreach ($priority as $group) {
        if (in_array($group, $groups, true)) {
            return $group;
        }
    }

    return $groups[0];
}

/**
 * Get the display label for a role (from group name or UserRole enum).
 */
function getUserRole(string|int $role): string
{
    // If it's an old integer value (from before migration), convert it
    if (is_numeric($role)) {
        $oldMap = [
            0 => 'scanner',
            1 => 'superadmin',
            2 => 'kepsek',
            3 => 'admin',
        ];
        $role = $oldMap[(int) $role] ?? 'scanner';
    }

    return UserRole::fromGroup($role)->label();
}

/**
 * Apakah user memiliki profil guru (terhubung ke tb_guru)?
 */
function is_guru(): bool
{
    $user = user();
    return $user !== null && ! empty($user->id_guru);
}

/**
 * Apakah user adalah wali kelas yang terdaftar di tb_kelas?
 */
function is_wali_kelas(): bool
{
    $user = user();
    if ($user === null || empty($user->id_guru)) {
        return false;
    }

    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $db = \Config\Database::connect();
    $cached = $db->table('tb_kelas')
        ->where('id_wali_kelas', $user->id_guru)
        ->countAllResults() > 0;

    return $cached;
}

function is_superadmin(): bool
{
    $user = user();
    return $user !== null && $user->inGroup('superadmin');
}

function is_kepsek(): bool
{
    $user = user();
    return $user !== null && $user->inGroup('kepsek');
}

function can_edit_attendance(): bool
{
    $user = user();
    return $user !== null && $user->can('attendance.edit');
}

/**
 * Correct saved lesson-attendance records for the roles explicitly permitted.
 * Teachers use the separate same-day, own-schedule workflow.
 */
function can_correct_class_attendance(): bool
{
    $user = user();
    if ($user === null) {
        return false;
    }

    if ($user->inGroup('superadmin')) {
        return true;
    }

    return $user->can('class_attendance.correct')
        && (
            $user->inGroup('admin')
            || $user->inGroup('waka')
            || $user->inGroup('bk')
        );
}

/**
 * Daily attendance monitoring is read-only for Admin, Principal, and Super Admin.
 */
function can_monitor_daily_attendance(): bool
{
    $user = user();

    return $user !== null
        && $user->can('attendance.monitor')
        && (
            $user->inGroup('superadmin')
            || $user->inGroup('admin')
            || $user->inGroup('kepsek')
        );
}

/**
 * Only Super Admin may correct attendance through the legacy administrative pages.
 */
function can_override_attendance(): bool
{
    $user = user();

    return $user !== null
        && $user->inGroup('superadmin')
        && $user->can('attendance.override');
}

/**
 * Monitoring-role membership takes precedence over a secondary Guru group.
 */
function can_record_teacher_attendance(): bool
{
    $user = user();
    if ($user === null) {
        return false;
    }

    if ($user->inGroup('superadmin')) {
        return true;
    }

    if (!$user->inGroup('guru') || !$user->can('class_attendance.record')) {
        return false;
    }

    foreach (['admin', 'bk', 'waka', 'kepsek', 'scanner'] as $monitoringRole) {
        if ($user->inGroup($monitoringRole)) {
            return false;
        }
    }

    return true;
}

/**
 * Only a dedicated Scanner account or Super Admin may access QR attendance.
 */
function can_use_attendance_scanner(): bool
{
    $user = user();
    if ($user === null || !$user->can('scan.access')) {
        return false;
    }

    if ($user->inGroup('superadmin')) {
        return true;
    }

    if (!$user->inGroup('scanner')) {
        return false;
    }

    foreach (['admin', 'bk', 'waka', 'kepsek', 'guru'] as $monitoringRole) {
        if ($user->inGroup($monitoringRole)) {
            return false;
        }
    }

    return true;
}

function can_generate_qr(): bool
{
    $user = user();
    return $user !== null && $user->can('qr.generate');
}

function can_view_report(): bool
{
    $user = user();
    return $user !== null && $user->can('attendance.view');
}
