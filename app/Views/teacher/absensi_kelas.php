<?= $this->extend('templates/role_page_layout') ?>

<?= $this->section('styles') ?>
<style>
    .schedule-card { border-radius: 14px; }
    .schedule-card .card-body { padding: 1.5rem; }
    .schedule-title { font-weight: 700; margin-bottom: .25rem; }
    .schedule-meta { color: #777; }
    .live-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; background: #4caf50; margin-right: 6px; }
    .schedule-time { font-size: 1.25rem; font-weight: 700; }
    .today-row { border-bottom: 1px solid #eee; padding: 12px 0; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-12">
                <h3 class="mb-1">Absensi Kelas</h3>
                <p class="text-muted mb-0">Jadwal ditentukan oleh sekolah. Anda hanya mengisi absensi pada kelas yang sedang Anda ajar.</p>
            </div>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card schedule-card">
                    <div class="card-header card-header-primary">
                        <h4 class="card-title mb-0"><span class="live-dot"></span>Jadwal Saat Ini</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($current): ?>
                            <div class="schedule-title h4"><?= esc($current['nama_mapel']) ?></div>
                            <div class="schedule-meta mb-3"><?= esc($current['nama_kelas']) ?> · <?= esc($current['nama_jam']) ?></div>
                            <div class="schedule-time mb-3"><?= esc(substr($current['jam_mulai'], 0, 5)) ?> – <?= esc(substr($current['jam_selesai'], 0, 5)) ?></div>
                            <a class="btn btn-primary btn-lg" href="<?= base_url('teacher/absensi-kelas/' . $current['id_jadwal']) ?>">
                                <i class="material-icons">assignment_turned_in</i> Mulai / Lanjutkan Absensi
                            </a>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="material-icons" style="font-size: 54px; color:#999;">schedule</i>
                                <h4 class="mt-2">Tidak ada jadwal yang sedang berlangsung</h4>
                                <?php if ($next): ?>
                                    <p class="text-muted mb-1">Jadwal berikutnya:</p>
                                    <strong><?= esc($next['nama_mapel']) ?> — <?= esc($next['nama_kelas']) ?></strong>
                                    <div class="text-muted"><?= esc(substr($next['jam_mulai'], 0, 5)) ?> – <?= esc(substr($next['jam_selesai'], 0, 5)) ?></div>
                                <?php else: ?>
                                    <p class="text-muted">Tidak ada jadwal berikutnya hari ini.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card schedule-card">
                    <div class="card-header card-header-info">
                        <h4 class="card-title mb-0">Jadwal Hari Ini</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($todaySchedules)): ?>
                            <p class="text-muted mb-0">Belum ada jadwal.</p>
                        <?php else: ?>
                            <?php foreach ($todaySchedules as $item): ?>
                                <div class="today-row">
                                    <strong><?= esc($item['nama_mapel']) ?></strong><br>
                                    <small><?= esc($item['nama_kelas']) ?></small><br>
                                    <small class="text-muted"><?= esc($item['nama_jam']) ?> · <?= esc(substr($item['jam_mulai'], 0, 5)) ?>–<?= esc(substr($item['jam_selesai'], 0, 5)) ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
