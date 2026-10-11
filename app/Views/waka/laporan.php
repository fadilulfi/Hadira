<?= $this->extend('templates/admin_page_layout') ?>
<?= $this->section('content') ?>
<div class="content"><div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-3 no-print"><div><h3 class="mb-0">Rekap Absensi Waka</h3><small class="text-muted">Panel baru</small></div></div>

<style>
  .stat-card .display-4{font-size:2rem;font-weight:700}
  .table-responsive{overflow-x:auto}
  @media print{.no-print{display:none!important}.sidebar,.navbar,.footer{display:none!important}.main-panel{width:100%!important}.content{margin:0!important}}
</style>


<form class="row no-print mb-3"><div class="col-md-2"><label>Mulai</label><input class="form-control" type="date" name="mulai" value="<?= esc($start) ?>"></div><div class="col-md-2"><label>Sampai</label><input class="form-control" type="date" name="sampai" value="<?= esc($end) ?>"></div><div class="col-md-3"><label>Guru</label><select class="form-control" name="guru"><option value="0">Semua</option><?php foreach($teachers as $g): ?><option value="<?= $g['id_guru'] ?>" <?= $teacher==$g['id_guru']?'selected':'' ?>><?= esc($g['nama_guru']) ?></option><?php endforeach; ?></select></div><div class="col-md-3"><label>Kelas</label><select class="form-control" name="kelas"><option value="0">Semua</option><?php foreach($classes as $c): ?><option value="<?= $c['id_kelas'] ?>" <?= $class==$c['id_kelas']?'selected':'' ?>><?= esc(trim($c['tingkat'].' '.$c['jurusan'].' '.$c['index_kelas'])) ?></option><?php endforeach; ?></select></div><div class="col-md-2 align-self-end"><button class="btn btn-info btn-block">Filter</button></div></form><div class="mb-3 no-print"><button class="btn btn-default" onclick="window.print()"><i class="material-icons">print</i> Cetak</button></div>
<div class="card"><div class="card-body"><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Hari</th><th>Jam</th><th>Guru</th><th>Mapel</th><th>Kelas</th><th>Terisi</th><th>Hadir</th><th>Sakit</th><th>Izin</th><th>Alpa</th></tr></thead><tbody><?php $hariNames=[1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu']; foreach($rows as $r): ?><tr><td><?= $hariNames[(int)$r['hari']] ?></td><td><?= esc($r['nama_jam']) ?></td><td><?= esc($r['nama_guru']) ?></td><td><?= esc($r['nama_mapel']) ?></td><td><?= esc($r['kelas_label']) ?></td><td><?= $r['terisi'] ?>/<?= $r['total_siswa'] ?></td><td><?= $r['hadir'] ?></td><td><?= $r['sakit'] ?></td><td><?= $r['izin'] ?></td><td><?= $r['alpa'] ?></td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="10" class="text-center">Tidak ada data.</td></tr><?php endif; ?></tbody></table></div></div></div>

</div></div>
<?= $this->endSection() ?>