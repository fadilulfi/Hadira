<?php

namespace App\\Database\\Migrations;

use CodeIgniter\\Database\\Migration;
use RuntimeException;

class EnforceSingleWaliKelas extends Migration
{
    public function up()
    {
        $duplicates = $this->db->table('tb_kelas')
            ->select('id_wali_kelas')
            ->selectCount('id_kelas', 'total')
            ->where('id_wali_kelas IS NOT NULL', null, false)
            ->groupBy('id_wali_kelas')
            ->having('total >', 1)
            ->get()
            ->getResultArray();

        if ($duplicates !== []) {
            throw new RuntimeException('Penugasan Wali Kelas ganda ditemukan. Rapikan data kelas terlebih dahulu sebelum menjalankan migrasi ini.');
        }

        $this->db->query('ALTER TABLE tb_kelas ADD CONSTRAINT uq_tb_kelas_wali_kelas UNIQUE (id_wali_kelas)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE tb_kelas DROP INDEX uq_tb_kelas_wali_kelas');
    }
}
