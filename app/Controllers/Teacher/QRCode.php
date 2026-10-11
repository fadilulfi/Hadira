<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Models\KelasModel;
use App\Models\SiswaModel;
use App\Controllers\Admin\QRGenerator;

class QRCode extends BaseController
{
    protected KelasModel $kelasModel;
    protected SiswaModel $siswaModel;

    public function __construct()
    {
        $this->kelasModel = new KelasModel();
        $this->siswaModel = new SiswaModel();
    }

    /**
     * Classes available for QR management.
     *
     * Guru:
     * - classes that are assigned to the teacher through an active schedule.
     *
     * Super Admin:
     * - every active class.
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
     * Resolve selected class while keeping the old URL contract working.
     * ?id_kelas=... selects a specific allowed class.
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

    private function guardAccess()
    {
        $user = user();

        if (!$user || (!$user->inGroup('guru') && !$user->inGroup('superadmin'))) {
            return redirect()->to('admin')->with('error', 'Anda bukan Guru.');
        }

        return null;
    }

    public function index()
    {
        if ($redirect = $this->guardAccess()) {
            return $redirect;
        }

        $allowed = $this->getAllowedClasses();

        if ($allowed === []) {
            return redirect()->to('teacher/dashboard')
                ->with('error', 'Belum ada kelas yang terhubung dengan jadwal mengajar Anda.');
        }

        $kelas = $this->resolveClass();
        $siswa = $this->siswaModel->getSiswaByKelas((int) $kelas['id_kelas']);

        foreach ($allowed as &$row) {
            $row['kelas'] = trim(
                ($row['tingkat'] ?? '') . ' ' .
                ($row['jurusan'] ?? '') . ' ' .
                ($row['index_kelas'] ?? '')
            );
        }
        unset($row);

        return view('teacher/qr_code', [
            'title' => 'QR Code Siswa',
            'ctx' => 'teacher-qr',
            'kelas' => $kelas,
            'availableKelas' => $allowed,
            'siswa' => $siswa,
        ]);
    }

    public function download()
    {
        if ($redirect = $this->guardAccess()) {
            return $redirect;
        }

        $kelas = $this->resolveClass();

        if (empty($kelas)) {
            return redirect()->back()
                ->with('error', 'Tidak ada kelas yang dapat dikelola.');
        }

        $qrGenerator = new QRGenerator();
        $qrGenerator->initController(
            $this->request,
            $this->response,
            service('logger')
        );

        $this->request->setGlobal('get', [
            'id_kelas' => (int) $kelas['id_kelas'],
        ]);

        return $qrGenerator->downloadAllQrSiswa();
    }

    public function print()
    {
        if ($redirect = $this->guardAccess()) {
            return $redirect;
        }

        $kelas = $this->resolveClass();

        if (empty($kelas)) {
            return redirect()->to('teacher/dashboard')
                ->with('error', 'Tidak ada kelas yang dapat dikelola.');
        }

        $qrGenerator = new QRGenerator();
        $qrGenerator->initController(
            $this->request,
            $this->response,
            service('logger')
        );

        return $qrGenerator->printQrSiswa((int) $kelas['id_kelas']);
    }
}
