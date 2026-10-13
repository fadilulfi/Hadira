<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class SchoolDirectory extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    /**
     * Directories are reference-only. Master-data changes remain an Admin task.
     */
    private function authorize(array $allowedGroups)
    {
        $user = auth()->user();

        if ($user === null) {
            return redirect()->to(base_url('login'));
        }

        foreach ($allowedGroups as $group) {
            if ($user->inGroup($group)) {
                return null;
            }
        }

        return $this->response->setStatusCode(403)
            ->setBody('Akses ditolak. Halaman data referensi ini hanya dapat dilihat oleh role yang berwenang.');
    }

    public function wakaStudents()
    {
        if ($denied = $this->authorize(['superadmin', 'waka'])) {
            return $denied;
        }

        return $this->studentDirectory('waka/data-siswa');
    }

    public function bkStudents()
    {
        if ($denied = $this->authorize(['superadmin', 'bk'])) {
            return $denied;
        }

        return $this->studentDirectory('bk/data-siswa');
    }

    public function wakaTeachers()
    {
        if ($denied = $this->authorize(['superadmin', 'waka'])) {
            return $denied;
        }

        $keyword = trim((string) $this->request->getGet('q'));

        $query = $this->db->table('tb_guru')
            ->select('nuptk, nama_guru, jenis_kelamin')
            ->orderBy('nama_guru');

        if ($keyword !== '') {
            $query->groupStart()
                ->like('nuptk', $keyword)
                ->orLike('nama_guru', $keyword)
                ->orLike('jenis_kelamin', $keyword)
                ->groupEnd();
        }

        return view('shared/directory', [
            'title' => 'Data Guru',
            'ctx' => 'directory-guru',
            'directoryType' => 'guru',
            'listUrl' => base_url('waka/data-guru'),
            'keyword' => $keyword,
            'rows' => $query->get()->getResultArray(),
        ]);
    }

    private function studentDirectory(string $listPath)
    {
        $keyword = trim((string) $this->request->getGet('q'));

        $query = $this->db->table('tb_siswa s')
            ->select('s.nis, s.nama_siswa, s.jenis_kelamin, k.tingkat, k.index_kelas, jr.jurusan')
            ->join('tb_kelas k', 'k.id_kelas = s.id_kelas', 'left')
            ->join('tb_jurusan jr', 'jr.id = k.id_jurusan', 'left')
            ->orderBy('k.tingkat')
            ->orderBy('jr.jurusan')
            ->orderBy('k.index_kelas')
            ->orderBy('s.nama_siswa');

        if ($keyword !== '') {
            $query->groupStart()
                ->like('s.nis', $keyword)
                ->orLike('s.nama_siswa', $keyword)
                ->orLike('k.tingkat', $keyword)
                ->orLike('jr.jurusan', $keyword)
                ->orLike('k.index_kelas', $keyword)
                ->groupEnd();
        }

        $rows = $query->get()->getResultArray();
        foreach ($rows as &$row) {
            $row['kelas_label'] = trim(
                ($row['tingkat'] ?? '') . ' '
                . ($row['jurusan'] ?? '') . ' '
                . ($row['index_kelas'] ?? '')
            );
        }
        unset($row);

        return view('shared/directory', [
            'title' => 'Data Siswa',
            'ctx' => 'directory-siswa',
            'directoryType' => 'siswa',
            'listUrl' => base_url($listPath),
            'keyword' => $keyword,
            'rows' => $rows,
        ]);
    }
}
