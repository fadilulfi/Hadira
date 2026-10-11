<?= $this->extend('templates/admin_page_layout') ?>
<?= $this->section('content') ?>
<div class="content"><div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-3 no-print"><div><h3 class="mb-0">Dashboard Waka</h3><small class="text-muted">Panel baru</small></div></div>

<style>
  .stat-card .display-4{font-size:2rem;font-weight:700}
  .table-responsive{overflow-x:auto}
  @media print{.no-print{display:none!important}.sidebar,.navbar,.footer{display:none!important}.main-panel{width:100%!important}.content{margin:0!important}}
</style>


<div class="row"><div class="col-md-3"><div class="card"><div class="card-body text-center"><div class="text-muted">Jadwal Aktif</div><h2><?= $scheduleCount ?></h2></div></div></div><div class="col-md-3"><div class="card"><div class="card-body text-center"><div class="text-muted">Guru</div><h2><?= $teacherCount ?></h2></div></div></div><div class="col-md-3"><div class="card"><div class="card-body text-center"><div class="text-muted">Kelas</div><h2><?= $classCount ?></h2></div></div></div><div class="col-md-3"><div class="card"><div class="card-body text-center"><div class="text-muted">Sesi Terisi Hari Ini</div><h2><?= $done ?>/<?= count($todaySchedules) ?></h2></div></div></div></div>
<div class="card"><div class="card-header"><h4 class="card-title">Jadwal Hari Ini</h4></div><div class="card-body"><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Jam</th><th>Mapel</th><th>Guru</th><th>Kelas</th><th>Progress</th></tr></thead><tbody><?php foreach($todaySchedules as $r): ?><tr><td><?= esc($r['nama_jam']) ?><br><small><?= esc($r['jam_mulai']) ?> - <?= esc($r['jam_selesai']) ?></small></td><td><?= esc($r['nama_mapel']) ?></td><td><?= esc($r['nama_guru']) ?></td><td><?= esc($r['kelas_label']) ?></td><td><span class="badge badge-<?= $r['absen_count']>0?'success':'secondary' ?>"><?= $r['absen_count'] ?>/<?= $r['total_siswa'] ?></span></td></tr><?php endforeach; ?><?php if(!$todaySchedules): ?><tr><td colspan="5" class="text-center text-muted">Belum ada jadwal.</td></tr><?php endif; ?></tbody></table></div></div></div>

</div></div>
<?= $this->endSection() ?>