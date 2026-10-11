<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;

class Jadwal extends BaseController
{
    public function index()
    {
        helper('user');
        $user = auth()->user();
        if (!$user || empty($user->id_guru)) {
            return redirect()->to('/teacher/dashboard')->with('error', 'Akun guru belum terhubung ke data guru.');
        }

        $db = db_connect();
        $guruId = (int) $user->id_guru;
        $today = (int) date('N');

        $queryBase = function () use ($db, $guruId) {
            return $db->table('tb_jadwal_pelajaran j')
                ->select('j.*, gm.nama_mapel, gm.kode_mapel, jp.nama_jam, jp.jam_mulai, jp.jam_selesai, jp.urutan, k.tingkat, k.index_kelas, jr.jurusan')
                ->join('tb_mapel gm', 'gm.id_mapel = j.id_mapel', 'left')
                ->join('tb_jam_pelajaran jp', 'jp.id_jam = j.id_jam', 'left')
                ->join('tb_kelas k', 'k.id_kelas = j.id_kelas', 'left')
                ->join('tb_jurusan jr', 'jr.id = k.id_jurusan', 'left')
                ->where('j.id_guru', $guruId)
                ->where('j.aktif', 1);
        };

        $todayRows = $queryBase()->where('j.hari', $today)->orderBy('jp.urutan', 'ASC')->get()->getResultArray();
        $allRows = $queryBase()->orderBy('j.hari', 'ASC')->orderBy('jp.urutan', 'ASC')->get()->getResultArray();

        return view('teacher/jadwal', [
            'title' => 'Jadwal Saya',
            'ctx' => 'teacher-jadwal',
            'todayRows' => $todayRows,
            'allRows' => $allRows,
            'today' => $today,
        ]);
    }
}
