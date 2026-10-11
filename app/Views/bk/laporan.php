<?= $this->extend('templates/role_page_layout') ?>
<?= $this->section('content') ?>
<div class="content"><div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-3 no-print"><div><h3 class="mb-0">Rekap Kehadiran BK</h3><small class="text-muted">Panel baru</small></div></div>

<style>
  .stat-card .display-4{font-size:2rem;font-weight:700}
  .table-responsive{overflow-x:auto}
  @media print{.no-print{display:none!important}.sidebar,.navbar,.footer{display:none!important}.main-panel{width:100%!important}.content{margin:0!important}}
</style>


<form class="row no-print mb-3"><div class="col-md-3"><label>Mulai</label><input class="form-control" type="date" name="mulai" value="<?= esc($start) ?>"></div><div class="col-md-3"><label>Sampai</label><input class="form-control" type="date" name="sampai" value="<?= esc($end) ?>"></div><div class="col-md-4"><label>Kelas</label><select class="form-control" name="kelas"><option value="0">Semua Kelas</option><?php foreach($classes as $c): ?><option value="<?= $c['id_kelas'] ?>" <?= $kelasId==$c['id_kelas']?'selected':'' ?>><?= esc(trim($c['tingkat'].' '.$c['jurusan'].' '.$c['index_kelas'])) ?></option><?php endforeach; ?></select></div><div class="col-md-2 align-self-end"><button class="btn btn-info btn-block">Filter</button></div></form>
<div class="mb-3 no-print"><button class="btn btn-default" onclick="window.print()"><i class="material-icons">print</i> Cetak</button></div>
<div class="card"><div class="card-body"><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>No</th><th>Siswa</th><th>Kelas</th><th>Hadir</th><th>Sakit</th><th>Izin</th><th>Alpa</th><th>Total Jam</th></tr></thead><tbody><?php $no=1; foreach($rows as $r): ?><tr><td><?= $no++ ?></td><td><?= esc($r['nama_siswa']) ?><br><small><?= esc($r['nis']) ?></small></td><td><?= esc(trim($r['tingkat'].' '.$r['jurusan'].' '.$r['index_kelas'])) ?></td><td><?= $r['hadir'] ?></td><td><?= $r['sakit'] ?></td><td><?= $r['izin'] ?></td><td><?= $r['alpa'] ?></td><td><?= $r['total_jam'] ?></td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="8" class="text-center">Tidak ada data.</td></tr><?php endif; ?></tbody></table></div></div></div>

</div></div>
<?= $this->endSection() ?>