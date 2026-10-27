<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPerizinanProcessorMetadata extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('processed_role', 'tb_perizinan')) {
            $this->forge->addColumn('tb_perizinan', [
                'processed_role' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'null' => true,
                    'after' => 'id_petugas',
                ],
            ]);
        }

        if (! $this->db->fieldExists('processed_at', 'tb_perizinan')) {
            $this->forge->addColumn('tb_perizinan', [
                'processed_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'processed_role',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('processed_at', 'tb_perizinan')) {
            $this->forge->dropColumn('tb_perizinan', 'processed_at');
        }

        if ($this->db->fieldExists('processed_role', 'tb_perizinan')) {
            $this->forge->dropColumn('tb_perizinan', 'processed_role');
        }
    }
}
