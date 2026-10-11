<?= $this->extend('templates/role_page_layout') ?>
<?= $this->section('content') ?>
<div class="content"><div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-3 no-print"><div><h3 class="mb-0">Mata Pelajaran</h3><small class="text-muted">Panel baru</small></div></div>

<style>
  .stat-card .display-4{font-size:2rem;font-weight:700}
  .table-responsive{overflow-x:auto}
  @media print{.no-print{display:none!important}.sidebar,.navbar,.footer{display:none!important}.main-panel{width:100%!important}.content{margin:0!important}}
</style>


<?php if(session('success')): ?><div class="alert alert-success no-print"><?= esc(session('success')) ?></div><?php endif; ?><?php if(session('error')): ?><div class="alert alert-danger no-print"><?= esc(session('error')) ?></div><?php endif; ?>
<div class="row"><div class="col-md-4"><div class="card"><div class="card-header"><h4 class="card-title"><?= $editRow ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran' ?></h4></div><div class="card-body"><form method="post" action="<?= base_url('waka/mapel/simpan') ?>"><?= csrf_field() ?><input type="hidden" name="id_mapel" value="<?= esc($editRow['id_mapel'] ?? "") ?>"><div class="form-group"><label>Kode</label><input class="form-control" name="kode_mapel" value="<?= esc($editRow['kode_mapel'] ?? "") ?>"></div><div class="form-group"><label>Nama Mata Pelajaran</label><input class="form-control" name="nama_mapel" value="<?= esc($editRow['nama_mapel'] ?? "") ?>" required></div><div class="form-group"><label>Status</label><select class="form-control" name="aktif"><option value="1" <?= ($editRow['aktif'] ?? 1) == 1 ? 'selected' : '' ?>>Aktif</option><option value="0" <?= ($editRow['aktif'] ?? 1) == 0 ? 'selected' : '' ?>>Nonaktif</option></select></div><button class="btn btn-info btn-block">Simpan</button></form><div class="mt-2 no-print"><?php if($editRow): ?><a class="btn btn-secondary btn-sm" href="<?= base_url('waka/mapel') ?>">Batal Edit</a><?php endif; ?></div></div></div></div><div class="col-md-8"><div class="card"><div class="card-header"><h4 class="card-title">Daftar Mata Pelajaran</h4></div><div class="card-body"><div class="table-responsive"><table class="table"><thead><tr><th>Kode</th><th>Nama</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?= esc($r['kode_mapel'] ?: '-') ?></td><td><?= esc($r['nama_mapel']) ?></td><td><span class="badge badge-<?= $r['aktif']?'success':'secondary' ?>"><?= $r['aktif']?'Aktif':'Nonaktif' ?></span></td><td><a class="btn btn-info btn-sm" href="<?= base_url('waka/mapel?edit='.$r['id_mapel']) ?>">Edit</a> <a class="btn btn-sm btn-<?= $r['aktif']?'warning':'success' ?>" href="<?= base_url('waka/mapel/toggle/'.$r['id_mapel']) ?>"><?= $r['aktif']?'Nonaktifkan':'Aktifkan' ?></a></td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="4" class="text-center text-muted">Belum ada mata pelajaran.</td></tr><?php endif; ?></tbody></table></div></div></div></div></div>

</div></div>
<?= $this->endSection() ?>