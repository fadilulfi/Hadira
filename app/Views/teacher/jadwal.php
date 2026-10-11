<?= $this->extend('templates/role_page_layout') ?>
<?= $this->section('content') ?>
<div class="content"><div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-3 no-print"><div><h3 class="mb-0">Jadwal Saya</h3><small class="text-muted">Panel baru</small></div></div>

<style>
  .stat-card .display-4{font-size:2rem;font-weight:700}
  .table-responsive{overflow-x:auto}
  @media print{.no-print{display:none!important}.sidebar,.navbar,.footer{display:none!important}.main-panel{width:100%!important}.content{margin:0!important}}
</style>


<div class="card mb-4"><div class="card-header card-header-success"><h4 class="card-title">Jadwal Hari Ini</h4><p class="card-category">Jadwal berdasarkan akun guru yang terhubung.</p></div><div class="card-body"><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Jam</th><th>Mata Pelajaran</th><th>Kelas</th><th>Tahun Ajaran</th></tr></thead><tbody>
<?php if (!$todayRows): ?><tr><td colspan="4" class="text-center text-muted">Tidak ada jadwal hari ini.</td></tr><?php endif; ?>
<?php foreach ($todayRows as $r): ?><tr><td><b><?= esc($r['nama_jam']) ?></b><br><small><?= esc($r['jam_mulai']) ?> - <?= esc($r['jam_selesai']) ?></small></td><td><?= esc($r['nama_mapel']) ?></td><td><?= esc(trim(($r['tingkat']??'').' '.($r['jurusan']??'').' '.($r['index_kelas']??''))) ?></td><td><?= esc($r['tahun_ajaran']) ?> / <?= esc($r['semester']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div></div>
<div class="card"><div class="card-header"><h4 class="card-title">Jadwal Mingguan</h4></div><div class="card-body"><div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Hari</th><th>Jam</th><th>Mapel</th><th>Kelas</th></tr></thead><tbody>
<?php $hariNames=[1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu']; foreach($allRows as $r): ?><tr><td><?= $hariNames[(int)$r['hari']] ?? '-' ?></td><td><?= esc($r['nama_jam']) ?></td><td><?= esc($r['nama_mapel']) ?></td><td><?= esc(trim(($r['tingkat']??'').' '.($r['jurusan']??'').' '.($r['index_kelas']??''))) ?></td></tr><?php endforeach; ?>
<?php if (!$allRows): ?><tr><td colspan="4" class="text-center text-muted">Belum ada jadwal mengajar.</td></tr><?php endif; ?></tbody></table></div></div></div>

</div></div>
<?= $this->endSection() ?>