<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Models\PerizinanModel;
use App\Models\PresensiSiswaModel;
use App\Models\SiswaModel;
use App\Models\KelasModel;

class Perizinan extends BaseController
{
    protected $perizinanModel;
    protected $presensiSiswaModel;
    protected $siswaModel;
    protected $kelasModel;

    public function __construct()
    {
        $this->perizinanModel = new PerizinanModel();
        $this->presensiSiswaModel = new PresensiSiswaModel();
        $this->siswaModel = new SiswaModel();
        $this->kelasModel = new KelasModel();
        helper(['user_helper']);
    }

    private function assignedHomeroom(): ?array
    {
        $user = user();
        if (!$user || empty($user->id_guru)) {
            return null;
        }

        return $this->kelasModel->getKelasByWali($user->id_guru) ?: null;
    }

    public function index()
    {
        $kelas = $this->assignedHomeroom();

        if (empty($kelas)) {
            return view('teacher/perizinan/index', [
                'title' => 'Data Perizinan Siswa',
                'perizinan' => [],
                'ctx' => 'teacher-perizinan',
                'no_class' => true
            ]);
        }

        // Ambil perizinan khusus untuk siswa di kelas ini
        $perizinan = $this->perizinanModel->db->table('tb_perizinan')
            ->select('tb_perizinan.*, tb_siswa.nama_siswa, tb_siswa.nis, tb_kelas.tingkat, tb_jurusan.jurusan, tb_kelas.index_kelas')
            ->join('tb_siswa', 'tb_siswa.id_siswa = tb_perizinan.id_siswa')
            ->join('tb_kelas', 'tb_kelas.id_kelas = tb_siswa.id_kelas')
            ->join('tb_jurusan', 'tb_jurusan.id = tb_kelas.id_jurusan')
            ->where('tb_siswa.id_kelas', $kelas['id_kelas'])
            ->orderBy('tb_perizinan.created_at', 'DESC')
            ->get()->getResultArray();

        $data = [
            'title' => 'Data Perizinan Siswa',
            'perizinan' => $perizinan,
            'ctx' => 'teacher-perizinan'
        ];
        return view('teacher/perizinan/index', $data);
    }

    public function konfirmasi()
    {
        $kelas = $this->assignedHomeroom();
        if (!$kelas) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Anda bukan Wali Kelas yang ditugaskan.']);
        }

        $idPerizinan = (int) request()->getPost('id_perizinan');
        $status = (string) request()->getPost('status');
        if ($idPerizinan <= 0 || !in_array($status, ['Disetujui', 'Ditolak'], true)) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 'error', 'message' => 'Data keputusan tidak valid.']);
        }

        $request = $this->perizinanModel->db->table('tb_perizinan p')
            ->select('p.id_perizinan')
            ->join('tb_siswa s', 's.id_siswa = p.id_siswa')
            ->where('p.id_perizinan', $idPerizinan)
            ->where('s.id_kelas', (int) $kelas['id_kelas'])
            ->get()->getRowArray();

        if (!$request) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Anda hanya dapat memproses izin siswa dari kelas wali Anda.']);
        }

        $result = $this->perizinanModel->konfirmasiPerizinan($idPerizinan, $status, (int) auth()->id());
        return $this->response->setJSON($result);
    }
}
