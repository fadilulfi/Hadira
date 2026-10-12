<?= $this->extend('templates/role_page_layout') ?>

<?= $this->section('content') ?>
<div class="content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <div>
                <h3 class="mb-1">Koreksi Daftar Hadir</h3>
                <p class="text-muted mb-0"><?= esc($schedule['nama_mapel']) ?> · <?= esc($schedule['nama_kelas']) ?> · <?= esc($date) ?></p>
                <small class="text-muted">Guru pengisi: <?= esc($schedule['nama_guru']) ?> · <?= esc($schedule['nama_jam']) ?> (<?= esc(substr($schedule['jam_mulai'], 0, 5)) ?>–<?= esc(substr($schedule['jam_selesai'], 0, 5)) ?>)</small>
            </div>
            <?php
                $backUrl = match ($source ?? 'admin') {
                    'bk' => base_url('class-attendance?source=bk&tanggal=' . rawurlencode($date)),
                    'waka' => base_url('waka/monitoring?tanggal=' . rawurlencode($date)),
                    default => base_url('class-attendance?source=admin&tanggal=' . rawurlencode($date)),
                };
            ?>
            <a href="<?= esc($backUrl) ?>" class="btn btn-outline-secondary mt-2">Kembali ke monitoring</a>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-warning"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <div class="alert alert-info">
            Daftar ini sudah tersimpan dari pengisian guru. Pilih status baru, lalu tekan <strong>Simpan Koreksi</strong>. Perubahan akan langsung tersimpan dan dicatat di Audit Log.
        </div>

        <div class="card">
            <div class="card-header card-header-primary d-flex justify-content-between align-items-center flex-wrap">
                <h4 class="card-title mb-0">Daftar Siswa</h4>
                <span class="text-white"><?= count($attendanceMap) ?> catatan sudah tersimpan</span>
            </div>
            <div class="card-body">
                <form method="post" action="<?= base_url('class-attendance/save') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_jadwal" value="<?= (int) $schedule['id_jadwal'] ?>">
                    <input type="hidden" name="tanggal" value="<?= esc($date) ?>">
                    <input type="hidden" name="source" value="<?= esc($source ?? 'admin') ?>">

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="text-primary">
                                <tr>
                                    <th style="width: 60px;">No.</th>
                                    <th>NIS</th>
                                    <th>Nama siswa</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                    <th>Waktu absen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $i => $student): ?>
                                    <?php
                                        $studentId = (int) $student['id_siswa'];
                                        $saved = $attendanceMap[$studentId] ?? null;
                                        $savedStatus = $saved ? (int) $saved['id_kehadiran'] : 0;
                                    ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= esc($student['nis']) ?></td>
                                        <td><strong><?= esc($student['nama_siswa']) ?></strong></td>
                                        <td style="min-width: 155px;">
                                            <select name="status[<?= $studentId ?>]" class="form-control">
                                                <option value="">-- Tidak diubah --</option>
                                                <?php foreach ($kehadiran as $status): ?>
                                                    <?php
                                                        $statusId = (int) $status['id_kehadiran'];
                                                        $statusLabel = $status['kehadiran'] === 'Tanpa keterangan' ? 'Alpa' : $status['kehadiran'];
                                                    ?>
                                                    <option value="<?= $statusId ?>" <?= $savedStatus === $statusId ? 'selected' : '' ?>>
                                                        <?= esc($statusLabel) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control" name="keterangan[<?= $studentId ?>]" value="<?= esc($saved['keterangan'] ?? '') ?>" placeholder="Opsional"></td>
                                        <td><?= $saved ? esc($saved['waktu_absen'] ?? '-') : '<span class="text-muted">Belum diisi</span>' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($students === []): ?>
                                    <tr><td colspan="6" class="text-center text-muted">Tidak ada siswa pada kelas ini.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="material-icons">save</i> Simpan Koreksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
