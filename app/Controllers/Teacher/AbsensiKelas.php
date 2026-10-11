<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Models\AbsensiPelajaranModel;
use App\Models\JadwalPelajaranModel;
use App\Models\KehadiranModel;
use App\Models\SiswaModel;
use CodeIgniter\I18n\Time;

class AbsensiKelas extends BaseController
{
    protected JadwalPelajaranModel $jadwalModel;
    protected AbsensiPelajaranModel $absensiModel;
    protected SiswaModel $siswaModel;
    protected KehadiranModel $kehadiranModel;

    public function __construct()
    {
        $this->jadwalModel = new JadwalPelajaranModel();
        $this->absensiModel = new AbsensiPelajaranModel();
        $this->siswaModel = new SiswaModel();
        $this->kehadiranModel = new KehadiranModel();
        helper(['user_helper']);
    }

    private function ensureTeacher()
    {
        if (!is_guru()) {
            return redirect()->to(base_url('admin'))->with('error', 'Anda bukan Guru.');
        }

        if (!user()->id_guru) {
            return redirect()->to(base_url('teacher/dashboard'))->with('error', 'Akun Guru belum terhubung ke data guru.');
        }

        return null;
    }

    public function index()
    {
        if ($redirect = $this->ensureTeacher()) {
            return $redirect;
        }

        $idGuru = (int) user()->id_guru;
        $now = Time::now();
        $tanggal = $now->toDateString();
        $waktu = $now->toTimeString();

        $current = $this->jadwalModel->getCurrentByTeacher($idGuru, $tanggal, $waktu);
        $next = $this->jadwalModel->getNextByTeacher($idGuru, $tanggal, $waktu);
        $todaySchedules = $this->jadwalModel->getTodayByTeacher($idGuru, $tanggal);

        $data = [
            'title' => 'Absensi Kelas',
            'ctx' => 'teacher-absensi-kelas',
            'current' => $current,
            'next' => $next,
            'todaySchedules' => $todaySchedules,
            'tanggal' => $tanggal,
            'waktu' => $waktu,
        ];

        return view('teacher/absensi_kelas', $data);
    }

    public function daftar($idJadwal)
    {
        if ($redirect = $this->ensureTeacher()) {
            return $redirect;
        }

        $idGuru = (int) user()->id_guru;
        $schedule = $this->jadwalModel->getScheduleForTeacher((int) $idJadwal, $idGuru);

        if (!$schedule) {
            return redirect()->to(base_url('teacher/absensi-kelas'))->with('error', 'Jadwal tidak ditemukan atau bukan jadwal Anda.');
        }

        $tanggal = Time::now()->toDateString();
        $students = $this->siswaModel->getSiswaByKelas((int) $schedule['id_kelas']);
        $attendanceMap = $this->absensiModel->getMapForSession((int) $schedule['id_jadwal'], $tanggal);
        $kehadiran = $this->kehadiranModel->getAllKehadiran();

        $izinMap = $this->getApprovedPermissionMap((int) $schedule['id_kelas'], $tanggal);

        return view('teacher/absensi_kelas_form', [
            'title' => 'Absensi ' . $schedule['nama_mapel'],
            'ctx' => 'teacher-absensi-kelas-form',
            'schedule' => $schedule,
            'students' => $students,
            'attendanceMap' => $attendanceMap,
            'kehadiran' => $kehadiran,
            'izinMap' => $izinMap,
            'tanggal' => $tanggal,
        ]);
    }

    public function simpan()
    {
        if ($redirect = $this->ensureTeacher()) {
            return $redirect;
        }

        $idGuru = (int) user()->id_guru;
        $idJadwal = (int) $this->request->getPost('id_jadwal');
        $tanggal = Time::now()->toDateString();
        $waktu = Time::now()->toTimeString();

        $schedule = $this->jadwalModel->getScheduleForTeacher($idJadwal, $idGuru);
        if (!$schedule) {
            return redirect()->to(base_url('teacher/absensi-kelas'))->with('error', 'Jadwal tidak valid.');
        }

        // Untuk MVP, absensi guru hanya bisa disimpan saat slot tersebut berlangsung.
        if ($waktu < $schedule['jam_mulai'] || $waktu > $schedule['jam_selesai']) {
            return redirect()->to(base_url('teacher/absensi-kelas/' . $idJadwal))
                ->with('error', 'Absensi hanya dapat dilakukan pada jam pelajaran yang sedang berlangsung.');
        }

        $students = $this->siswaModel->getSiswaByKelas((int) $schedule['id_kelas']);
        $posted = $this->request->getPost('status') ?? [];
        $notes = $this->request->getPost('keterangan') ?? [];
        $now = Time::now()->toDateTimeString();

        // Hanya terima siswa yang memang berada di kelas pada jadwal tersebut.
        $allowedStudentIds = [];
        foreach ($students as $student) {
            $allowedStudentIds[(int) $student['id_siswa']] = true;
        }

        $rows = [];
        foreach ($allowedStudentIds as $idSiswa => $_) {
            $status = isset($posted[$idSiswa]) ? (int) $posted[$idSiswa] : 0;
            if (!in_array($status, [1, 2, 3, 4], true)) {
                continue;
            }

            $rows[] = [
                'id_jadwal' => $idJadwal,
                'id_siswa' => $idSiswa,
                'tanggal' => $tanggal,
                'waktu_absen' => $waktu,
                'id_kehadiran' => $status,
                'keterangan' => trim((string) ($notes[$idSiswa] ?? '')),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($rows)) {
            return redirect()->to(base_url('teacher/absensi-kelas/' . $idJadwal))
                ->with('error', 'Belum ada status absensi yang dipilih.');
        }

        if (!$this->absensiModel->saveSession($rows)) {
            return redirect()->to(base_url('teacher/absensi-kelas/' . $idJadwal))
                ->with('error', 'Absensi gagal disimpan.');
        }

        return redirect()->to(base_url('teacher/absensi-kelas/' . $idJadwal))
            ->with('success', 'Absensi kelas berhasil disimpan.');
    }

    public function currentJson()
    {
        if ($redirect = $this->ensureTeacher()) {
            return $redirect;
        }

        $now = Time::now();
        $current = $this->jadwalModel->getCurrentByTeacher(
            (int) user()->id_guru,
            $now->toDateString(),
            $now->toTimeString()
        );

        return $this->response->setJSON([
            'success' => true,
            'current' => $current,
            'time' => $now->toTimeString(),
        ]);
    }

    private function getApprovedPermissionMap(int $idKelas, string $tanggal): array
    {
        $rows = $this->absensiModel->db->table('tb_perizinan p')
            ->select('p.id_siswa, p.tipe_izin, p.alasan')
            ->join('tb_siswa s', 's.id_siswa = p.id_siswa')
            ->where('s.id_kelas', $idKelas)
            ->where('p.status', 'Disetujui')
            ->where('p.tanggal_mulai <=', $tanggal)
            ->where('p.tanggal_selesai >=', $tanggal)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id_siswa']] = $row;
        }

        return $map;
    }
}
