<?= $this->extend('templates/role_page_layout') ?>
<?= $this->section('content') ?>
<div class="content"><div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-3 no-print"><div><h3 class="mb-0">Dashboard BK</h3><small class="text-muted">Panel baru</small></div></div>

<style>
  .stat-card .display-4{font-size:2rem;font-weight:700}
  .table-responsive{overflow-x:auto}
  @media print{.no-print{display:none!important}.sidebar,.navbar,.footer{display:none!important}.main-panel{width:100%!important}.content{margin:0!important}}
</style>


<form class="form-inline mb-3 no-print" method="get"><label class="mr-2">Tanggal</label><input class="form-control mr-2" type="date" name="tanggal" value="<?= esc($date) ?>"><button class="btn btn-info" type="submit">Tampilkan</button></form>
<div class="row">
<?php foreach ([['Total Catatan',$stats['total'],'info'],['Hadir',$stats['hadir'],'success'],['Sakit',$stats['sakit'],'warning'],['Izin',$stats['izin'],'primary'],['Alpa',$stats['alpa'],'danger'],['Izin Pending',$pending,'secondary']] as $c): ?><div class="col-md-4 col-lg-2"><div class="card stat-card"><div class="card-body text-center"><div class="text-muted small"><?= $c[0] ?></div><div class="display-4 text-<?= $c[2] ?>"><?= number_format($c[1]) ?></div></div></div></div><?php endforeach; ?>
</div>
<div class="card"><div class="card-header"><h4 class="card-title">Aktivitas Absensi Terbaru</h4></div><div class="card-body"><div class="table-responsive"><table class="table table-striped"><thead><tr><th>Waktu</th><th>Siswa</th><th>Kelas</th><th>Mapel</th></tr></thead><tbody><?php foreach($recent as $r): ?><tr><td><?= esc($r['waktu_absen'] ?? '-') ?></td><td><?= esc($r['nama_siswa']) ?></td><td><?= esc(trim(($r['tingkat']??'').' '.($r['jurusan']??'').' '.($r['index_kelas']??''))) ?></td><td><?= esc($r['nama_mapel']) ?></td></tr><?php endforeach; ?><?php if(!$recent): ?><tr><td colspan="4" class="text-center text-muted">Belum ada data.</td></tr><?php endif; ?></tbody></table></div></div></div>

</div></div>
<?= $this->endSection() ?>