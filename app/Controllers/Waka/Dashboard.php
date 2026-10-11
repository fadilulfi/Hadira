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
    protected function jams(): array
    {
        return $this->db->table('tb_jam_pelajaran')->where('aktif',1)->orderBy('urutan')->get()->getResultArray();
    }
    protected function dayName(int $day): string
    {
        return [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu'][$day] ?? '-';
    }

    public function index()
    {
        if ($r=$this->guard()) return $r;
        $today=(int)date('N'); $date=date('Y-m-d');
        $scheduleCount=(int)$this->db->table('tb_jadwal_pelajaran')->where('aktif',1)->countAllResults();
        $teacherCount=(int)$this->db->table('tb_guru')->countAllResults();
        $classCount=(int)$this->db->table('tb_kelas')->countAllResults();
        $todaySchedules=$this->db->table('tb_jadwal_pelajaran j')
            ->select('j.*, g.nama_guru, mp.nama_mapel, jp.nama_jam, jp.jam_mulai, jp.jam_selesai, k.tingkat, k.index_kelas, jr.jurusan')
            ->join('tb_guru g','g.id_guru=j.id_guru')->join('tb_mapel mp','mp.id_mapel=j.id_mapel')
            ->join('tb_jam_pelajaran jp','jp.id_jam=j.id_jam')->join('tb_kelas k','k.id_kelas=j.id_kelas')
            ->join('tb_jurusan jr','jr.id=k.id_jurusan','left')->where('j.hari',$today)->where('j.aktif',1)->orderBy('jp.urutan')->get()->getResultArray();
        foreach($todaySchedules as &$row){
            $row['kelas_label']=trim(($row['tingkat']??'').' '.($row['jurusan']??'').' '.($row['index_kelas']??''));
            $row['absen_count']=(int)$this->db->table('tb_absensi_pelajaran')->where('id_jadwal',(int)$row['id_jadwal'])->where('tanggal',$date)->countAllResults();
            $row['total_siswa']=(int)$this->db->table('tb_siswa')->where('id_kelas',(int)$row['id_kelas'])->countAllResults();
        } unset($row);
        $done=array_sum(array_map(fn($x)=>$x['absen_count']>0?1:0,$todaySchedules));
        return view('waka/dashboard',[ 'title'=>'Dashboard Waka','ctx'=>'waka-dashboard','scheduleCount'=>$scheduleCount,'teacherCount'=>$teacherCount,'classCount'=>$classCount,'todaySchedules'=>$todaySchedules,'done'=>$done,'today'=>$today ]);
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
        if($r=$this->guard())return$r;
        $day=(int)($this->request->getGet('hari')?:0); $teacher=(int)($this->request->getGet('guru')?:0); $class=(int)($this->request->getGet('kelas')?:0);
        $editId=(int)($this->request->getGet('edit')?:0);
        $q=$this->scheduleQuery()->orderBy('j.hari')->orderBy('jp.urutan'); if($day)$q->where('j.hari',$day); if($teacher)$q->where('j.id_guru',$teacher); if($class)$q->where('j.id_kelas',$class);
        $rows=$q->get()->getResultArray();
        $editRow=$editId?( $this->db->table('tb_jadwal_pelajaran')->where('id_jadwal',$editId)->get()->getRowArray() ?: null ):null;
        return view('waka/jadwal',['title'=>'Jadwal Pelajaran','ctx'=>'waka-jadwal','rows'=>$rows,'editRow'=>$editRow,'teachers'=>$this->teachers(),'classes'=>$this->classes(),'mapels'=>$this->mapels(),'jams'=>$this->jams(),'day'=>$day,'teacher'=>$teacher,'class'=>$class]);
    }

    public function jadwalSave()
    {
        if($r=$this->guard())return$r;
        $id=(int)$this->request->getPost('id_jadwal'); $day=(int)$this->request->getPost('hari'); $guru=(int)$this->request->getPost('id_guru'); $mapel=(int)$this->request->getPost('id_mapel'); $kelas=(int)$this->request->getPost('id_kelas'); $jam=(int)$this->request->getPost('id_jam'); $year=trim((string)$this->request->getPost('tahun_ajaran'))?:'2026/2027'; $semester=$this->request->getPost('semester')==='Genap'?'Genap':'Ganjil';
        if(!in_array($day,[1,2,3,4,5,6],true)||!$guru||!$mapel||!$kelas||!$jam)return redirect()->back()->with('error','Guru, mapel, kelas, hari, dan jam wajib diisi.');
        $dup=$this->db->table('tb_jadwal_pelajaran')->where(['hari'=>$day,'id_guru'=>$guru,'id_mapel'=>$mapel,'id_kelas'=>$kelas,'id_jam'=>$jam,'tahun_ajaran'=>$year,'semester'=>$semester]); if($id)$dup->where('id_jadwal !=',$id);
        if($dup->countAllResults())return redirect()->back()->with('error','Jadwal yang sama sudah ada.');

        // Satu guru tidak boleh mengajar dua kelas pada jam yang sama.
        $teacherBusy=$this->db->table('tb_jadwal_pelajaran')->where(['hari'=>$day,'id_guru'=>$guru,'id_jam'=>$jam,'tahun_ajaran'=>$year,'semester'=>$semester,'aktif'=>1]); if($id)$teacherBusy->where('id_jadwal !=',$id);
        if($teacherBusy->countAllResults())return redirect()->back()->with('error','Guru tersebut sudah memiliki jadwal pada jam yang sama.');

        // Satu kelas tidak boleh memiliki dua pelajaran pada jam yang sama.
        $classBusy=$this->db->table('tb_jadwal_pelajaran')->where(['hari'=>$day,'id_kelas'=>$kelas,'id_jam'=>$jam,'tahun_ajaran'=>$year,'semester'=>$semester,'aktif'=>1]); if($id)$classBusy->where('id_jadwal !=',$id);
        if($classBusy->countAllResults())return redirect()->back()->with('error','Kelas tersebut sudah memiliki jadwal pada jam yang sama.');
        $data=['hari'=>$day,'id_guru'=>$guru,'id_mapel'=>$mapel,'id_kelas'=>$kelas,'id_jam'=>$jam,'tahun_ajaran'=>$year,'semester'=>$semester,'aktif'=>(int)($this->request->getPost('aktif')??1),'updated_at'=>date('Y-m-d H:i:s')];
        if($id)$this->db->table('tb_jadwal_pelajaran')->where('id_jadwal',$id)->update($data);else{$data['created_at']=date('Y-m-d H:i:s');$this->db->table('tb_jadwal_pelajaran')->insert($data);}
        return redirect()->to('/waka/jadwal')->with('success','Jadwal pelajaran berhasil disimpan.');
    }
    public function jadwalToggle($id)
    {
        if($r=$this->guard())return$r; $row=$this->db->table('tb_jadwal_pelajaran')->where('id_jadwal',(int)$id)->get()->getRowArray(); if(!$row)return redirect()->back()->with('error','Jadwal tidak ditemukan.');
        $this->db->table('tb_jadwal_pelajaran')->where('id_jadwal',(int)$id)->update(['aktif'=>$row['aktif']?0:1,'updated_at'=>date('Y-m-d H:i:s')]); return redirect()->back()->with('success','Status jadwal diperbarui.');
    }

    public function monitoring()
    {
        if($r=$this->guard())return$r;
        $date=$this->request->getGet('tanggal')?:date('Y-m-d'); $class=(int)($this->request->getGet('kelas')?:0); $teacher=(int)($this->request->getGet('guru')?:0);
        $q=$this->scheduleQuery()->where('j.hari',date('N',strtotime($date)))->where('j.aktif',1)->orderBy('jp.urutan'); if($class)$q->where('j.id_kelas',$class);if($teacher)$q->where('j.id_guru',$teacher); $rows=$q->get()->getResultArray();
        foreach($rows as &$row){$row['kelas_label']=trim(($row['tingkat']??'').' '.($row['jurusan']??'').' '.($row['index_kelas']??''));$row['total_siswa']=(int)$this->db->table('tb_siswa')->where('id_kelas',$row['id_kelas'])->countAllResults();$row['terisi']=(int)$this->db->table('tb_absensi_pelajaran')->where('id_jadwal',$row['id_jadwal'])->where('tanggal',$date)->countAllResults();$row['hadir']=(int)$this->db->table('tb_absensi_pelajaran')->where(['id_jadwal'=>$row['id_jadwal'],'tanggal'=>$date,'id_kehadiran'=>1])->countAllResults();$row['izin']=(int)$this->db->table('tb_absensi_pelajaran')->where(['id_jadwal'=>$row['id_jadwal'],'tanggal'=>$date,'id_kehadiran'=>3])->countAllResults();$row['sakit']=(int)$this->db->table('tb_absensi_pelajaran')->where(['id_jadwal'=>$row['id_jadwal'],'tanggal'=>$date,'id_kehadiran'=>2])->countAllResults();$row['alpa']=(int)$this->db->table('tb_absensi_pelajaran')->where(['id_jadwal'=>$row['id_jadwal'],'tanggal'=>$date,'id_kehadiran'=>4])->countAllResults();$row['persen']=$row['total_siswa']?round(($row['terisi']/$row['total_siswa'])*100):0;}unset($row);
        return view('waka/monitoring',['title'=>'Monitoring Absensi','ctx'=>'waka-monitoring','date'=>$date,'class'=>$class,'teacher'=>$teacher,'classes'=>$this->classes(),'teachers'=>$this->teachers(),'rows'=>$rows]);
    }

    public function laporan()
    {
        if($r=$this->guard())return$r;
        $start=$this->request->getGet('mulai')?:date('Y-m-01');$end=$this->request->getGet('sampai')?:date('Y-m-d');$class=(int)($this->request->getGet('kelas')?:0);$teacher=(int)($this->request->getGet('guru')?:0);
        $q=$this->scheduleQuery()->select("j.id_jadwal,j.hari,j.id_kelas,j.tahun_ajaran,j.semester,j.aktif,g.nama_guru,mp.nama_mapel,jp.nama_jam,jp.jam_mulai,jp.jam_selesai,k.tingkat,k.index_kelas,jr.jurusan,\n            COUNT(DISTINCT ap.id_siswa) terisi,\n            SUM(CASE WHEN ap.id_kehadiran=1 THEN 1 ELSE 0 END) hadir,\n            SUM(CASE WHEN ap.id_kehadiran=2 THEN 1 ELSE 0 END) sakit,\n            SUM(CASE WHEN ap.id_kehadiran=3 THEN 1 ELSE 0 END) izin,\n            SUM(CASE WHEN ap.id_kehadiran=4 THEN 1 ELSE 0 END) alpa",false)
          ->join('tb_absensi_pelajaran ap','ap.id_jadwal=j.id_jadwal AND ap.tanggal >= '.$this->db->escape($start).' AND ap.tanggal <= '.$this->db->escape($end),'left')
          ->groupBy('j.id_jadwal,j.hari,j.id_kelas,j.tahun_ajaran,j.semester,j.aktif,g.nama_guru,mp.nama_mapel,jp.nama_jam,jp.jam_mulai,jp.jam_selesai,k.tingkat,k.index_kelas,jr.jurusan')
          ->orderBy('j.hari')->orderBy('jp.urutan');
        if($class)$q->where('j.id_kelas',$class);if($teacher)$q->where('j.id_guru',$teacher);
        $rows=$q->get()->getResultArray(); foreach($rows as &$row){$row['kelas_label']=trim(($row['tingkat']??'').' '.($row['jurusan']??'').' '.($row['index_kelas']??''));$row['total_siswa']=(int)$this->db->table('tb_siswa')->where('id_kelas',$row['id_kelas'])->countAllResults();}unset($row);
        return view('waka/laporan',['title'=>'Rekap Absensi Waka','ctx'=>'waka-laporan','start'=>$start,'end'=>$end,'class'=>$class,'teacher'=>$teacher,'classes'=>$this->classes(),'teachers'=>$this->teachers(),'rows'=>$rows]);
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
