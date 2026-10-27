<?php

namespace App\Controllers\Piket;

use App\Controllers\BaseController;
use App\Models\PerizinanModel;

class Dashboard extends BaseController
{
    protected $db;
    protected PerizinanModel $perizinanModel;

    public function __construct()
    {
        $this->db = db_connect();
        $this->perizinanModel = new PerizinanModel();
    }

    private function guard()
    {
        $user = auth()->user();
        if (!$user || (!$user->inGroup('piket') && !$user->inGroup('superadmin'))) {
            return redirect()->to('/');
        }

        return null;
    }

    public function perizinan()
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $status = $this->request->getGet('status') ?: 'Pending';
        $type = $this->request->getGet('tipe') ?: '';
        $date = $this->request->getGet('tanggal') ?: '';

        $query = $this->db->table('tb_perizinan p')
            ->select('p.*, s.nis, s.nama_siswa, k.tingkat, k.index_kelas, jr.jurusan, u.username AS petugas')
            ->join('tb_siswa s', 's.id_siswa = p.id_siswa', 'left')
            ->join('tb_kelas k', 'k.id_kelas = s.id_kelas', 'left')
            ->join('tb_jurusan jr', 'jr.id = k.id_jurusan', 'left')
            ->join('users u', 'u.id = p.id_petugas', 'left')
            ->orderBy('p.created_at', 'DESC');

        if ($status !== '') $query->where('p.status', $status);
        if ($type !== '') $query->where('p.tipe_izin', $type);
        if ($date !== '') $query->where('p.tanggal_mulai <=', $date)->where('p.tanggal_selesai >=', $date);

        return view('bk/perizinan', [
            'title' => 'Perizinan Siswa',
            'ctx' => 'piket-perizinan',
            'heading' => 'Perizinan Siswa',
            'subtitle' => 'Petugas Piket menangani pengajuan izin dan sakit seluruh siswa.',
            'permissionAction' => 'piket/perizinan/status',
            'status' => $status,
            'type' => $type,
            'date' => $date,
            'rows' => $query->get()->getResultArray(),
        ]);
    }

    public function updatePermission()
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $id = (int) $this->request->getPost('id_perizinan');
        $status = (string) $this->request->getPost('status');
        if (!$id || !in_array($status, ['Disetujui', 'Ditolak'], true)) {
            return redirect()->back()->with('error', 'Permintaan tidak valid.');
        }

        $request = $this->db->table('tb_perizinan')
            ->select('id_perizinan')
            ->where('id_perizinan', $id)
            ->where('status', 'Pending')
            ->get()
            ->getRowArray();

        if (!$request) {
            return redirect()->back()->with('error', 'Pengajuan tidak ditemukan atau sudah diproses.');
        }

        $result = $this->perizinanModel->konfirmasiPerizinan($id, $status, auth()->id(), 'piket');

        return redirect()->back()->with(
            $result['status'] === 'success' ? 'success' : 'error',
            $result['message']
        );
    }
}
