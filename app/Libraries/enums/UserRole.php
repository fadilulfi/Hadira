<?php

namespace App\Libraries\enums;

/**
 * UserRole enum maps to Shield group names.
 *
 * The enum value corresponds to the Shield group key used in
 * AuthGroups config. This is kept as a helper for display labels
 * and form options, but authorisation checks should use Shield's
 * $user->inGroup() or $user->can() directly.
 */
enum UserRole: string
{
  case Scanner     = 'scanner';
  case SuperAdmin  = 'superadmin';
  case Kepsek      = 'kepsek';
  case StafPetugas = 'admin';
  case Guru        = 'guru';
  case BK          = 'bk';
  case Waka        = 'waka';
  case Piket       = 'piket';

  public const ALL_ROLES = [
    self::SuperAdmin,
    self::Kepsek,
    self::StafPetugas,
    self::Scanner,
    self::Guru,
    self::BK,
    self::Waka,
    self::Piket,
  ];

  public const ALL_ROLES_WITH_GURU = [
    self::SuperAdmin,
    self::Kepsek,
    self::StafPetugas,
    self::Scanner,
    self::Guru,
    self::BK,
    self::Waka,
  ];

  public function label(): string
  {
    return match ($this) {
      self::Scanner     => 'Scanner',
      self::SuperAdmin  => 'Super Admin',
      self::Kepsek      => 'Kepsek',
      self::StafPetugas => 'Staf Petugas',
      self::Guru        => 'Guru',
      self::BK          => 'Guru BK',
      self::Waka        => 'Waka Kurikulum',
      self::Piket       => 'Petugas Piket',
    };
  }

  public function isSuperAdmin(): bool
  {
    return $this === self::SuperAdmin;
  }

  /**
   * Get the UserRole enum from a Shield group name.
   */
  public static function fromGroup(string $group): self
  {
    return self::tryFrom($group) ?? self::Scanner;
  }
}
