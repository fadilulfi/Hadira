<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class JadwalPelajaranModel extends Model
{
    protected $table = 'tb_jadwal_pelajaran';
    protected $primaryKey = 'id_jadwal';
    protected $returnType = 'array';
    protected $allowedFields = [
        'hari',
        'id_guru',
        'id_mapel',
        'id_kelas',
        'id_jam',
        'tahun_ajaran',
        'semester',
        'aktif',
        'created_at',
        'updated_at',
    ];

    public function getCurrentByTeacher(int $idGuru, ?string $date = null, ?string $time = null): ?array
    {
        $date ??= Time::now()->toDateString();
        $time ??= Time::now()->toTimeString();
        $hari = (int) date('N', strtotime($date));

        return $this->baseScheduleQuery($idGuru)
            ->where('j.hari', $hari)
            ->where('j.aktif', 1)
            ->where('jp.aktif', 1)
            ->where('jp.jam_mulai <=', $time)
            ->where('jp.jam_selesai >=', $time)
            ->orderBy('jp.jam_mulai', 'ASC')
            ->get()
            ->getRowArray() ?: null;
    }

    public function getNextByTeacher(int $idGuru, ?string $date = null, ?string $time = null): ?array
    {
        $date ??= Time::now()->toDateString();
        $time ??= Time::now()->toTimeString();
        $hari = (int) date('N', strtotime($date));

        return $this->baseScheduleQuery($idGuru)
            ->where('j.hari', $hari)
            ->where('j.aktif', 1)
            ->where('jp.aktif', 1)
            ->where('jp.jam_mulai >', $time)
            ->orderBy('jp.jam_mulai', 'ASC')
            ->get()
            ->getRowArray() ?: null;
    }

    public function getScheduleForTeacher(int $idJadwal, int $idGuru): ?array
    {
        return $this->baseScheduleQuery($idGuru)
            ->where('j.id_jadwal', $idJadwal)
            ->where('j.aktif', 1)
            ->get()
            ->getRowArray() ?: null;
    }

    public function getTodayByTeacher(int $idGuru, ?string $date = null): array
    {
        $date ??= Time::now()->toDateString();
        $hari = (int) date('N', strtotime($date));

        return $this->baseScheduleQuery($idGuru)
            ->where('j.hari', $hari)
            ->where('j.aktif', 1)
            ->where('jp.aktif', 1)
            ->orderBy('jp.jam_mulai', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function baseScheduleQuery(int $idGuru)
    {
        return $this->db->table('tb_jadwal_pelajaran j')
            ->select(
                'j.id_jadwal, j.hari, j.id_guru, j.id_mapel, j.id_kelas, j.id_jam, '
                . 'j.tahun_ajaran, j.semester, '
                . 'm.kode_mapel, m.nama_mapel, '
                . 'jp.nama_jam, jp.jam_mulai, jp.jam_selesai, jp.urutan, '
                . 'k.tingkat, k.index_kelas, jur.jurusan, '
                . 'CONCAT(k.tingkat, " ", jur.jurusan, " ", k.index_kelas) AS nama_kelas, '
                . 'g.nama_guru'
            )
            ->join('tb_mapel m', 'm.id_mapel = j.id_mapel')
            ->join('tb_jam_pelajaran jp', 'jp.id_jam = j.id_jam')
            ->join('tb_kelas k', 'k.id_kelas = j.id_kelas')
            ->join('tb_jurusan jur', 'jur.id = k.id_jurusan')
            ->join('tb_guru g', 'g.id_guru = j.id_guru')
            ->where('j.id_guru', $idGuru);
    }
}
