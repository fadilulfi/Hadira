<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use CodeIgniter\I18n\Time;
use DateTime;
use DateInterval;
use DatePeriod;

use App\Models\KelasModel;
use App\Models\SiswaModel;
use App\Models\PresensiSiswaModel;

class Reports extends BaseController
{
    protected SiswaModel $siswaModel;
    protected KelasModel $kelasModel;
    protected PresensiSiswaModel $presensiSiswaModel;

    public function __construct()
    {
        $this->siswaModel = new SiswaModel();
        $this->kelasModel = new KelasModel();
        $this->presensiSiswaModel = new PresensiSiswaModel();
    }

    /**
     * Get classes that the current user is allowed to report.
     *
     * Guru:
     * - only classes appearing in their active teaching schedule.
     *
     * Super Admin:
     * - all active classes.
     *
     * A Wali Kelas assignment is not required.
     */
    private function getAllowedClasses(): array
    {
        $user = user();
        $db = db_connect();

        if (!$user) {
            return [];
        }

        if ($user->inGroup('superadmin')) {
            return $db->table('tb_kelas k')
                ->select('k.id_kelas, k.tingkat, k.index_kelas, j.jurusan')
                ->join('tb_jurusan j', 'j.id = k.id_jurusan', 'left')
                ->where('k.deleted_at IS NULL', null, false)
                ->orderBy('k.tingkat', 'ASC')
                ->orderBy('j.jurusan', 'ASC')
                ->orderBy('k.index_kelas', 'ASC')
                ->get()
                ->getResultArray();
        }

        $idGuru = (int) ($user->id_guru ?? 0);
        if ($idGuru <= 0) {
            return [];
        }

        return $db->table('tb_jadwal_pelajaran jp')
            ->select('DISTINCT k.id_kelas, k.tingkat, k.index_kelas, j.jurusan', false)
            ->join('tb_kelas k', 'k.id_kelas = jp.id_kelas')
            ->join('tb_jurusan j', 'j.id = k.id_jurusan', 'left')
            ->where('jp.id_guru', $idGuru)
            ->where('jp.aktif', 1)
            ->where('k.deleted_at IS NULL', null, false)
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('j.jurusan', 'ASC')
            ->orderBy('k.index_kelas', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Resolve and validate the selected class.
     * Falls back to the first allowed class for backward compatibility
     * with the existing teacher/reports view.
     */
    private function resolveClass(): ?array
    {
        $allowed = $this->getAllowedClasses();

        if ($allowed === []) {
            return null;
        }

        $requestedId = (int) ($this->request->getVar('id_kelas') ?? 0);

        foreach ($allowed as $row) {
            if ((int) $row['id_kelas'] === $requestedId) {
                $row['kelas'] = trim(
                    ($row['tingkat'] ?? '') . ' ' .
                    ($row['jurusan'] ?? '') . ' ' .
                    ($row['index_kelas'] ?? '')
                );
                return $row;
            }
        }

        $row = $allowed[0];
        $row['kelas'] = trim(
            ($row['tingkat'] ?? '') . ' ' .
            ($row['jurusan'] ?? '') . ' ' .
            ($row['index_kelas'] ?? '')
        );

        return $row;
    }

    public function index()
    {
        $user = user();

        if (!$user || (!$user->inGroup('guru') && !$user->inGroup('superadmin'))) {
            return redirect()->to('admin')->with('error', 'Akses laporan guru tidak diizinkan.');
        }

        $allowed = $this->getAllowedClasses();

        if ($allowed === []) {
            return redirect()->to('teacher/dashboard')
                ->with('error', 'Belum ada kelas yang terhubung dengan jadwal mengajar Anda.');
        }

        $kelas = $this->resolveClass();

        $data = [
            'title' => 'Laporan Presensi Kelas',
            'ctx' => 'teacher-laporan',
            'kelas' => $kelas,
            'availableKelas' => $allowed,
        ];

        return view('teacher/reports', $data);
    }

    public function generate()
    {
        $user = user();

        if (!$user || (!$user->inGroup('guru') && !$user->inGroup('superadmin'))) {
            return redirect()->to('admin')->with('error', 'Akses laporan guru tidak diizinkan.');
        }

        $kelas = $this->resolveClass();

        if (empty($kelas)) {
            return redirect()->to('teacher/dashboard')
                ->with('error', 'Belum ada kelas yang dapat dibuatkan laporan.');
        }

        $idKelas = (int) $kelas['id_kelas'];
        $siswa = $this->siswaModel->getSiswaByKelas($idKelas);
        $type = (string) $this->request->getVar('type');

        if (empty($siswa)) {
            return redirect()->back()->with('error', 'Data siswa kosong!');
        }

        $kelasData = (array) $this->kelasModel->getKelas($idKelas);

        // Existing view posts the month value. Keep that contract unchanged.
        $bulan = (string) ($this->request->getVar('bulan') ?? date('Y-m'));

        try {
            $begin = new Time($bulan, locale: 'id');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Bulan laporan tidak valid.');
        }

        $end = (new DateTime($begin->format('Y-m-t')))->modify('+1 day');
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($begin, $interval, $end);

        $arrayTanggal = [];
        $dataAbsen = [];

        foreach ($period as $value) {
            if (isWorkingDay($value->format('Y-m-d'))) {
                $tanggal = $value->format('Y-m-d');
                $lewat = Time::parse($tanggal)->isAfter(Time::today());
                $absenByTanggal = $this->presensiSiswaModel
                    ->getPresensiByKelasTanggal($idKelas, $tanggal);

                $absenByTanggal['lewat'] = $lewat;
                $dataAbsen[] = $absenByTanggal;
                $arrayTanggal[] = Time::createFromInstance($value, locale: 'id');
            }
        }

        $laki = 0;
        foreach ($siswa as $value) {
            if (($value['jenis_kelamin'] ?? '') !== 'Perempuan') {
                $laki++;
            }
        }

        $data = [
            'tanggal' => $arrayTanggal,
            'bulan' => $begin->toLocalizedString('MMMM'),
            'listAbsen' => $dataAbsen,
            'listSiswa' => $siswa,
            'rekapSiswa' => [
                'laki' => $laki,
                'perempuan' => count($siswa) - $laki,
            ],
            'kelas' => $kelasData,
            'grup' => 'kelas ' . ($kelasData['kelas'] ?? $kelas['kelas']),
        ];

        if ($type === 'doc') {
            $this->response->setHeader('Content-type', 'application/vnd.ms-word');
            $this->response->setHeader(
                'Content-Disposition',
                'attachment;Filename=laporan_absen_' .
                ($kelasData['kelas'] ?? $kelas['kelas']) . '_' .
                $begin->toLocalizedString('MMMM-Y') . '.doc'
            );

            return view('admin/generate-laporan/laporan-siswa', $data);
        }

        return view('admin/generate-laporan/laporan-siswa', $data)
            . view('admin/generate-laporan/topdf');
    }
}
