<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

use App\Models\GuruModel;
use App\Models\JurusanModel;
use App\Models\SiswaModel;
use App\Models\KelasModel;
use App\Models\PetugasModel;
use App\Models\PresensiGuruModel;
use App\Models\PresensiSiswaModel;
use CodeIgniter\I18n\Time;

class Dashboard extends BaseController
{
   protected SiswaModel $siswaModel;
   protected GuruModel $guruModel;

   protected KelasModel $kelasModel;
   protected JurusanModel $jurusanModel;

   protected PresensiSiswaModel $presensiSiswaModel;
   protected PresensiGuruModel $presensiGuruModel;

   protected PetugasModel $petugasModel;

   public function __construct()
   {
      $this->siswaModel = new SiswaModel();
      $this->guruModel = new GuruModel();
      $this->kelasModel = new KelasModel();
      $this->jurusanModel = new JurusanModel();
      $this->presensiSiswaModel = new PresensiSiswaModel();
      $this->presensiGuruModel = new PresensiGuruModel();
      $this->petugasModel = new PetugasModel();
   }

   public function index()
   {
      $now = Time::now();

      $dateRange = [];
      $chartLabelColors = [];
      $holidayModel = new \App\Models\HariLiburModel();
      $dateObjects = [];
      for ($i = 6; $i >= 0; $i--) {
         $dateObjects[] = $now->subDays($i);
      }

      $dateStrings = array_map(
         static fn ($dateTime) => $dateTime->toDateString(),
         $dateObjects
      );
      $holidayRows = $holidayModel->where('tanggal >=', $dateStrings[0])
         ->where('tanggal <=', $dateStrings[count($dateStrings) - 1])
         ->findAll();
      $holidayDates = array_fill_keys(array_column($holidayRows, 'tanggal'), true);

      foreach ($dateObjects as $index => $dateTime) {
         $date = $dateStrings[$index];
         $formattedDate = $index === count($dateObjects) - 1
            ? "Hari ini"
            : "{$dateTime->getDay()} " . substr($dateTime->toFormattedDateString(), 0, 3);

         $dateRange[] = $formattedDate;
         $chartLabelColors[] = isset($holidayDates[$date]) ? '#f44336' : '#333';
      }

      $today = $now->toDateString();
      
      $jamPulangStandard = $this->generalSettings->jam_pulang_standard ?? '14:00:00';
      $isAfterSchool = $now->toTimeString() > $jamPulangStandard;

      // Get attendance trends using new methods
      $grafikKehadiranSiswa = $this->presensiSiswaModel->getAttendanceTrend();
      $grafikKehadiranGuru = $this->presensiGuruModel->getAttendanceTrend();

      // Fetch student counts grouped by class once instead of querying once per class.
      $studentCountRows = db_connect()->table('tb_siswa')
         ->select('id_kelas')
         ->selectCount('id_siswa', 'jumlah_siswa')
         ->groupBy('id_kelas')
         ->get()
         ->getResultArray();
      $studentCountsByClass = [];
      foreach ($studentCountRows as $studentCountRow) {
         $studentCountsByClass[(int) $studentCountRow['id_kelas']] = (int) $studentCountRow['jumlah_siswa'];
      }

      $kelasData = $this->kelasModel->getDataKelas();
      foreach ($kelasData as &$k) {
         $k['jumlah_siswa'] = $studentCountsByClass[(int) $k['id_kelas']] ?? 0;
      }
      unset($k);

      $jumlahKehadiranSiswa = $this->presensiSiswaModel->getAttendanceSummaryForDate($today);
      $jumlahKehadiranGuru = $this->presensiGuruModel->getAttendanceSummaryForDate($today);
      $totalSiswa = $jumlahKehadiranSiswa['total'];
      $totalGuru = $jumlahKehadiranGuru['total'];
      $totalJurusan = (int) db_connect()->table('tb_jurusan')->countAllResults();
      $totalPetugas = $this->petugasModel->countAllResults();

      $data = [
         'title' => 'Dashboard',
         'ctx' => 'admin-dashboard',

         // The dashboard only needs totals, not fully hydrated student/user rows.
         'totalSiswa' => $totalSiswa,
         'totalGuru' => $totalGuru,
         'totalJurusan' => $totalJurusan,
         'totalPetugas' => $totalPetugas,

         'kelas' => $kelasData,

         'dateRange' => $dateRange,
         'chartLabelColors' => $chartLabelColors,
         'dateNow' => $now->toLocalizedString('d MMMM Y'),

         'grafikKehadiranSiswa' => $grafikKehadiranSiswa,
         'grafikKehadiranGuru' => $grafikKehadiranGuru,

         'jumlahKehadiranSiswa' => $jumlahKehadiranSiswa,
         'jumlahKehadiranGuru' => $jumlahKehadiranGuru,
         
         'topLateStudents' => $this->siswaModel->select('tb_siswa.*, tb_kelas.tingkat, tb_kelas.index_kelas, tb_jurusan.jurusan')
            ->join('tb_kelas', 'tb_kelas.id_kelas = tb_siswa.id_kelas')
            ->join('tb_jurusan', 'tb_jurusan.id = tb_kelas.id_jurusan')
            ->where('poin_pelanggaran >', 0)
            ->orderBy('poin_pelanggaran', 'DESC')
            ->limit(5)
            ->get()->getResultArray(),

         'absenteeAlerts' => $this->presensiSiswaModel->getConsecutiveAbsences(3),
      ];

      return view('admin/dashboard', $data);
   }

   public function auditLog()
   {
      $auditLogModel = new \App\Models\AuditLogModel();
      $data = [
         'title' => 'Audit Log - Riwayat Perubahan',
         'ctx' => 'audit-log',
         'logs' => $auditLogModel->getLogs()
      ];
      return view('admin/audit_log', $data);
   }

   public function getLiveStats()
   {
      $now = Time::now();
      $today = $now->toDateString();
      $idKelas = $this->request->getGet('id_kelas');

      $jumlahKehadiranSiswa = $this->presensiSiswaModel->getAttendanceSummaryForDate($today, $idKelas);

      $totalSiswa = $jumlahKehadiranSiswa['total'];
      
      $jamPulangStandard = $this->generalSettings->jam_pulang_standard ?? '14:00:00';
      $isAfterSchool = $now->toTimeString() > $jamPulangStandard;

      return $this->response->setJSON([
         'stats' => $jumlahKehadiranSiswa,
         'totalSiswa' => $totalSiswa,
         'isAfterSchool' => $isAfterSchool,
         'lastUpdate' => $now->toTimeString()
      ]);
   }

   public function filterData()
   {
      $idKelas = $this->request->getPost('id_kelas');
      $now = Time::now();
      $today = $now->toDateString();

      // Statistik Siswa
      $jumlahKehadiranSiswa = $this->presensiSiswaModel->getAttendanceSummaryForDate($today, $idKelas);

      // Grafik Siswa (7 Hari) - using getAttendanceTrend
      $grafikKehadiranSiswa = $this->presensiSiswaModel->getAttendanceTrend(7, $idKelas ?: null);

      // Total siswa is already calculated together with today's attendance aggregate.
      $jumlahSiswa = $jumlahKehadiranSiswa['total'];

      $data = [
         'hadir' => $jumlahKehadiranSiswa['hadir'],
         'sakit' => $jumlahKehadiranSiswa['sakit'],
         'izin' => $jumlahKehadiranSiswa['izin'],
         'alfa' => $jumlahKehadiranSiswa['alfa'],
         'totalSiswa' => $jumlahSiswa,
      ];

      return $this->response->setJSON([
         'result' => 1,
         'htmlContent' => view('admin/_dashboard_siswa_stats', $data),
         'chartData' => $grafikKehadiranSiswa,
         'totalSiswa' => $jumlahSiswa
      ]);
   }
}
