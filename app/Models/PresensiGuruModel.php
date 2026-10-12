<?php

namespace App\Models;

use App\Models\PresensiInterface;
use CodeIgniter\I18n\Time;
use CodeIgniter\Model;
use App\Libraries\enums\Kehadiran;

class PresensiGuruModel extends Model implements PresensiInterface
{
   protected $primaryKey = 'id_presensi';

   protected $allowedFields = [
      'id_guru',
      'tanggal',
      'jam_masuk',
      'jam_keluar',
      'id_kehadiran',
      'keterangan'
   ];

   protected $table = 'tb_presensi_guru';

   public function cekAbsen(string|int $id, string|Time $date)
   {
      $result = $this->where(['id_guru' => $id, 'tanggal' => $date])->first();

      if (empty($result))
         return false;

      return $result[$this->primaryKey];
   }

   public function absenMasuk(string $id, $date, $time)
   {
      $this->save([
         'id_guru' => $id,
         'tanggal' => $date,
         'jam_masuk' => $time,
         // 'jam_keluar' => '',
         'id_kehadiran' => Kehadiran::Hadir->value,
         'keterangan' => ''
      ]);
   }

   public function absenKeluar(string $id, $time)
   {
      $this->update($id, [
         'jam_keluar' => $time,
         'keterangan' => ''
      ]);
   }

   public function getPresensiByIdGuruTanggal($idGuru, $date)
   {
      return $this->where(['id_guru' => $idGuru, 'tanggal' => $date])->first();
   }

   public function getPresensiById(string $idPresensi)
   {
      return $this->where([$this->primaryKey => $idPresensi])->first();
   }

   public function getPresensiByTanggal($tanggal): array
   {
      return $this->db->table('tb_guru')
         ->select('*')
         ->join(
            "(SELECT id_presensi, id_guru AS id_guru_presensi, tanggal, jam_masuk, jam_keluar, id_kehadiran, keterangan FROM tb_presensi_guru) tb_presensi_guru",
            "tb_guru.id_guru = tb_presensi_guru.id_guru_presensi AND tb_presensi_guru.tanggal = '$tanggal'",
            'left'
         )
         ->join(
            'tb_kehadiran',
            'tb_presensi_guru.id_kehadiran = tb_kehadiran.id_kehadiran',
            'left'
         )
         ->orderBy("nama_guru")
         ->get()
         ->getResultArray();
   }

   public function getPresensiByKehadiran(string $idKehadiran, $tanggal)
   {
      $this->join(
         'tb_guru',
         "tb_presensi_guru.id_guru = tb_guru.id_guru AND tb_presensi_guru.tanggal = '$tanggal'",
         'right'
      );

      if ($idKehadiran == '4') {
         $result = $this->findAll();

         $schoolConfigurations = new \Config\School();
         $generalSettings = $schoolConfigurations::$generalSettings;
         $jamPulangStandard = $generalSettings->jam_pulang_standard ?? '14:00:00';
         
         $now = Time::now();
         $nowTime = $now->toTimeString();
         $today = $now->toDateString();
         $isAfterSchool = ($today > $tanggal) || ($today == $tanggal && $nowTime > $jamPulangStandard);

         $filteredResult = [];

         foreach ($result as $value) {
            if (!in_array($value['id_kehadiran'], ['1', '2', '3'])) {
               $value['is_alfa_final'] = $isAfterSchool;
               array_push($filteredResult, $value);
            }
         }

         return $filteredResult;
      } else {
         $this->where(['tb_presensi_guru.id_kehadiran' => $idKehadiran]);
         return $this->findAll();
      }
   }

   /**
    * Return teacher attendance counts without loading matching rows into PHP.
    */
   public function getAttendanceSummaryForDate(string $tanggal): array
   {
      $row = $this->db->table('tb_presensi_guru ps')
         ->select("SUM(CASE WHEN ps.id_kehadiran = 1 THEN 1 ELSE 0 END) AS hadir", false)
         ->select("SUM(CASE WHEN ps.id_kehadiran = 2 THEN 1 ELSE 0 END) AS sakit", false)
         ->select("SUM(CASE WHEN ps.id_kehadiran = 3 THEN 1 ELSE 0 END) AS izin", false)
         ->select("COUNT(DISTINCT CASE WHEN ps.id_kehadiran IN (1, 2, 3) THEN ps.id_guru END) AS tercatat", false)
         ->where('ps.tanggal', $tanggal)
         ->get()
         ->getRowArray() ?? [];

      $totalRow = $this->db->table('tb_guru')->selectCount('id_guru', 'total_guru')->get()->getRowArray() ?? [];
      $totalGuru = (int) ($totalRow['total_guru'] ?? 0);
      $tercatat = (int) ($row['tercatat'] ?? 0);

      return [
         'hadir' => (int) ($row['hadir'] ?? 0),
         'sakit' => (int) ($row['sakit'] ?? 0),
         'izin' => (int) ($row['izin'] ?? 0),
         'alfa' => max(0, $totalGuru - $tercatat),
         'total' => $totalGuru,
      ];
   }

   /**
    * Get attendance trend for the last N calendar days using grouped queries.
    *
    * @return array<string, list<int>>
    */
   public function getAttendanceTrend(int $days = 7): array
   {
      $result = ['hadir' => [], 'sakit' => [], 'izin' => [], 'alfa' => [], 'belum_absen' => []];
      $days = max(0, $days);
      if ($days === 0) {
         return $result;
      }

      $now = Time::now();
      $dates = [];
      for ($offset = $days - 1; $offset >= 0; $offset--) {
         $dates[] = $now->subDays($offset)->toDateString();
      }

      $startDate = $dates[0];
      $endDate = $dates[count($dates) - 1];
      $today = $now->toDateString();
      $nowTime = $now->toTimeString();

      $holidayRows = $this->db->table('tb_hari_libur')
         ->select('tanggal')
         ->where('tanggal >=', $startDate)
         ->where('tanggal <=', $endDate)
         ->get()
         ->getResultArray();
      $holidayDates = array_fill_keys(array_column($holidayRows, 'tanggal'), true);

      $attendanceRows = $this->db->table('tb_presensi_guru ps')
         ->select('ps.tanggal')
         ->select("SUM(CASE WHEN ps.id_kehadiran = 1 THEN 1 ELSE 0 END) AS hadir", false)
         ->select("SUM(CASE WHEN ps.id_kehadiran = 2 THEN 1 ELSE 0 END) AS sakit", false)
         ->select("SUM(CASE WHEN ps.id_kehadiran = 3 THEN 1 ELSE 0 END) AS izin", false)
         ->select("COUNT(DISTINCT CASE WHEN ps.id_kehadiran IN (1, 2, 3) THEN ps.id_guru END) AS tercatat", false)
         ->where('ps.tanggal >=', $startDate)
         ->where('ps.tanggal <=', $endDate)
         ->groupBy('ps.tanggal')
         ->get()
         ->getResultArray();

      $attendanceByDate = [];
      foreach ($attendanceRows as $row) {
         $attendanceByDate[$row['tanggal']] = $row;
      }

      $totalRow = $this->db->table('tb_guru')->selectCount('id_guru', 'total_guru')->get()->getRowArray() ?? [];
      $totalGuru = (int) ($totalRow['total_guru'] ?? 0);
      $schoolConfigurations = new \Config\School();
      $jamPulangStandard = $schoolConfigurations::$generalSettings->jam_pulang_standard ?? '14:00:00';

      foreach ($dates as $date) {
         if (isset($holidayDates[$date])) {
            $result['hadir'][] = 0;
            $result['sakit'][] = 0;
            $result['izin'][] = 0;
            $result['alfa'][] = 0;
            $result['belum_absen'][] = 0;
            continue;
         }

         $row = $attendanceByDate[$date] ?? [];
         $hadir = (int) ($row['hadir'] ?? 0);
         $sakit = (int) ($row['sakit'] ?? 0);
         $izin = (int) ($row['izin'] ?? 0);
         $tercatat = (int) ($row['tercatat'] ?? 0);
         $notPresent = max(0, $totalGuru - $tercatat);
         $isAfterSchool = ($date < $today) || ($date === $today && $nowTime > $jamPulangStandard);

         $result['hadir'][] = $hadir;
         $result['sakit'][] = $sakit;
         $result['izin'][] = $izin;
         $result['alfa'][] = $isAfterSchool ? $notPresent : 0;
         $result['belum_absen'][] = $isAfterSchool ? 0 : $notPresent;
      }

      return $result;
   }

   public function updatePresensi(
      $idPresensi,
      $idGuru,
      $tanggal,
      $idKehadiran,
      $jamMasuk,
      $jamKeluar,
      $keterangan
   ) {
      $presensi = $this->getPresensiByIdGuruTanggal($idGuru, $tanggal);

      $data = [
         'id_guru' => $idGuru,
         'tanggal' => $tanggal,
         'id_kehadiran' => $idKehadiran,
         'keterangan' => $keterangan ?? $presensi['keterangan'] ?? ''
      ];

      if ($idPresensi != null) {
         $data[$this->primaryKey] = $idPresensi;
      }

      if ($jamMasuk != null) {
         $data['jam_masuk'] = $jamMasuk;
      }

      if ($jamKeluar != null) {
         $data['jam_keluar'] = $jamKeluar;
      }

      return $this->save($data);
   }
}
