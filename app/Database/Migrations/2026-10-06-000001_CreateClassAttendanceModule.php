<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClassAttendanceModule extends Migration
{
    public function up()
    {
        // 1) Master mata pelajaran
        $this->forge->addField([
            'id_mapel' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'kode_mapel' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'nama_mapel' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'aktif' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id_mapel', true);
        $this->forge->addUniqueKey(['kode_mapel']);
        $this->forge->createTable('tb_mapel', true);

        // 2) Master blok/jam pelajaran.
        // Satu record dapat mewakili gabungan jam, misalnya "3-4".
        $this->forge->addField([
            'id_jam' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nama_jam' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'jam_mulai' => [
                'type' => 'TIME',
            ],
            'jam_selesai' => [
                'type' => 'TIME',
            ],
            'urutan' => [
                'type' => 'TINYINT',
                'constraint' => 3,
            ],
            'aktif' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id_jam', true);
        $this->forge->addUniqueKey(['nama_jam']);
        $this->forge->createTable('tb_jam_pelajaran', true);

        // 3) Jadwal mingguan yang menjadi sumber kebenaran absensi guru.
        $this->forge->addField([
            'id_jadwal' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            // 1=Senin ... 6=Sabtu
            'hari' => [
                'type' => 'TINYINT',
                'constraint' => 1,
            ],
            'id_guru' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'id_mapel' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'id_kelas' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'id_jam' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'tahun_ajaran' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => '2026/2027',
            ],
            'semester' => [
                'type' => 'ENUM',
                'constraint' => ['Ganjil', 'Genap'],
                'default' => 'Ganjil',
            ],
            'aktif' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id_jadwal', true);
        $this->forge->addKey(['hari', 'id_guru', 'aktif']);
        $this->forge->addKey(['id_kelas', 'id_jam']);
        $this->forge->addUniqueKey([
            'hari', 'id_guru', 'id_mapel', 'id_kelas', 'id_jam', 'tahun_ajaran', 'semester'
        ], 'uq_jadwal_mingguan');
        $this->forge->createTable('tb_jadwal_pelajaran', true);

        // 4) Absensi per sesi/mata pelajaran.
        $this->forge->addField([
            'id_absensi' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id_jadwal' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'id_siswa' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'tanggal' => [
                'type' => 'DATE',
            ],
            'waktu_absen' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'id_kehadiran' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'keterangan' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'default' => '',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id_absensi', true);
        $this->forge->addKey(['id_jadwal', 'tanggal']);
        $this->forge->addKey(['id_siswa', 'tanggal']);
        $this->forge->addUniqueKey(['id_jadwal', 'id_siswa', 'tanggal'], 'uq_absensi_sesi_siswa_tanggal');
        $this->forge->createTable('tb_absensi_pelajaran', true);

        // Explicit FK statements are used to match the repository's migration style.
        $this->db->query(
            'ALTER TABLE tb_jadwal_pelajaran '
            . 'ADD CONSTRAINT fk_jadwal_guru FOREIGN KEY (id_guru) REFERENCES tb_guru(id_guru) '
            . 'ON UPDATE CASCADE ON DELETE RESTRICT'
        );
        $this->db->query(
            'ALTER TABLE tb_jadwal_pelajaran '
            . 'ADD CONSTRAINT fk_jadwal_mapel FOREIGN KEY (id_mapel) REFERENCES tb_mapel(id_mapel) '
            . 'ON UPDATE CASCADE ON DELETE RESTRICT'
        );
        $this->db->query(
            'ALTER TABLE tb_jadwal_pelajaran '
            . 'ADD CONSTRAINT fk_jadwal_kelas FOREIGN KEY (id_kelas) REFERENCES tb_kelas(id_kelas) '
            . 'ON UPDATE CASCADE ON DELETE RESTRICT'
        );
        $this->db->query(
            'ALTER TABLE tb_jadwal_pelajaran '
            . 'ADD CONSTRAINT fk_jadwal_jam FOREIGN KEY (id_jam) REFERENCES tb_jam_pelajaran(id_jam) '
            . 'ON UPDATE CASCADE ON DELETE RESTRICT'
        );
        $this->db->query(
            'ALTER TABLE tb_absensi_pelajaran '
            . 'ADD CONSTRAINT fk_absensi_pelajaran_jadwal FOREIGN KEY (id_jadwal) REFERENCES tb_jadwal_pelajaran(id_jadwal) '
            . 'ON UPDATE CASCADE ON DELETE CASCADE'
        );
        $this->db->query(
            'ALTER TABLE tb_absensi_pelajaran '
            . 'ADD CONSTRAINT fk_absensi_pelajaran_siswa FOREIGN KEY (id_siswa) REFERENCES tb_siswa(id_siswa) '
            . 'ON UPDATE CASCADE ON DELETE CASCADE'
        );
        $this->db->query(
            'ALTER TABLE tb_absensi_pelajaran '
            . 'ADD CONSTRAINT fk_absensi_pelajaran_kehadiran FOREIGN KEY (id_kehadiran) REFERENCES tb_kehadiran(id_kehadiran) '
            . 'ON UPDATE CASCADE ON DELETE RESTRICT'
        );
    }

    public function down()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->forge->dropTable('tb_absensi_pelajaran', true);
        $this->forge->dropTable('tb_jadwal_pelajaran', true);
        $this->forge->dropTable('tb_jam_pelajaran', true);
        $this->forge->dropTable('tb_mapel', true);
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}
