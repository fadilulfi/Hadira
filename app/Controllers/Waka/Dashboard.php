<?php

namespace App\Controllers\Waka;

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
        if (!$user || (!$user->inGroup('waka') && !$user->inGroup('superadmin'))) return redirect()->to('/');
        return null;
    }

    protected function teachers(): array
    {
        return $this->db->table('tb_guru')->orderBy('nama_guru')->get()->getResultArray();
    }
    protected function classes(): array
    {
        return $this->db->table('tb_kelas k')->select('k.*, j.jurusan')->join('tb_jurusan j','j.id=k.id_jurusan','left')->orderBy('k.tingkat')->orderBy('j.jurusan')->orderBy('k.index_kelas')->get()->getResultArray();
    }
    protected function mapels(): array
    {
        return $this->db->table('tb_mapel')->where('aktif',1)->orderBy('nama_mapel')->get()->getResultArray();
    }
    /**
     * Return atomic lesson periods only. Existing combined blocks (e.g. 3-4)
     * remain stored in tb_jam_pelajaran, but are generated/reused by jadwalSave().
     */
    protected function jams(): array
    {
        $rows = $this->db->table('tb_jam_pelajaran')
            ->where('aktif', 1)
            ->orderBy('urutan')
            ->orderBy('jam_mulai')
            ->get()
            ->getResultArray();

        return array_values(array_filter($rows, static function (array $jam): bool {
            return preg_match('/^\s*\d+\s*[-–]\s*\d+\s*$/u', (string) $jam['nama_jam']) !== 1;
        }));
    }
    protected function dayName(int $day): string
    {
        return [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu'][$day] ?? '-';
    }

    public function index()
    {
        if ($r = $this->guard()) return $r;

        $today = (int) date('N');
        $date = date('Y-m-d');

        $scheduleCount = (int) $this->db->table('tb_jadwal_pelajaran')->where('aktif', 1)->countAllResults();
        $teacherCount = (int) $this->db->table('tb_guru')->countAllResults();
        $classCount = (int) $this->db->table('tb_kelas')->countAllResults();

        $todaySchedules = $this->db->table('tb_jadwal_pelajaran j')
            ->select('j.*, g.nama_guru, mp.nama_mapel, jp.nama_jam, jp.jam_mulai, jp.jam_selesai, k.tingkat, k.index_kelas, jr.jurusan')
            ->join('tb_guru g', 'g.id_guru = j.id_guru')
            ->join('tb_mapel mp', 'mp.id_mapel = j.id_mapel')
            ->join('tb_jam_pelajaran jp', 'jp.id_jam = j.id_jam')
            ->join('tb_kelas k', 'k.id_kelas = j.id_kelas')
            ->join('tb_jurusan jr', 'jr.id = k.id_jurusan', 'left')
            ->where('j.hari', $today)
            ->where('j.aktif', 1)
            ->orderBy('jp.urutan')
            ->get()
            ->getResultArray();

        // Batch schedule attendance counts instead of one query per schedule.
        $scheduleIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['id_jadwal'],
            $todaySchedules
        )));
        $attendanceCountBySchedule = [];
        if ($scheduleIds !== []) {
            $attendanceRows = $this->db->table('tb_absensi_pelajaran')
                ->select('id_jadwal')
                ->selectCount('id_absensi', 'absen_count')
                ->where('tanggal', $date)
                ->whereIn('id_jadwal', $scheduleIds)
                ->groupBy('id_jadwal')
                ->get()
                ->getResultArray();

            foreach ($attendanceRows as $attendanceRow) {
                $attendanceCountBySchedule[(int) $attendanceRow['id_jadwal']] = (int) $attendanceRow['absen_count'];
            }
        }

        // Batch student counts for all classes instead of querying per schedule.
        $studentCountsByClass = [];
        $studentCountRows = $this->db->table('tb_siswa')
            ->select('id_kelas')
            ->selectCount('id_siswa', 'total_siswa')
            ->groupBy('id_kelas')
            ->get()
            ->getResultArray();
        foreach ($studentCountRows as $studentCountRow) {
            $studentCountsByClass[(int) $studentCountRow['id_kelas']] = (int) $studentCountRow['total_siswa'];
        }

        foreach ($todaySchedules as &$row) {
            $row['kelas_label'] = trim(($row['tingkat'] ?? '') . ' ' . ($row['jurusan'] ?? '') . ' ' . ($row['index_kelas'] ?? ''));
            $row['absen_count'] = $attendanceCountBySchedule[(int) $row['id_jadwal']] ?? 0;
            $row['total_siswa'] = $studentCountsByClass[(int) $row['id_kelas']] ?? 0;
        }
        unset($row);

        $done = array_sum(array_map(static fn (array $row): int => $row['absen_count'] > 0 ? 1 : 0, $todaySchedules));

        return view('waka/dashboard', [
            'title' => 'Dashboard Waka',
            'ctx' => 'waka-dashboard',
            'scheduleCount' => $scheduleCount,
            'teacherCount' => $teacherCount,
            'classCount' => $classCount,
            'todaySchedules' => $todaySchedules,
            'done' => $done,
            'today' => $today,
        ]);
    }

    public function mapel()
    {
        if($r=$this->guard())return$r;
        $rows=$this->db->table('tb_mapel')->orderBy('aktif','DESC')->orderBy('nama_mapel')->get()->getResultArray();
        $editId=(int)($this->request->getGet('edit')?:0);
        $editRow=$editId?( $this->db->table('tb_mapel')->where('id_mapel',$editId)->get()->getRowArray() ?: null ):null;
        return view('waka/mapel',['title'=>'Mata Pelajaran','ctx'=>'waka-mapel','rows'=>$rows,'editRow'=>$editRow]);
    }
    public function mapelSave()
    {
        if($r=$this->guard())return$r;
        $id=(int)$this->request->getPost('id_mapel'); $name=trim((string)$this->request->getPost('nama_mapel')); $code=trim((string)$this->request->getPost('kode_mapel'));
        if($name==='')return redirect()->back()->with('error','Nama mata pelajaran wajib diisi.');
        $q=$this->db->table('tb_mapel')->where('nama_mapel',$name); if($id)$q->where('id_mapel !=',$id);
        if($q->countAllResults())return redirect()->back()->with('error','Nama mata pelajaran sudah ada.');
        $data=['nama_mapel'=>$name,'kode_mapel'=>$code!==''?$code:null,'aktif'=>(int)($this->request->getPost('aktif')??1),'updated_at'=>date('Y-m-d H:i:s')];
        if($id)$this->db->table('tb_mapel')->where('id_mapel',$id)->update($data); else {$data['created_at']=date('Y-m-d H:i:s');$this->db->table('tb_mapel')->insert($data);}
        return redirect()->to('/waka/mapel')->with('success','Mata pelajaran berhasil disimpan.');
    }
    public function mapelToggle($id)
    {
        if($r=$this->guard())return$r; $row=$this->db->table('tb_mapel')->where('id_mapel',(int)$id)->get()->getRowArray(); if(!$row)return redirect()->back()->with('error','Data tidak ditemukan.');
        $this->db->table('tb_mapel')->where('id_mapel',(int)$id)->update(['aktif'=>$row['aktif']?0:1,'updated_at'=>date('Y-m-d H:i:s')]); return redirect()->back()->with('success','Status mata pelajaran diperbarui.');
    }

    public function jam()
    {
        if($r=$this->guard())return$r;
        $rows=$this->db->table('tb_jam_pelajaran')->orderBy('urutan')->get()->getResultArray();
        $editId=(int)($this->request->getGet('edit')?:0);
        $editRow=$editId?( $this->db->table('tb_jam_pelajaran')->where('id_jam',$editId)->get()->getRowArray() ?: null ):null;
        return view('waka/jam',['title'=>'Jam Pelajaran','ctx'=>'waka-jam','rows'=>$rows,'editRow'=>$editRow]);
    }
    public function jamSave()
    {
        if($r=$this->guard())return$r;
        $id=(int)$this->request->getPost('id_jam'); $name=trim((string)$this->request->getPost('nama_jam')); $mulai=(string)$this->request->getPost('jam_mulai'); $selesai=(string)$this->request->getPost('jam_selesai'); $urutan=(int)$this->request->getPost('urutan');
        if($name===''||$mulai===''||$selesai===''||$urutan<1)return redirect()->back()->with('error','Nama, waktu, dan urutan wajib diisi.');
        $q=$this->db->table('tb_jam_pelajaran')->where('nama_jam',$name); if($id)$q->where('id_jam !=',$id); if($q->countAllResults())return redirect()->back()->with('error','Nama jam sudah ada.');
        $data=['nama_jam'=>$name,'jam_mulai'=>$mulai,'jam_selesai'=>$selesai,'urutan'=>$urutan,'aktif'=>(int)($this->request->getPost('aktif')??1),'updated_at'=>date('Y-m-d H:i:s')];
        if($id)$this->db->table('tb_jam_pelajaran')->where('id_jam',$id)->update($data); else {$data['created_at']=date('Y-m-d H:i:s');$this->db->table('tb_jam_pelajaran')->insert($data);}
        return redirect()->to('/waka/jam')->with('success','Jam pelajaran berhasil disimpan.');
    }
    public function jamToggle($id)
    {
        if($r=$this->guard())return$r; $row=$this->db->table('tb_jam_pelajaran')->where('id_jam',(int)$id)->get()->getRowArray(); if(!$row)return redirect()->back()->with('error','Data tidak ditemukan.');
        $this->db->table('tb_jam_pelajaran')->where('id_jam',(int)$id)->update(['aktif'=>$row['aktif']?0:1,'updated_at'=>date('Y-m-d H:i:s')]); return redirect()->back()->with('success','Status jam pelajaran diperbarui.');
    }

    protected function scheduleQuery()
    {
        return $this->db->table('tb_jadwal_pelajaran j')
            ->select('j.*, g.nama_guru, mp.nama_mapel, jp.nama_jam, jp.jam_mulai, jp.jam_selesai, k.tingkat, k.index_kelas, jr.jurusan')
            ->join('tb_guru g','g.id_guru=j.id_guru')->join('tb_mapel mp','mp.id_mapel=j.id_mapel')
            ->join('tb_jam_pelajaran jp','jp.id_jam=j.id_jam')->join('tb_kelas k','k.id_kelas=j.id_kelas')
            ->join('tb_jurusan jr','jr.id=k.id_jurusan','left');
    }

    public function jadwal()
    {
        if ($r = $this->guard()) return $r;

        $day = (int) ($this->request->getGet('hari') ?: 0);
        $teacher = (int) ($this->request->getGet('guru') ?: 0);
        $class = (int) ($this->request->getGet('kelas') ?: 0);
        $editId = (int) ($this->request->getGet('edit') ?: 0);

        $query = $this->scheduleQuery()->orderBy('j.hari')->orderBy('jp.urutan');
        if ($day) $query->where('j.hari', $day);
        if ($teacher) $query->where('j.id_guru', $teacher);
        if ($class) $query->where('j.id_kelas', $class);
        $rows = $query->get()->getResultArray();

        $editRow = $editId
            ? ($this->db->table('tb_jadwal_pelajaran j')
                ->select('j.*, jp.nama_jam, jp.jam_mulai, jp.jam_selesai, jp.urutan AS jam_urutan')
                ->join('tb_jam_pelajaran jp', 'jp.id_jam = j.id_jam')
                ->where('j.id_jadwal', $editId)
                ->get()
                ->getRowArray() ?: null)
            : null;

        $periods = $this->jams();
        $editStartId = (int) ($editRow['id_jam'] ?? 0);
        $editDuration = 1;

        if ($editRow && preg_match('/^\\s*(\\d+)\\s*[-–]\\s*(\\d+)\\s*$/u', (string) $editRow['nama_jam'], $matches)) {
            $startOrder = (int) $matches[1];
            $endOrder = (int) $matches[2];
            $editDuration = max(1, min(3, $endOrder - $startOrder + 1));

            foreach ($periods as $period) {
                if ((int) $period['urutan'] === $startOrder) {
                    $editStartId = (int) $period['id_jam'];
                    break;
                }
            }
        }

        return view('waka/jadwal', [
            'title' => 'Jadwal Pelajaran',
            'ctx' => 'waka-jadwal',
            'rows' => $rows,
            'editRow' => $editRow,
            'teachers' => $this->teachers(),
            'classes' => $this->classes(),
            'mapels' => $this->mapels(),
            'jams' => $periods,
            'editStartId' => $editStartId,
            'editDuration' => $editDuration,
            'day' => $day,
            'teacher' => $teacher,
            'class' => $class,
        ]);
    }

    public function jadwalSave()
    {
        if ($r = $this->guard()) return $r;

        $id = (int) $this->request->getPost('id_jadwal');
        $day = (int) $this->request->getPost('hari');
        $teacher = (int) $this->request->getPost('id_guru');
        $mapel = (int) $this->request->getPost('id_mapel');
        $class = (int) $this->request->getPost('id_kelas');
        $startPeriodId = (int) $this->request->getPost('id_jam_mulai');
        $duration = (int) $this->request->getPost('durasi_jp');
        $year = trim((string) $this->request->getPost('tahun_ajaran')) ?: '2026/2027';
        $semester = $this->request->getPost('semester') === 'Genap' ? 'Genap' : 'Ganjil';

        if (!in_array($day, [1, 2, 3, 4, 5, 6], true)
            || !$teacher || !$mapel || !$class || !$startPeriodId
            || !in_array($duration, [1, 2, 3], true)) {
            return redirect()->back()->withInput()->with('error', 'Lengkapi data jadwal dan pilih durasi 1, 2, atau 3 JP.');
        }

        $periods = $this->jams();
        $periodsById = [];
        $periodsByOrder = [];
        foreach ($periods as $period) {
            $periodsById[(int) $period['id_jam']] = $period;
            $order = (int) $period['urutan'];
            if (isset($periodsByOrder[$order])) {
                return redirect()->back()->withInput()->with('error', 'Urutan JP harus unik untuk setiap jam pelajaran. Periksa menu Jam Pelajaran.');
            }
            $periodsByOrder[$order] = $period;
        }

        $startPeriod = $periodsById[$startPeriodId] ?? null;
        if (!$startPeriod) {
            return redirect()->back()->withInput()->with('error', 'Jam mulai tidak valid atau sudah nonaktif.');
        }

        $startOrder = (int) $startPeriod['urutan'];
        $endOrder = $startOrder + $duration - 1;
        $endPeriod = $periodsByOrder[$endOrder] ?? null;

        if (!$endPeriod) {
            return redirect()->back()->withInput()->with('error', 'Durasi yang dipilih membutuhkan JP berurutan. Lengkapi urutan pada menu Jam Pelajaran.');
        }

        $startTime = (string) $startPeriod['jam_mulai'];
        $endTime = (string) $endPeriod['jam_selesai'];
        if ($startTime === '' || $endTime === '' || $endTime <= $startTime) {
            return redirect()->back()->withInput()->with('error', 'Rentang waktu JP tidak valid. Periksa jam mulai dan selesai pada menu Jam Pelajaran.');
        }

        $db = $this->db;
        $db->transBegin();

        // One schedule row represents the full time range. For 2-3 JP, reuse
        // an existing configured block or create a range block such as "3-4".
        $idJam = $startPeriodId;
        if ($duration > 1) {
            $blockName = $startOrder . '-' . $endOrder;
            $existingBlock = $db->table('tb_jam_pelajaran')
                ->where('nama_jam', $blockName)
                ->get()
                ->getRowArray();

            if ($existingBlock) {
                if (!(int) $existingBlock['aktif']) {
                    $db->transRollback();
                    return redirect()->back()->withInput()->with('error', "Blok JP {$blockName} sedang nonaktif. Aktifkan blok tersebut di menu Jam Pelajaran terlebih dahulu.");
                }
                if ((string) $existingBlock['jam_mulai'] !== $startTime
                    || (string) $existingBlock['jam_selesai'] !== $endTime
                    || (int) $existingBlock['urutan'] !== $startOrder) {
                    $db->transRollback();
                    return redirect()->back()->withInput()->with('error', "Blok JP {$blockName} sudah ada dengan waktu berbeda. Sesuaikan waktu blok di menu Jam Pelajaran terlebih dahulu.");
                }
                $idJam = (int) $existingBlock['id_jam'];
            } else {
                $now = date('Y-m-d H:i:s');
                $inserted = $db->table('tb_jam_pelajaran')->insert([
                    'nama_jam' => $blockName,
                    'jam_mulai' => $startTime,
                    'jam_selesai' => $endTime,
                    'urutan' => $startOrder,
                    'aktif' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                if (!$inserted) {
                    $db->transRollback();
                    return redirect()->back()->withInput()->with('error', "Gagal membuat blok JP {$blockName}. Periksa pengaturan jam pelajaran.");
                }
                $idJam = (int) $db->insertID();
            }
        }

        // Check overlaps by actual times, not just by matching id_jam. This
        // prevents collisions between e.g. JP 3-4 and a separate JP 4 lesson.
        $teacherBusy = $db->table('tb_jadwal_pelajaran j')
            ->join('tb_jam_pelajaran jp', 'jp.id_jam = j.id_jam')
            ->where('j.hari', $day)
            ->where('j.id_guru', $teacher)
            ->where('j.tahun_ajaran', $year)
            ->where('j.semester', $semester)
            ->where('j.aktif', 1)
            ->where('jp.jam_mulai <', $endTime)
            ->where('jp.jam_selesai >', $startTime);
        if ($id) $teacherBusy->where('j.id_jadwal !=', $id);

        if ($teacherBusy->countAllResults() > 0) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'Jadwal guru bertabrakan dengan jam lain pada hari tersebut.');
        }

        $classBusy = $db->table('tb_jadwal_pelajaran j')
            ->join('tb_jam_pelajaran jp', 'jp.id_jam = j.id_jam')
            ->where('j.hari', $day)
            ->where('j.id_kelas', $class)
            ->where('j.tahun_ajaran', $year)
            ->where('j.semester', $semester)
            ->where('j.aktif', 1)
            ->where('jp.jam_mulai <', $endTime)
            ->where('jp.jam_selesai >', $startTime);
        if ($id) $classBusy->where('j.id_jadwal !=', $id);

        if ($classBusy->countAllResults() > 0) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'Kelas sudah memiliki pelajaran lain pada rentang waktu tersebut.');
        }

        $now = date('Y-m-d H:i:s');
        $data = [
            'hari' => $day,
            'id_guru' => $teacher,
            'id_mapel' => $mapel,
            'id_kelas' => $class,
            'id_jam' => $idJam,
            'tahun_ajaran' => $year,
            'semester' => $semester,
            'aktif' => (int) ($this->request->getPost('aktif') ?? 1),
            'updated_at' => $now,
        ];

        if ($id) {
            $saved = $db->table('tb_jadwal_pelajaran')->where('id_jadwal', $id)->update($data);
        } else {
            $data['created_at'] = $now;
            $saved = $db->table('tb_jadwal_pelajaran')->insert($data);
        }

        if (!$saved) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'Jadwal gagal disimpan. Periksa kembali data dan jam pelajaran.');
        }

        $db->transComplete();
        if (!$db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Jadwal gagal disimpan karena konflik data.');
        }

        return redirect()->to('/waka/jadwal')->with('success', 'Jadwal berhasil disimpan.');
    }

    public function jadwalToggle($id)
    {
        if($r=$this->guard())return$r; $row=$this->db->table('tb_jadwal_pelajaran')->where('id_jadwal',(int)$id)->get()->getRowArray(); if(!$row)return redirect()->back()->with('error','Jadwal tidak ditemukan.');
        $this->db->table('tb_jadwal_pelajaran')->where('id_jadwal',(int)$id)->update(['aktif'=>$row['aktif']?0:1,'updated_at'=>date('Y-m-d H:i:s')]); return redirect()->back()->with('success','Status jadwal diperbarui.');
    }

    public function monitoring()
    {
        if ($r = $this->guard()) return $r;

        $date = $this->request->getGet('tanggal') ?: date('Y-m-d');
        $class = (int) ($this->request->getGet('kelas') ?: 0);
        $teacher = (int) ($this->request->getGet('guru') ?: 0);

        $query = $this->scheduleQuery()
            ->where('j.hari', date('N', strtotime($date)))
            ->where('j.aktif', 1)
            ->orderBy('jp.urutan');
        if ($class) $query->where('j.id_kelas', $class);
        if ($teacher) $query->where('j.id_guru', $teacher);
        $rows = $query->get()->getResultArray();

        $scheduleIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['id_jadwal'],
            $rows
        )));
        $studentCountsByClass = [];
        $attendanceBySchedule = [];

        if ($rows !== []) {
            $classIds = array_values(array_unique(array_map(
                static fn (array $row): int => (int) $row['id_kelas'],
                $rows
            )));
            $studentCountRows = $this->db->table('tb_siswa')
                ->select('id_kelas')
                ->selectCount('id_siswa', 'total_siswa')
                ->whereIn('id_kelas', $classIds)
                ->groupBy('id_kelas')
                ->get()
                ->getResultArray();
            foreach ($studentCountRows as $studentCountRow) {
                $studentCountsByClass[(int) $studentCountRow['id_kelas']] = (int) $studentCountRow['total_siswa'];
            }

            // Aggregate all attendance statuses per schedule in one query.
            $attendanceRows = $this->db->table('tb_absensi_pelajaran')
                ->select('id_jadwal')
                ->selectCount('id_absensi', 'terisi')
                ->select("SUM(CASE WHEN id_kehadiran = 1 THEN 1 ELSE 0 END) AS hadir", false)
                ->select("SUM(CASE WHEN id_kehadiran = 2 THEN 1 ELSE 0 END) AS sakit", false)
                ->select("SUM(CASE WHEN id_kehadiran = 3 THEN 1 ELSE 0 END) AS izin", false)
                ->select("SUM(CASE WHEN id_kehadiran = 4 THEN 1 ELSE 0 END) AS alpa", false)
                ->where('tanggal', $date)
                ->whereIn('id_jadwal', $scheduleIds)
                ->groupBy('id_jadwal')
                ->get()
                ->getResultArray();
            foreach ($attendanceRows as $attendanceRow) {
                $attendanceBySchedule[(int) $attendanceRow['id_jadwal']] = $attendanceRow;
            }
        }

        foreach ($rows as &$row) {
            $row['kelas_label'] = trim(($row['tingkat'] ?? '') . ' ' . ($row['jurusan'] ?? '') . ' ' . ($row['index_kelas'] ?? ''));
            $row['total_siswa'] = $studentCountsByClass[(int) $row['id_kelas']] ?? 0;
            $stats = $attendanceBySchedule[(int) $row['id_jadwal']] ?? [];
            $row['terisi'] = (int) ($stats['terisi'] ?? 0);
            $row['hadir'] = (int) ($stats['hadir'] ?? 0);
            $row['izin'] = (int) ($stats['izin'] ?? 0);
            $row['sakit'] = (int) ($stats['sakit'] ?? 0);
            $row['alpa'] = (int) ($stats['alpa'] ?? 0);
            $row['persen'] = $row['total_siswa']
                ? round(($row['terisi'] / $row['total_siswa']) * 100)
                : 0;
        }
        unset($row);

        return view('waka/monitoring', [
            'title' => 'Monitoring Absensi',
            'ctx' => 'waka-monitoring',
            'date' => $date,
            'class' => $class,
            'teacher' => $teacher,
            'classes' => $this->classes(),
            'teachers' => $this->teachers(),
            'rows' => $rows,
        ]);
    }

    public function laporan()
    {
        if ($r = $this->guard()) return $r;

        $start = $this->request->getGet('mulai') ?: date('Y-m-01');
        $end = $this->request->getGet('sampai') ?: date('Y-m-d');
        $class = (int) ($this->request->getGet('kelas') ?: 0);
        $teacher = (int) ($this->request->getGet('guru') ?: 0);

        $query = $this->scheduleQuery()
            ->select("j.id_jadwal, j.hari, j.id_kelas, j.tahun_ajaran, j.semester, j.aktif,
                g.nama_guru, mp.nama_mapel, jp.nama_jam, jp.jam_mulai, jp.jam_selesai,
                k.tingkat, k.index_kelas, jr.jurusan,
                COUNT(DISTINCT ap.id_siswa) terisi,
                SUM(CASE WHEN ap.id_kehadiran = 1 THEN 1 ELSE 0 END) hadir,
                SUM(CASE WHEN ap.id_kehadiran = 2 THEN 1 ELSE 0 END) sakit,
                SUM(CASE WHEN ap.id_kehadiran = 3 THEN 1 ELSE 0 END) izin,
                SUM(CASE WHEN ap.id_kehadiran = 4 THEN 1 ELSE 0 END) alpa", false)
            ->join(
                'tb_absensi_pelajaran ap',
                'ap.id_jadwal = j.id_jadwal AND ap.tanggal >= ' . $this->db->escape($start) . ' AND ap.tanggal <= ' . $this->db->escape($end),
                'left'
            )
            ->groupBy('j.id_jadwal, j.hari, j.id_kelas, j.tahun_ajaran, j.semester, j.aktif, g.nama_guru, mp.nama_mapel, jp.nama_jam, jp.jam_mulai, jp.jam_selesai, k.tingkat, k.index_kelas, jr.jurusan')
            ->orderBy('j.hari')
            ->orderBy('jp.urutan');

        if ($class) $query->where('j.id_kelas', $class);
        if ($teacher) $query->where('j.id_guru', $teacher);

        $rows = $query->get()->getResultArray();

        // Batch totals by class instead of querying students once per report row.
        $classIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['id_kelas'],
            $rows
        )));
        $studentCountsByClass = [];
        if ($classIds !== []) {
            $studentCountRows = $this->db->table('tb_siswa')
                ->select('id_kelas')
                ->selectCount('id_siswa', 'total_siswa')
                ->whereIn('id_kelas', $classIds)
                ->groupBy('id_kelas')
                ->get()
                ->getResultArray();

            foreach ($studentCountRows as $studentCountRow) {
                $studentCountsByClass[(int) $studentCountRow['id_kelas']] = (int) $studentCountRow['total_siswa'];
            }
        }

        foreach ($rows as &$row) {
            $row['kelas_label'] = trim(($row['tingkat'] ?? '') . ' ' . ($row['jurusan'] ?? '') . ' ' . ($row['index_kelas'] ?? ''));
            $row['total_siswa'] = $studentCountsByClass[(int) $row['id_kelas']] ?? 0;
        }
        unset($row);

        return view('waka/laporan', [
            'title' => 'Rekap Absensi Waka',
            'ctx' => 'waka-laporan',
            'start' => $start,
            'end' => $end,
            'class' => $class,
            'teacher' => $teacher,
            'classes' => $this->classes(),
            'teachers' => $this->teachers(),
            'rows' => $rows,
        ]);
    }

    public function perizinan()
    {
        if ($r = $this->guard()) return $r;

        $status = $this->request->getGet('status') ?: 'Pending';
        $type   = $this->request->getGet('tipe') ?: '';
        $date   = $this->request->getGet('tanggal') ?: '';

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
        if ($date !== '') {
            $q->where('p.tanggal_mulai <=', $date)
              ->where('p.tanggal_selesai >=', $date);
        }

        return view('waka/perizinan', [
            'title' => 'Data Izin & Sakit',
            'ctx'   => 'waka-perizinan',
            'status'=> $status,
            'type'  => $type,
            'date'  => $date,
            'rows'  => $q->get()->getResultArray(),
        ]);
    }

    public function updatePermission()
    {
        if ($r = $this->guard()) return $r;

        $id = (int) $this->request->getPost('id_perizinan');
        $status = (string) $this->request->getPost('status');

        if (!$id || !in_array($status, ['Disetujui', 'Ditolak'], true)) {
            return redirect()->back()->with('error', 'Permintaan tidak valid.');
        }

        $this->db->table('tb_perizinan')->where('id_perizinan', $id)->update([
            'status'     => $status,
            'id_petugas' => auth()->id(),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Status perizinan berhasil diperbarui.');
    }

}
