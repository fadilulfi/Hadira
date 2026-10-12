<?php

namespace App\Models;

use App\Models\PresensiInterface;
use CodeIgniter\I18n\Time;
use CodeIgniter\Model;
use App\Libraries\enums\Kehadiran;

class PresensiSiswaModel extends Model implements PresensiInterface
{
   protected $primaryKey = 'id_presensi';

   protected $allowedFields = [
      'id_siswa',
      'id_kelas',
      'tanggal',
      'jam_masuk',
      'jam_keluar',
      'id_kehadiran',
      'menit_keterlambatan',
      'keterangan'
   ];

   protected $table = 'tb_presensi_siswa';

   public function cekAbsen(string|int $id, string|Time $date)
   {
      $result = $this->where(['id_siswa' => $id, 'tanggal' => $date])->first();

      if (empty($result))
         return false;

      return $result[$this->primaryKey];
   }

   public function absenMasuk(string $id, $date, $time, $idKelas = '', $menitKeterlambatan = 0)
   {
      $this->save([
         'id_siswa' => $id,
         'id_kelas' => $idKelas,
         'tanggal' => $date,
         'jam_masuk' => $time,
         'id_kehadiran' => Kehadiran::Hadir->value,
         'menit_keterlambatan' => $menitKeterlambatan,
         'keterangan' => $menitKeterlambatan > 0 ? "Terlambat $menitKeterlambatan menit" : ''
      ]);

      // Jika terlambat, tambahkan poin pelanggaran ke tabel siswa
      if ($menitKeterlambatan > 0) {
         $db = \Config\Database::connect();
         $db->table('tb_siswa')
            ->where('id_siswa', $id)
            ->set('poin_pelanggaran', "poin_pelanggaran + $menitKeterlambatan", false)
            ->update();
      }
   }

   public function absenKeluar(string $id, $time)
   {
      $this->update($id, [
         'jam_keluar' => $time,
         'keterangan' => ''
      ]);
   }

   public function getPresensiByIdSiswaTanggal($idSiswa, $date)
   {
      return $this->where(['id_siswa' => $idSiswa, 'tanggal' => $date])->first();
   }

   public function getPresensiById(string $idPresensi)
   {
      return $this->where([$this->primaryKey => $idPresensi])->first();
   }

   public function getPresensiByKelasTanggal($idKelas, $tanggal): array
   {
      return $this->db->table('tb_siswa')
         ->select('*')
         ->join(
            "(SELECT id_presensi, id_siswa AS id_siswa_presensi, tanggal, jam_masuk, jam_keluar, id_kehadiran, keterangan FROM tb_presensi_siswa)tb_presensi_siswa",
            "tb_siswa.id_siswa = tb_presensi_siswa.id_siswa_presensi AND tb_presensi_siswa.tanggal = '$tanggal'",
            'left'
         )
         ->join(
            'tb_kehadiran',
            'tb_presensi_siswa.id_kehadiran = tb_kehadiran.id_kehadiran',
            'left'
         )
         ->where("tb_siswa.id_kelas", $idKelas)
         ->orderBy("nama_siswa")
         ->get()
         ->getResultArray();
   }

   public function getPresensiByKehadiran(string $idKehadiran, $tanggal, $idKelas = null)
   {
      $this->join(
         'tb_siswa',
         "tb_presensi_siswa.id_siswa = tb_siswa.id_siswa AND tb_presensi_siswa.tanggal = '$tanggal'",
         'right'
      );

      if ($idKelas) {
         $this->where('tb_siswa.id_kelas', $idKelas);
      }

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
               // Tambahkan status virtual untuk membedakan di view jika perlu
               $value['is_alfa_final'] = $isAfterSchool;
               array_push($filteredResult, $value);
            }
         }

         return $filteredResult;
      } else {
         $this->where(['tb_presensi_siswa.id_kehadiran' => $idKehadiran]);
         return $this->findAll();
      }
   }

   /**
    * Return attendance counts without loading matching attendance rows into PHP.
    * Anyone without a valid (Hadir/Sakit/Izin) record counts as not present.
    */
   public function getAttendanceSummaryForDate(string $tanggal, $idKelas = null): array
   {
      $query = $this->db->table('tb_presensi_siswa ps')
         ->select("SUM(CASE WHEN ps.id_kehadiran = 1 THEN 1 ELSE 0 END) AS hadir", false)
         ->select("SUM(CASE WHEN ps.id_kehadiran = 2 THEN 1 ELSE 0 END) AS sakit", false)
         ->select("SUM(CASE WHEN ps.id_kehadiran = 3 THEN 1 ELSE 0 END) AS izin", false)
         ->select("COUNT(DISTINCT CASE WHEN ps.id_kehadiran IN (1, 2, 3) THEN ps.id_siswa END) AS tercatat", false)
         ->where('ps.tanggal', $tanggal);

      if (!empty($idKelas)) {
         $query->join('tb_siswa s', 's.id_siswa = ps.id_siswa')
            ->where('s.id_kelas', (int) $idKelas);
      }

      $row = $query->get()->getRowArray() ?? [];
      $totalQuery = $this->db->table('tb_siswa')->selectCount('id_siswa', 'total_siswa');
      if (!empty($idKelas)) {
         $totalQuery->where('id_kelas', (int) $idKelas);
      }
      $totalRow = $totalQuery->get()->getRowArray() ?? [];
      $totalSiswa = (int) ($totalRow['total_siswa'] ?? 0);
      $tercatat = (int) ($row['tercatat'] ?? 0);

      return [
         'hadir' => (int) ($row['hadir'] ?? 0),
         'sakit' => (int) ($row['sakit'] ?? 0),
         'izin' => (int) ($row['izin'] ?? 0),
         'alfa' => max(0, $totalSiswa - $tercatat),
         'total' => $totalSiswa,
      ];
   }

   /**
    * Get attendance trend for the last N calendar days using grouped queries.
    *
    * @return array<string, list<int>>
    */
   public function getAttendanceTrend(int $days = 7, $idKelas = null): array
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

      $attendanceQuery = $this->db->table('tb_presensi_siswa ps')
         ->select('ps.tanggal')
         ->select("SUM(CASE WHEN ps.id_kehadiran = 1 THEN 1 ELSE 0 END) AS hadir", false)
         ->select("SUM(CASE WHEN ps.id_kehadiran = 2 THEN 1 ELSE 0 END) AS sakit", false)
         ->select("SUM(CASE WHEN ps.id_kehadiran = 3 THEN 1 ELSE 0 END) AS izin", false)
         ->select("COUNT(DISTINCT CASE WHEN ps.id_kehadiran IN (1, 2, 3) THEN ps.id_siswa END) AS tercatat", false)
         ->where('ps.tanggal >=', $startDate)
         ->where('ps.tanggal <=', $endDate);

      if (!empty($idKelas)) {
         $attendanceQuery->join('tb_siswa s', 's.id_siswa = ps.id_siswa')
            ->where('s.id_kelas', (int) $idKelas);
      }

      $attendanceRows = $attendanceQuery->groupBy('ps.tanggal')->get()->getResultArray();
      $attendanceByDate = [];
      foreach ($attendanceRows as $row) {
         $attendanceByDate[$row['tanggal']] = $row;
      }

      $totalQuery = $this->db->table('tb_siswa')->selectCount('id_siswa', 'total_siswa');
      if (!empty($idKelas)) {
         $totalQuery->where('id_kelas', (int) $idKelas);
      }
      $totalRow = $totalQuery->get()->getRowArray() ?? [];
      $totalSiswa = (int) ($totalRow['total_siswa'] ?? 0);

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
         $notPresent = max(0, $totalSiswa - $tercatat);
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
      $idSiswa,
      $idKelas,
      $tanggal,
      $idKehadiran,
      $jamMasuk,
      $jamKeluar,
      $keterangan
   ) {
      $presensi = $this->getPresensiByIdSiswaTanggal($idSiswa, $tanggal);

      $data = [
         'id_siswa' => $idSiswa,
         'id_kelas' => $idKelas,
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

   /**
    * Get students who have been absent (Alfa) for N consecutive days
    */
   public function getConsecutiveAbsences(int $consecutiveDays = 3, $idKelas = null): array
   {
      if ($consecutiveDays < 1) {
         return [];
      }

      // Preserve the current definition: use the latest N dates present in attendance.
      $dates = $this->db->table('tb_presensi_siswa')
         ->select('tanggal')
         ->groupBy('tanggal')
         ->orderBy('tanggal', 'DESC')
         ->limit($consecutiveDays)
         ->get()
         ->getResultArray();

      if (count($dates) < $consecutiveDays) {
         return [];
      }

      $dateStrings = array_column($dates, 'tanggal');
      $quotedDates = implode(', ', array_map(
         fn (string $date): string => $this->db->escape($date),
         $dateStrings
      ));

      // Resolve all candidates with one grouped query instead of per-student/per-day queries.
      $builder = $this->db->table('tb_siswa')
         ->select('tb_siswa.*, tb_kelas.tingkat, tb_kelas.index_kelas, tb_jurusan.jurusan')
         ->join('tb_kelas', 'tb_kelas.id_kelas = tb_siswa.id_kelas')
         ->join('tb_jurusan', 'tb_jurusan.id = tb_kelas.id_jurusan')
         ->join(
            'tb_presensi_siswa recent_presence',
            "recent_presence.id_siswa = tb_siswa.id_siswa AND recent_presence.tanggal IN ({$quotedDates})",
            'left',
            false
         )
         ->where(
            'EXISTS (SELECT 1 FROM tb_presensi_siswa any_presence WHERE any_presence.id_siswa = tb_siswa.id_siswa)',
            null,
            false
         );

      if (!empty($idKelas)) {
         $builder->where('tb_siswa.id_kelas', (int) $idKelas);
      }

      $builder->groupBy([
         'tb_siswa.id_siswa',
         'tb_siswa.nis',
         'tb_siswa.nama_siswa',
         'tb_siswa.id_kelas',
         'tb_siswa.jenis_kelamin',
         'tb_siswa.no_hp',
         'tb_siswa.poin_pelanggaran',
         'tb_siswa.unique_code',
         'tb_siswa.rfid_code',
         'tb_kelas.tingkat',
         'tb_kelas.index_kelas',
         'tb_jurusan.jurusan',
      ])->having(
         'SUM(CASE WHEN recent_presence.id_kehadiran IN (1, 2, 3) THEN 1 ELSE 0 END) = 0',
         null,
         false
      );

      $flaggedStudents = $builder->get()->getResultArray();
      foreach ($flaggedStudents as &$student) {
         $student['days_count'] = $consecutiveDays;
      }
      unset($student);

      return $flaggedStudents;
   }
}
