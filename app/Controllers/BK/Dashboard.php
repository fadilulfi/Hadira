<?php

namespace App\Controllers\BK;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    protected function guard()
    {
        $user = auth()->user();
        if (!$user || (!$user->inGroup('bk') && !$user->inGroup('superadmin'))) {
            return redirect()->to('/');
        }
        return null;
    }

    protected function classes(): array
    {
        return $this->db->table('tb_kelas k')
            ->select('k.id_kelas, k.tingkat, k.index_kelas, j.jurusan')
            ->join('tb_jurusan j', 'j.id = k.id_jurusan', 'left')
            ->orderBy('k.tingkat')->orderBy('j.jurusan')->orderBy('k.index_kelas')
            ->get()->getResultArray();
    }

    protected function aggregateForDate(string $date, ?int $kelasId = null): array
    {
        $b = $this->db->table('tb_absensi_pelajaran ap')
            ->select("COUNT(ap.id_absensi) total,\n                SUM(CASE WHEN ap.id_kehadiran = 1 THEN 1 ELSE 0 END) hadir,\n                SUM(CASE WHEN ap.id_kehadiran = 2 THEN 1 ELSE 0 END) sakit,\n                SUM(CASE WHEN ap.id_kehadiran = 3 THEN 1 ELSE 0 END) izin,\n                SUM(CASE WHEN ap.id_kehadiran = 4 THEN 1 ELSE 0 END) alpa", false)
            ->where('ap.tanggal', $date);
        if ($kelasId) {
            $b->join('tb_jadwal_pelajaran jp', 'jp.id_jadwal = ap.id_jadwal')
              ->where('jp.id_kelas', $kelasId);
        }
        $row = $b->get()->getRowArray() ?? [];
        return [
            'total' => (int)($row['total'] ?? 0),
            'hadir' => (int)($row['hadir'] ?? 0),
            'sakit' => (int)($row['sakit'] ?? 0),
            'izin' => (int)($row['izin'] ?? 0),
            'alpa' => (int)($row['alpa'] ?? 0),
        ];
    }

    public function index()
    {
        if ($r = $this->guard()) return $r;
        $date = $this->request->getGet('tanggal') ?: date('Y-m-d');
        $stats = $this->aggregateForDate($date);
        $pending = $this->db->table('tb_perizinan')->where('status', 'Pending')->countAllResults();

        $recent = $this->db->table('tb_absensi_pelajaran ap')
            ->select('ap.tanggal, ap.waktu_absen, s.nama_siswa, k.tingkat, k.index_kelas, jr.jurusan, mp.nama_mapel')
            ->join('tb_siswa s', 's.id_siswa = ap.id_siswa')
            ->join('tb_jadwal_pelajaran jp', 'jp.id_jadwal = ap.id_jadwal')
            ->join('tb_kelas k', 'k.id_kelas = jp.id_kelas')
            ->join('tb_jurusan jr', 'jr.id = k.id_jurusan', 'left')
            ->join('tb_mapel mp', 'mp.id_mapel = jp.id_mapel', 'left')
            ->where('ap.tanggal', $date)
            ->orderBy('ap.waktu_absen', 'DESC')->limit(10)->get()->getResultArray();

        return view('bk/dashboard', [
            'title' => 'Dashboard BK', 'ctx' => 'bk-dashboard', 'date' => $date,
            'stats' => $stats, 'pending' => $pending, 'recent' => $recent,
        ]);
    }

    public function monitoring()
    {
        if ($r = $this->guard()) return $r;
        $date = $this->request->getGet('tanggal') ?: date('Y-m-d');
        $kelasId = (int)($this->request->getGet('kelas') ?: 0);

        $q = $this->db->table('tb_siswa s')
            ->select("s.id_siswa, s.nis, s.nama_siswa, k.tingkat, k.index_kelas, jr.jurusan,\n                COALESCE(SUM(CASE WHEN ap.id_kehadiran = 1 THEN 1 ELSE 0 END),0) hadir,\n                COALESCE(SUM(CASE WHEN ap.id_kehadiran = 2 THEN 1 ELSE 0 END),0) sakit,\n                COALESCE(SUM(CASE WHEN ap.id_kehadiran = 3 THEN 1 ELSE 0 END),0) izin,\n                COALESCE(SUM(CASE WHEN ap.id_kehadiran = 4 THEN 1 ELSE 0 END),0) alpa,\n                COUNT(ap.id_absensi) total_jam", false)
            ->join('tb_kelas k', 'k.id_kelas = s.id_kelas')
            ->join('tb_jurusan jr', 'jr.id = k.id_jurusan', 'left')
            ->join('tb_absensi_pelajaran ap', 'ap.id_siswa = s.id_siswa AND ap.tanggal = ' . $this->db->escape($date), 'left')
            ->groupBy('s.id_siswa, s.nis, s.nama_siswa, k.tingkat, k.index_kelas, jr.jurusan')
            ->orderBy('k.tingkat')->orderBy('jr.jurusan')->orderBy('k.index_kelas')->orderBy('s.nama_siswa');
        if ($kelasId) $q->where('s.id_kelas', $kelasId);

        return view('bk/monitoring', [
            'title' => 'Monitoring Kehadiran', 'ctx' => 'bk-monitoring',
            'date' => $date, 'kelasId' => $kelasId, 'classes' => $this->classes(),
            'rows' => $q->get()->getResultArray(),
        ]);
    }

    public function perizinan()
    {
        if ($r = $this->guard()) return $r;
        $status = $this->request->getGet('status') ?: 'Pending';
        $type = $this->request->getGet('tipe') ?: '';
        $date = $this->request->getGet('tanggal') ?: '';

        $q = $this->db->table('tb_perizinan p')
            ->select('p.*, s.nis, s.nama_siswa, k.tingkat, k.index_kelas, jr.jurusan, g.nama_guru, u.username AS petugas')
            ->join('tb_siswa s', 's.id_siswa = p.id_siswa', 'left')
            ->join('tb_kelas k', 'k.id_kelas = s.id_kelas', 'left')
            ->join('tb_jurusan jr', 'jr.id = k.id_jurusan', 'left')
            ->join('tb_guru g', 'g.id_guru = p.id_guru', 'left')
            ->join('users u', 'u.id = p.id_petugas', 'left')
            ->orderBy('p.created_at', 'DESC');
        if ($status !== '') $q->where('p.status', $status);
        if ($type !== '') $q->where('p.tipe_izin', $type);
        if ($date !== '') $q->where('p.tanggal_mulai <=', $date)->where('p.tanggal_selesai >=', $date);

        return view('bk/perizinan', [
            'title' => 'Data Izin & Sakit', 'ctx' => 'bk-perizinan',
            'status' => $status, 'type' => $type, 'date' => $date,
            'rows' => $q->get()->getResultArray(),
        ]);
    }

    public function updatePermission()
    {
        if ($r = $this->guard()) return $r;
        $id = (int)$this->request->getPost('id_perizinan');
        $status = $this->request->getPost('status');
        if (!$id || !in_array($status, ['Disetujui', 'Ditolak'], true)) {
            return redirect()->back()->with('error', 'Permintaan tidak valid.');
        }
        $this->db->table('tb_perizinan')->where('id_perizinan', $id)->update([
            'status' => $status,
            'id_petugas' => auth()->id(),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->back()->with('success', 'Status perizinan berhasil diperbarui.');
    }

    public function laporan()
    {
        if ($r = $this->guard()) return $r;
        $start = $this->request->getGet('mulai') ?: date('Y-m-01');
        $end = $this->request->getGet('sampai') ?: date('Y-m-d');
        $kelasId = (int)($this->request->getGet('kelas') ?: 0);

        $q = $this->db->table('tb_siswa s')
            ->select("s.nis, s.nama_siswa, k.tingkat, k.index_kelas, jr.jurusan,\n                SUM(CASE WHEN ap.id_kehadiran = 1 THEN 1 ELSE 0 END) hadir,\n                SUM(CASE WHEN ap.id_kehadiran = 2 THEN 1 ELSE 0 END) sakit,\n                SUM(CASE WHEN ap.id_kehadiran = 3 THEN 1 ELSE 0 END) izin,\n                SUM(CASE WHEN ap.id_kehadiran = 4 THEN 1 ELSE 0 END) alpa,\n                COUNT(ap.id_absensi) total_jam", false)
            ->join('tb_kelas k', 'k.id_kelas = s.id_kelas')
            ->join('tb_jurusan jr', 'jr.id = k.id_jurusan', 'left')
            ->join('tb_absensi_pelajaran ap', 'ap.id_siswa = s.id_siswa AND ap.tanggal >= ' . $this->db->escape($start) . ' AND ap.tanggal <= ' . $this->db->escape($end), 'left')
            ->groupBy('s.id_siswa, s.nis, s.nama_siswa, k.tingkat, k.index_kelas, jr.jurusan')
            ->orderBy('k.tingkat')->orderBy('jr.jurusan')->orderBy('k.index_kelas')->orderBy('s.nama_siswa');
        if ($kelasId) $q->where('s.id_kelas', $kelasId);

        return view('bk/laporan', [
            'title' => 'Rekap Kehadiran BK', 'ctx' => 'bk-laporan',
            'start' => $start, 'end' => $end, 'kelasId' => $kelasId,
            'classes' => $this->classes(), 'rows' => $q->get()->getResultArray(),
        ]);
    }
}
