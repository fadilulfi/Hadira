<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;

class GuruDashboard extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    protected function guard()
    {
        $user = auth()->user();
        if (!$user || (!$user->inGroup('guru') && !$user->inGroup('superadmin'))) {
            return redirect()->to('/');
        }

        return null;
    }

    protected function dayName(int $day): string
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ][$day] ?? '-';
    }

    public function index()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $user    = auth()->user();
        $guruId  = (int) ($user->id_guru ?? 0);
        $today   = (int) date('N');
        $date    = date('Y-m-d');
        $now     = date('H:i:s');

        $q = $this->db->table('tb_jadwal_pelajaran j')
            ->select('j.*, g.nama_guru, mp.nama_mapel, jp.nama_jam, jp.jam_mulai, jp.jam_selesai, jp.urutan, k.tingkat, k.index_kelas, jr.jurusan')
            ->join('tb_guru g', 'g.id_guru = j.id_guru', 'left')
            ->join('tb_mapel mp', 'mp.id_mapel = j.id_mapel', 'left')
            ->join('tb_jam_pelajaran jp', 'jp.id_jam = j.id_jam', 'left')
            ->join('tb_kelas k', 'k.id_kelas = j.id_kelas', 'left')
            ->join('tb_jurusan jr', 'jr.id = k.id_jurusan', 'left')
            ->where('j.hari', $today)
            ->where('j.aktif', 1);

        // Guru biasa hanya melihat jadwalnya sendiri.
        // Super Admin yang membuka Panel Guru dapat melihat seluruh jadwal.
        if ($guruId > 0) {
            $q->where('j.id_guru', $guruId);
        }

        $todayRows = $q->orderBy('jp.urutan', 'ASC')->get()->getResultArray();

        foreach ($todayRows as &$row) {
            $row['kelas_label'] = trim(($row['tingkat'] ?? '') . ' ' . ($row['jurusan'] ?? '') . ' ' . ($row['index_kelas'] ?? ''));
            $row['total_siswa'] = (int) $this->db->table('tb_siswa')
                ->where('id_kelas', (int) $row['id_kelas'])
                ->countAllResults();
            $row['terisi'] = (int) $this->db->table('tb_absensi_pelajaran')
                ->where('id_jadwal', (int) $row['id_jadwal'])
                ->where('tanggal', $date)
                ->countAllResults();
            $row['is_current'] = (($row['jam_mulai'] ?? '') <= $now && $now <= ($row['jam_selesai'] ?? ''));
        }
        unset($row);

        $scheduleBase = $this->db->table('tb_jadwal_pelajaran')
            ->where('aktif', 1);
        if ($guruId > 0) {
            $scheduleBase->where('id_guru', $guruId);
        }
        $scheduleCount = (int) $scheduleBase->countAllResults();

        $todayCount = count($todayRows);
        $completed  = 0;
        $current    = null;
        foreach ($todayRows as $row) {
            if (($row['terisi'] ?? 0) > 0) {
                $completed++;
            }
            if (!empty($row['is_current'])) {
                $current = $row;
            }
        }

        $next = null;
        foreach ($todayRows as $row) {
            if (($row['jam_mulai'] ?? '') > $now) {
                $next = $row;
                break;
            }
        }

        $label = $guruId > 0
            ? (string)($todayRows[0]['nama_guru'] ?? '')
            : 'Mode Super Admin';

        return view('teacher/guru-dashboard', [
            'title'        => 'Dashboard Guru',
            'ctx'          => 'teacher-dashboard',
            'guruId'       => $guruId,
            'guruLabel'    => $label,
            'today'        => $today,
            'todayName'    => $this->dayName($today),
            'date'         => $date,
            'now'          => $now,
            'todayRows'    => $todayRows,
            'scheduleCount'=> $scheduleCount,
            'todayCount'   => $todayCount,
            'completed'    => $completed,
            'current'      => $current,
            'next'         => $next,
        ]);
    }
}
