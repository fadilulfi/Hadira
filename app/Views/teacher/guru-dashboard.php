<?= $this->extend('templates/role_page_layout') ?>
<?= $this->section('content') ?>
<div class="content">
    <div class="container-fluid">
        <?php if (session('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= esc(session('success')) ?>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        <?php endif; ?>
        <?php if (session('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= esc(session('error')) ?>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        <?php endif; ?>

        <div class="row align-items-center mb-3">
            <div class="col-md-8">
                <h2 class="mb-1">Dashboard Guru</h2>
                <p class="text-muted mb-0">
                    <?= esc($todayName) ?>, <?= esc($date) ?> · <?= esc($guruLabel) ?>
                </p>
            </div>
            <div class="col-md-4 text-md-right mt-2 mt-md-0">
                <a href="<?= base_url('teacher/absensi-kelas') ?>" class="btn btn-success">
                    <i class="material-icons align-middle mr-1">fact_check</i> Absensi Jam Pelajaran
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-success card-header-icon">
                        <div class="card-icon"><i class="material-icons">calendar_month</i></div>
                        <p class="card-category">Jadwal Aktif</p>
                        <h3 class="card-title"><?= esc($scheduleCount) ?></h3>
                    </div>
                    <div class="card-footer"><div class="stats">Total jadwal mengajar</div></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-info card-header-icon">
                        <div class="card-icon"><i class="material-icons">today</i></div>
                        <p class="card-category">Hari Ini</p>
                        <h3 class="card-title"><?= esc($todayCount) ?></h3>
                    </div>
                    <div class="card-footer"><div class="stats">Jam mengajar hari ini</div></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-warning card-header-icon">
                        <div class="card-icon"><i class="material-icons">fact_check</i></div>
                        <p class="card-category">Sudah Diisi</p>
                        <h3 class="card-title"><?= esc($completed) ?></h3>
                    </div>
                    <div class="card-footer"><div class="stats">Sesi yang sudah terisi</div></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-header card-header-danger card-header-icon">
                        <div class="card-icon"><i class="material-icons">schedule</i></div>
                        <p class="card-category">Jam Sekarang</p>
                        <h3 class="card-title"><?= esc(substr($now, 0, 5)) ?></h3>
                    </div>
                    <div class="card-footer"><div class="stats">Waktu server aplikasi</div></div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header card-header-success">
                <h4 class="card-title">Jadwal Saat Ini</h4>
                <p class="card-category mb-0">Sistem membaca jadwal berdasarkan hari dan jam pelajaran.</p>
            </div>
            <div class="card-body">
                <?php if ($current): ?>
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="mb-1"><?= esc($current['nama_mapel']) ?></h3>
                            <p class="mb-1"><strong><?= esc($current['kelas_label']) ?></strong></p>
                            <p class="text-muted mb-0">
                                <?= esc($current['nama_jam']) ?> · <?= esc($current['jam_mulai']) ?> - <?= esc($current['jam_selesai']) ?>
                                · <?= esc($current['terisi']) ?>/<?= esc($current['total_siswa']) ?> siswa
                            </p>
                        </div>
                        <div class="col-md-4 text-md-right mt-3 mt-md-0">
                            <a href="<?= base_url('teacher/absensi-kelas/' . $current['id_jadwal']) ?>" class="btn btn-success">
                                <i class="material-icons align-middle mr-1">fact_check</i> Buka Absensi
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="material-icons text-muted" style="font-size:48px;">event_available</i>
                        <h4 class="mt-2">Tidak ada jadwal yang sedang berlangsung</h4>
                        <?php if ($next): ?>
                            <p class="text-muted mb-1">Jadwal berikutnya: <strong><?= esc($next['nama_mapel']) ?></strong> - <?= esc($next['kelas_label']) ?></p>
                            <small class="text-muted"><?= esc($next['nama_jam']) ?> · <?= esc($next['jam_mulai']) ?> - <?= esc($next['jam_selesai']) ?></small>
                        <?php else: ?>
                            <p class="text-muted mb-0">Tidak ada jadwal berikutnya hari ini.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Jadwal Mengajar Hari Ini</h4>
                <p class="card-category"><?= esc($todayName) ?></p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>Jam</th>
                            <th>Mata Pelajaran</th>
                            <th>Kelas</th>
                            <th>Absensi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$todayRows): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada jadwal mengajar hari ini.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($todayRows as $row): ?>
                            <tr <?= !empty($row['is_current']) ? 'class="table-success"' : '' ?>>
                                <td>
                                    <strong><?= esc($row['nama_jam']) ?></strong><br>
                                    <small><?= esc($row['jam_mulai']) ?> - <?= esc($row['jam_selesai']) ?></small>
                                </td>
                                <td><?= esc($row['nama_mapel']) ?></td>
                                <td><?= esc($row['kelas_label']) ?></td>
                                <td><?= esc($row['terisi']) ?>/<?= esc($row['total_siswa']) ?></td>
                                <td>
                                    <?php if (!empty($row['is_current'])): ?>
                                        <span class="badge badge-success">Sedang Berlangsung</span>
                                    <?php elseif (($row['terisi'] ?? 0) > 0): ?>
                                        <span class="badge badge-info">Sudah Diisi</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Belum Diisi</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('teacher/absensi-kelas/' . $row['id_jadwal']) ?>" class="btn btn-sm btn-success">Absensi</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
