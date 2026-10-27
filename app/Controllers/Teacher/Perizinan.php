<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Models\KelasModel;
use App\Models\PerizinanModel;

class Perizinan extends BaseController
{
    protected PerizinanModel $perizinanModel;
    protected KelasModel $kelasModel;

    public function __construct()
    {
        $this->perizinanModel = new PerizinanModel();
        $this->kelasModel = new KelasModel();
        helper(['user_helper']);
    }

    /**
     * Wali Kelas is an assignment on a Guru account. A request is never
     * authorised from its submitted id alone: its student's class is checked.
     */
    private function assignedClass(): ?array
    {
        $user = user();

        if (!$user || !$user->inGroup('guru') || empty($user->id_guru)) {
            return null;
        }

        return $this->kelasModel->getKelasByWali((int) $user->id_guru) ?: null;
    }

    public function index()
    {
        $kelas = $this->assignedClass();

        if (!$kelas) {
            return view('teacher/perizinan/index', [
                'title' => 'Perizinan Kelas',
                'perizinan' => [],
                'ctx' => 'teacher-perizinan',
                'no_class' => true,
            ]);
        }

        $perizinan = $this->perizinanModel->db->table('tb_perizinan p')
            ->select('p.*, s.nama_siswa, s.nis, k.tingkat, jr.jurusan, k.index_kelas')
            ->join('tb_siswa s', 's.id_siswa = p.id_siswa')
            ->join('tb_kelas k', 'k.id_kelas = s.id_kelas')
            ->join('tb_jurusan jr', 'jr.id = k.id_jurusan')
            ->where('s.id_kelas', $kelas['id_kelas'])
            ->orderBy('p.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return view('teacher/perizinan/index', [
            'title' => 'Perizinan Kelas ' . $kelas['kelas'],
            'perizinan' => $perizinan,
            'ctx' => 'teacher-perizinan',
        ]);
    }

    public function konfirmasi()
    {
        $kelas = $this->assignedClass();
        if (!$kelas) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Akses perizinan hanya tersedia untuk Wali Kelas yang ditugaskan.',
            ]);
        }

        $id = (int) $this->request->getPost('id_perizinan');
        $status = (string) $this->request->getPost('status');

        if (!$id || !in_array($status, ['Disetujui', 'Ditolak'], true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Permintaan perizinan tidak valid.',
            ]);
        }

        $request = $this->perizinanModel->db->table('tb_perizinan p')
            ->select('p.id_perizinan')
            ->join('tb_siswa s', 's.id_siswa = p.id_siswa')
            ->where('p.id_perizinan', $id)
            ->where('p.status', 'Pending')
            ->where('s.id_kelas', $kelas['id_kelas'])
            ->get()
            ->getRowArray();

        if (!$request) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Pengajuan tidak ditemukan, sudah diproses, atau bukan milik kelas Anda.',
            ]);
        }

        return $this->response->setJSON(
            $this->perizinanModel->konfirmasiPerizinan($id, $status, auth()->id(), 'wali_kelas')
        );
    }
}
