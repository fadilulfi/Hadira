<?php

namespace App\Controllers;

use App\Models\AbsensiPelajaranModel;
use App\Models\AuditLogModel;
use App\Models\KehadiranModel;
use App\Models\SiswaModel;
use CodeIgniter\I18n\Time;

class ClassAttendance extends BaseController
{
    protected $db;
    protected AbsensiPelajaranModel $absensiModel;
    protected KehadiranModel $kehadiranModel;
    protected SiswaModel $siswaModel;

    public function __construct()
    {
        helper('user_helper');
        $this->db = db_connect();
        $this->absensiModel = new AbsensiPelajaranModel();
        $this->kehadiranModel = new KehadiranModel();
        $this->siswaModel = new SiswaModel();
    }

    private function denyIfUnauthorized()
    {
        if (!can_correct_class_attendance()) {
            return $this->response->setStatusCode(403)
                ->setBody('Role Anda tidak diizinkan mengoreksi absensi jam pelajaran.');
        }

        return null;
    }

    private function safeDate(?string $value): ?string
    {
        $value = trim((string) $value);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();

        if (
            !$date
            || ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== $value
            || $value > Time::now()->toDateString()
        ) {
            return null;
        }

        return $value;
    }

    private function schedule(int $idJadwal): ?array
    {
        return $this->db->table('tb_jadwal_pelajaran j')
            ->select(
                'j.id_jadwal, j.id_guru, j.id_kelas, j.id_jam, '
                . 'g.nama_guru, mp.nama_mapel, jp.nama_jam, jp.jam_mulai, jp.jam_selesai, jp.urutan, '
                . 'k.tingkat, k.index_kelas, jr.jurusan, '
                . 'CONCAT(k.tingkat, " ", jr.jurusan, " ", k.index_kelas) AS nama_kelas',
                false
            )
            ->join('tb_guru g', 'g.id_guru = j.id_guru')
            ->join('tb_mapel mp', 'mp.id_mapel = j.id_mapel')
            ->join('tb_jam_pelajaran jp', 'jp.id_jam = j.id_jam')
            ->join('tb_kelas k', 'k.id_kelas = j.id_kelas')
            ->join('tb_jurusan jr', 'jr.id = k.id_jurusan', 'left')
            ->where('j.id_jadwal', $idJadwal)
            ->get()
            ->getRowArray() ?: null;
    }

    private function classOptions(): array
    {
        return $this->db->table('tb_kelas k')
            ->select('k.id_kelas, k.tingkat, k.index_kelas, jr.jurusan')
            ->join('tb_jurusan jr', 'jr.id = k.id_jurusan', 'left')
            ->orderBy('k.tingkat')
            ->orderBy('jr.jurusan')
            ->orderBy('k.index_kelas')
            ->get()
            ->getResultArray();
    }

    private function teacherOptions(): array
    {
        return $this->db->table('tb_guru')
            ->select('id_guru, nama_guru')
            ->orderBy('nama_guru')
            ->get()
            ->getResultArray();
    }

    public function index()
    {
        if ($denied = $this->denyIfUnauthorized()) {
            return $denied;
        }

        $today = Time::now()->toDateString();
        $requestedDate = (string) ($this->request->getGet('tanggal') ?: $today);
        $date = $this->safeDate($requestedDate);

        if ($date === null) {
            $date = $today;
            session()->setFlashdata('error', 'Tanggal tidak valid atau belum terjadi. Menampilkan tanggal hari ini.');
        }

        $classId = (int) ($this->request->getGet('kelas') ?: 0);
        $teacherId = (int) ($this->request->getGet('guru') ?: 0);

        // Only display schedule sessions that have at least one saved record
        // for the selected date. Inactive schedules remain available for history.
        $query = $this->db->table('tb_jadwal_pelajaran j')
            ->select(
                'j.id_jadwal, j.id_guru, j.id_kelas, '
                . 'g.nama_guru, mp.nama_mapel, jp.nama_jam, jp.jam_mulai, jp.jam_selesai, '
                . 'k.tingkat, k.index_kelas, jr.jurusan, '
                . 'COUNT(ap.id_absensi) AS terisi',
                false
            )
            ->join(
                'tb_absensi_pelajaran ap',
                'ap.id_jadwal = j.id_jadwal AND ap.tanggal = ' . $this->db->escape($date),
                'inner'
            )
            ->join('tb_guru g', 'g.id_guru = j.id_guru')
            ->join('tb_mapel mp', 'mp.id_mapel = j.id_mapel')
            ->join('tb_jam_pelajaran jp', 'jp.id_jam = j.id_jam')
            ->join('tb_kelas k', 'k.id_kelas = j.id_kelas')
            ->join('tb_jurusan jr', 'jr.id = k.id_jurusan', 'left')
            ->groupBy([
                'j.id_jadwal', 'j.id_guru', 'j.id_kelas',
                'g.nama_guru', 'mp.nama_mapel', 'jp.nama_jam',
                'jp.jam_mulai', 'jp.jam_selesai', 'jp.urutan',
                'k.tingkat', 'k.index_kelas', 'jr.jurusan',
            ])
            ->orderBy('jp.urutan');

        if ($classId > 0) {
            $query->where('j.id_kelas', $classId);
        }
        if ($teacherId > 0) {
            $query->where('j.id_guru', $teacherId);
        }

        $rows = $query->get()->getResultArray();
        $classIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['id_kelas'],
            $rows
        )));

        $studentCounts = [];
        if ($classIds !== []) {
            $counts = $this->db->table('tb_siswa')
                ->select('id_kelas')
                ->selectCount('id_siswa', 'total_siswa')
                ->whereIn('id_kelas', $classIds)
                ->groupBy('id_kelas')
                ->get()
                ->getResultArray();

            foreach ($counts as $count) {
                $studentCounts[(int) $count['id_kelas']] = (int) $count['total_siswa'];
            }
        }

        foreach ($rows as &$row) {
            $row['kelas_label'] = trim(
                ($row['tingkat'] ?? '') . ' '
                . ($row['jurusan'] ?? '') . ' '
                . ($row['index_kelas'] ?? '')
            );
            $row['terisi'] = (int) $row['terisi'];
            $row['total_siswa'] = $studentCounts[(int) $row['id_kelas']] ?? 0;
        }
        unset($row);

        $pageTitle = match (user_role()) {
            'bk' => 'Monitoring Kehadiran',
            'waka' => 'Monitoring Absensi',
            'admin', 'superadmin' => 'Absensi Jam Pelajaran',
            default => 'Koreksi Absensi Jam Pelajaran',
        };

        return view('class_attendance/index', [
            'title' => $pageTitle,
            'ctx' => 'class-attendance',
            'date' => $date,
            'classId' => $classId,
            'teacherId' => $teacherId,
            'classes' => $this->classOptions(),
            'teachers' => $this->teacherOptions(),
            'rows' => $rows,
        ]);
    }

    public function edit($idJadwal)
    {
        if ($denied = $this->denyIfUnauthorized()) {
            return $denied;
        }

        $today = Time::now()->toDateString();
        $date = $this->safeDate((string) ($this->request->getGet('tanggal') ?: $today));
        if ($date === null) {
            return redirect()->to(base_url('class-attendance'))->with('error', 'Tanggal koreksi tidak valid.');
        }

        $schedule = $this->schedule((int) $idJadwal);
        if (!$schedule) {
            return redirect()->to(base_url('class-attendance'))->with('error', 'Jadwal pelajaran tidak ditemukan.');
        }

        $attendanceMap = $this->absensiModel->getMapForSession((int) $idJadwal, $date);
        if ($attendanceMap === []) {
            return redirect()->to(base_url('class-attendance?tanggal=' . rawurlencode($date)))
                ->with('error', 'Belum ada daftar hadir tersimpan untuk sesi dan tanggal tersebut.');
        }

        return view('class_attendance/edit', [
            'title' => 'Koreksi Daftar Hadir',
            'ctx' => 'class-attendance',
            'date' => $date,
            'schedule' => $schedule,
            'students' => $this->siswaModel->getSiswaByKelas((int) $schedule['id_kelas']),
            'attendanceMap' => $attendanceMap,
            'kehadiran' => $this->kehadiranModel->getAllKehadiran(),
        ]);
    }

    public function save()
    {
        if ($denied = $this->denyIfUnauthorized()) {
            return $denied;
        }

        $idJadwal = (int) $this->request->getPost('id_jadwal');
        $date = $this->safeDate((string) $this->request->getPost('tanggal'));
        if (!$idJadwal || $date === null) {
            return redirect()->back()->with('error', 'Sesi atau tanggal koreksi tidak valid.');
        }

        $schedule = $this->schedule($idJadwal);
        if (!$schedule) {
            return redirect()->to(base_url('class-attendance'))->with('error', 'Jadwal pelajaran tidak ditemukan.');
        }

        $existingMap = $this->absensiModel->getMapForSession($idJadwal, $date);
        if ($existingMap === []) {
            return redirect()->to(base_url('class-attendance?tanggal=' . rawurlencode($date)))
                ->with('error', 'Koreksi hanya tersedia untuk daftar hadir yang sudah tersimpan.');
        }

        $students = $this->siswaModel->getSiswaByKelas((int) $schedule['id_kelas']);
        $postedStatus = $this->request->getPost('status') ?? [];
        $postedNotes = $this->request->getPost('keterangan') ?? [];
        $now = Time::now()->toDateTimeString();
        $rows = [];
        $studentNames = [];

        foreach ($students as $student) {
            $studentId = (int) $student['id_siswa'];
            $studentNames[$studentId] = (string) $student['nama_siswa'];
            $rawStatus = isset($postedStatus[$studentId]) ? trim((string) $postedStatus[$studentId]) : '';

            // Blank entries are left untouched, allowing incomplete sessions to be
            // completed incrementally without losing previously saved statuses.
            if ($rawStatus === '') {
                continue;
            }

            $status = filter_var($rawStatus, FILTER_VALIDATE_INT);
            if ($status === false || !in_array((int) $status, [1, 2, 3, 4], true)) {
                return redirect()->back()->with('error', 'Status absensi tidak valid. Periksa kembali pilihan status siswa.');
            }

            $rows[] = [
                'id_jadwal' => $idJadwal,
                'id_siswa' => $studentId,
                'tanggal' => $date,
                'waktu_absen' => $now,
                'id_kehadiran' => (int) $status,
                'keterangan' => trim((string) ($postedNotes[$studentId] ?? '')),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows === []) {
            return redirect()->back()->with('error', 'Tidak ada perubahan status untuk disimpan.');
        }

        if (!$this->absensiModel->saveSession($rows)) {
            return redirect()->back()->with('error', 'Koreksi absensi gagal disimpan. Silakan coba kembali.');
        }

        $audit = new AuditLogModel();
        $role = user_role() ?? 'unknown';
        foreach ($rows as $row) {
            $studentId = (int) $row['id_siswa'];
            $old = $existingMap[$studentId] ?? null;
            $oldStatus = $old ? (int) $old['id_kehadiran'] : null;
            $oldNote = $old ? (string) ($old['keterangan'] ?? '') : null;
            $newStatus = (int) $row['id_kehadiran'];
            $newNote = (string) $row['keterangan'];

            if ($old && $oldStatus === $newStatus && $oldNote === $newNote) {
                continue;
            }

            $audit->log(
                ($old ? 'Koreksi' : 'Lengkapi') . " absensi jam ({$role}): " . ($studentNames[$studentId] ?? "ID {$studentId}"),
                'tb_absensi_pelajaran',
                $old['id_absensi'] ?? null,
                $old ? ['id_kehadiran' => $oldStatus, 'keterangan' => $oldNote] : null,
                [
                    'id_jadwal' => $idJadwal,
                    'id_siswa' => $studentId,
                    'tanggal' => $date,
                    'id_kehadiran' => $newStatus,
                    'keterangan' => $newNote,
                ]
            );
        }

        return redirect()->to(base_url('class-attendance/edit/' . $idJadwal . '?tanggal=' . rawurlencode($date)))
            ->with('success', 'Koreksi daftar hadir berhasil disimpan.');
    }
}
