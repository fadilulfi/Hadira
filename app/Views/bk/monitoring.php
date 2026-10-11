<?= $this->extend('templates/role_page_layout') ?>
<?= $this->section('content') ?>
<div class="content"><div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-3 no-print"><div><h3 class="mb-0">Monitoring Kehadiran</h3><small class="text-muted">Panel baru</small></div></div>

<style>
  .stat-card .display-4{font-size:2rem;font-weight:700}
  .table-responsive{overflow-x:auto}
  @media print{.no-print{display:none!important}.sidebar,.navbar,.footer{display:none!important}.main-panel{width:100%!important}.content{margin:0!important}}
</style>


<form class="row no-print mb-3"><div class="col-md-3"><label>Tanggal</label><input class="form-control" type="date" name="tanggal" value="<?= esc($date) ?>"></div><div class="col-md-4"><label>Kelas</label><select class="form-control" name="kelas"><option value="0">Semua Kelas</option><?php foreach($classes as $c): ?><option value="<?= $c['id_kelas'] ?>" <?= $kelasId==$c['id_kelas']?'selected':'' ?>><?= esc(trim($c['tingkat'].' '.$c['jurusan'].' '.$c['index_kelas'])) ?></option><?php endforeach; ?></select></div><div class="col-md-2 align-self-end"><button class="btn btn-info btn-block">Filter</button></div></form>
<div class="card"><div class="card-body"><div class="table-responsive"><table class="table table-hover"><thead><tr><th>No</th><th>NIS</th><th>Siswa</th><th>Kelas</th><th>Hadir</th><th>Sakit</th><th>Izin</th><th>Alpa</th><th>Jam</th></tr></thead><tbody><?php $no=1; foreach($rows as $r): ?><tr><td><?= $no++ ?></td><td><?= esc($r['nis']) ?></td><td><?= esc($r['nama_siswa']) ?></td><td><?= esc(trim($r['tingkat'].' '.$r['jurusan'].' '.$r['index_kelas'])) ?></td><td class="text-success font-weight-bold"><?= $r['hadir'] ?></td><td class="text-warning font-weight-bold"><?= $r['sakit'] ?></td><td class="text-primary font-weight-bold"><?= $r['izin'] ?></td><td class="text-danger font-weight-bold"><?= $r['alpa'] ?></td><td><?= $r['total_jam'] ?></td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="9" class="text-center text-muted">Tidak ada data siswa.</td></tr><?php endif; ?></tbody></table></div></div></div>

</div></div>
<?= $this->endSection() ?>