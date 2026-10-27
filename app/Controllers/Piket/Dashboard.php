<?php

namespace App\Controllers\Piket;

use App\Controllers\BaseController;
use App\Models\PerizinanModel;

class Dashboard extends BaseController
{
    private PerizinanModel $perizinanModel;

    public function __construct()
    {
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

    public function index()
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $status = (string) ($this->request->getGet('status') ?: 'Pending');
        $allowedStatuses = ['Pending', 'Disetujui', 'Ditolak'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'Pending';
        }

        $rows = $this->perizinanModel->db->table('tb_perizinan p')
            ->select('p.*, s.nama_siswa, s.nis, k.tingkat, k.index_kelas, j.jurusan')
            ->join('tb_siswa s', 's.id_siswa = p.id_siswa')
            ->join('tb_kelas k', 'k.id_kelas = s.id_kelas', 'left')
            ->join('tb_jurusan j', 'j.id = k.id_jurusan', 'left')
            ->where('p.status', $status)
            ->orderBy('p.created_at', 'DESC')
            ->get()->getResultArray();

        return view('piket/dashboard', [
            'title' => 'Dashboard Piket',
            'ctx' => 'piket-dashboard',
            'status' => $status,
            'rows' => $rows,
        ]);
    }

    public function updatePermission()
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $id = (int) $this->request->getPost('id_perizinan');
        $status = (string) $this->request->getPost('status');
        if ($id <= 0 || !in_array($status, ['Disetujui', 'Ditolak'], true)) {
            return redirect()->back()->with('error', 'Data keputusan tidak valid.');
        }

        // Piket can process student requests only, not teacher leave requests.
        $request = $this->perizinanModel->db->table('tb_perizinan')
            ->select('id_perizinan')
            ->where('id_perizinan', $id)
            ->where('id_siswa IS NOT NULL', null, false)
            ->get()->getRowArray();

        if (!$request) {
            return redirect()->back()->with('error', 'Pengajuan siswa tidak ditemukan.');
        }

        $result = $this->perizinanModel->konfirmasiPerizinan($id, $status, (int) auth()->id());
        return redirect()->to('piket/dashboard')->with(
            $result['status'] === 'success' ? 'success' : 'error',
            $result['message']
        );
    }
}
