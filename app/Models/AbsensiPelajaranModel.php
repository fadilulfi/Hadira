<?php

namespace App\Models;

use CodeIgniter\Model;

class AbsensiPelajaranModel extends Model
{
    protected $table = 'tb_absensi_pelajaran';
    protected $primaryKey = 'id_absensi';
    protected $returnType = 'array';
    protected $allowedFields = [
        'id_jadwal',
        'id_siswa',
        'tanggal',
        'waktu_absen',
        'id_kehadiran',
        'keterangan',
        'created_at',
        'updated_at',
    ];

    public function getForSession(int $idJadwal, string $tanggal): array
    {
        return $this->db->table('tb_absensi_pelajaran ap')
            ->select('ap.*, s.nis, s.nama_siswa, kh.kehadiran')
            ->join('tb_siswa s', 's.id_siswa = ap.id_siswa')
            ->join('tb_kehadiran kh', 'kh.id_kehadiran = ap.id_kehadiran', 'left')
            ->where('ap.id_jadwal', $idJadwal)
            ->where('ap.tanggal', $tanggal)
            ->orderBy('s.nama_siswa', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getMapForSession(int $idJadwal, string $tanggal): array
    {
        $rows = $this->select('id_absensi, id_siswa, id_kehadiran, waktu_absen, keterangan')
            ->where('id_jadwal', $idJadwal)
            ->where('tanggal', $tanggal)
            ->findAll();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id_siswa']] = $row;
        }

        return $map;
    }

    public function saveSession(array $rows): bool
    {
        $db = $this->db;
        $db->transStart();

        foreach ($rows as $row) {
            $existing = $this->where([
                'id_jadwal' => $row['id_jadwal'],
                'id_siswa' => $row['id_siswa'],
                'tanggal' => $row['tanggal'],
            ])->first();

            if ($existing) {
                $this->update($existing['id_absensi'], [
                    'waktu_absen' => $row['waktu_absen'],
                    'id_kehadiran' => $row['id_kehadiran'],
                    'keterangan' => $row['keterangan'],
                    'updated_at' => $row['updated_at'],
                ]);
            } else {
                $this->insert($row);
            }
        }

        $db->transComplete();
        return $db->transStatus();
    }
}
