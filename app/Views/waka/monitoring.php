<?= $this->extend('templates/role_page_layout') ?>
<?= $this->section('content') ?>
<div class="content"><div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-3 no-print"><div><h3 class="mb-0">Monitoring Absensi</h3><small class="text-muted">Panel baru</small></div></div>

<style>
  .stat-card .display-4{font-size:2rem;font-weight:700}
  .table-responsive{overflow-x:auto}
  @media print{.no-print{display:none!important}.sidebar,.navbar,.footer{display:none!important}.main-panel{width:100%!important}.content{margin:0!important}}
</style>


<form class="row no-print mb-3"><div class="col-md-3"><label>Tanggal</label><input class="form-control" type="date" name="tanggal" value="<?= esc($date) ?>"></div><div class="col-md-3"><label>Guru</label><select class="form-control" name="guru"><option value="0">Semua Guru</option><?php foreach($teachers as $g): ?><option value="<?= $g['id_guru'] ?>" <?= $teacher==$g['id_guru']?'selected':'' ?>><?= esc($g['nama_guru']) ?></option><?php endforeach; ?></select></div><div class="col-md-3"><label>Kelas</label><select class="form-control" name="kelas"><option value="0">Semua Kelas</option><?php foreach($classes as $c): ?><option value="<?= $c['id_kelas'] ?>" <?= $class==$c['id_kelas']?'selected':'' ?>><?= esc(trim($c['tingkat'].' '.$c['jurusan'].' '.$c['index_kelas'])) ?></option><?php endforeach; ?></select></div><div class="col-md-2 align-self-end"><button class="btn btn-info btn-block">Filter</button></div></form>
<div class="card"><div class="card-body"><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Jam</th><th>Guru</th><th>Mapel</th><th>Kelas</th><th>Terisi</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpa</th><th>Progress</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?= esc($r['nama_jam']) ?></td><td><?= esc($r['nama_guru']) ?></td><td><?= esc($r['nama_mapel']) ?></td><td><?= esc($r['kelas_label']) ?></td><td><?= $r['terisi'] ?>/<?= $r['total_siswa'] ?></td><td><?= $r['hadir'] ?></td><td><?= $r['izin'] ?></td><td><?= $r['sakit'] ?></td><td><?= $r['alpa'] ?></td><td><div class="progress"><div class="progress-bar" style="width:<?= $r['persen'] ?>%" role="progressbar"><?= $r['persen'] ?>%</div></div></td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="10" class="text-center text-muted">Tidak ada sesi pada tanggal/filter ini.</td></tr><?php endif; ?></tbody></table></div></div></div>

</div></div>
<?= $this->endSection() ?>