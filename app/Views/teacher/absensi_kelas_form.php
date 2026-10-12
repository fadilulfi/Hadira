<?= $this->extend('templates/role_page_layout') ?>

<?= $this->section('styles') ?>
<style>
    .session-card { border-radius: 14px; }
    .student-status select { min-width: 150px; }
    .summary-pill { display: inline-block; padding: 6px 10px; border-radius: 20px; margin-right: 6px; background: #f5f5f5; }
    .permission-hint { font-size: 12px; color: #1565c0; display: block; margin-top: 3px; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-1"><?= esc($schedule['nama_mapel']) ?></h3>
                <p class="text-muted mb-0"><?= esc($schedule['nama_kelas']) ?> · <?= esc($schedule['nama_jam']) ?> · <?= esc($tanggal) ?></p>
            </div>
            <a href="<?= base_url('teacher/absensi-kelas') ?>" class="btn btn-outline-secondary">Kembali</a>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        <?php if (!empty($attendanceMap)): ?>
            <div class="alert alert-info">
                Absensi untuk jam pelajaran ini sudah tersimpan. Perubahan berikutnya langsung memperbarui data dan dapat dilakukan kembali sampai hari ini berakhir.
            </div>
        <?php endif; ?>

        <div class="card session-card">
            <div class="card-header card-header-primary d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-0">Daftar Siswa</h4>
                    <small><?= esc(substr($schedule['jam_mulai'], 0, 5)) ?> – <?= esc(substr($schedule['jam_selesai'], 0, 5)) ?></small>
                </div>
                <button type="button" class="btn btn-white btn-sm" id="hadirSemua">Hadir Semua</button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="summary-pill">Hadir: <b id="countHadir">0</b></span>
                    <span class="summary-pill">Izin: <b id="countIzin">0</b></span>
                    <span class="summary-pill">Sakit: <b id="countSakit">0</b></span>
                    <span class="summary-pill">Alpa: <b id="countAlpa">0</b></span>
                </div>

                <form method="post" action="<?= base_url('teacher/absensi-kelas/simpan') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_jadwal" value="<?= esc($schedule['id_jadwal']) ?>">

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="text-primary">
                                <tr>
                                    <th style="width: 60px;">No</th>
                                    <th>NIS</th>
                                    <th>Nama Siswa</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($students as $i => $student):
                                $sid = (int) $student['id_siswa'];
                                $saved = $attendanceMap[$sid] ?? null;
                                $permission = $izinMap[$sid] ?? null;
                                $defaultStatus = $saved['id_kehadiran'] ?? '';
                                if (!$saved && $permission) {
                                    $defaultStatus = $permission['tipe_izin'] === 'Sakit' ? 2 : 3;
                                }
                                $defaultNote = $saved['keterangan'] ?? ($permission['alasan'] ?? '');
                            ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= esc($student['nis']) ?></td>
                                    <td>
                                        <strong><?= esc($student['nama_siswa']) ?></strong>
                                        <?php if ($permission): ?>
                                            <span class="permission-hint">Pengajuan <?= esc($permission['tipe_izin']) ?> disetujui</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="student-status">
                                        <select class="form-control status-select" name="status[<?= $sid ?>]">
                                            <option value="">-- Pilih --</option>
                                            <?php foreach ($kehadiran as $status): ?>
                                                <?php
                                                    $statusId = (int) $status['id_kehadiran'];
                                                    $label = $status['kehadiran'] === 'Tanpa keterangan' ? 'Alpa' : $status['kehadiran'];
                                                ?>
                                                <option value="<?= $statusId ?>" <?= $defaultStatus === $statusId ? 'selected' : '' ?>><?= esc($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="keterangan[<?= $sid ?>]" value="<?= esc($defaultNote) ?>" placeholder="Opsional">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-right mt-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="material-icons">save</i> <?= !empty($attendanceMap) ? 'Simpan Perubahan' : 'Simpan Absensi' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const selects = () => Array.from(document.querySelectorAll('.status-select'));

    function updateCounts() {
        const counts = {1: 0, 2: 0, 3: 0, 4: 0};
        selects().forEach(s => {
            if (counts[s.value] !== undefined) counts[s.value]++;
        });
        document.getElementById('countHadir').textContent = counts[1];
        document.getElementById('countSakit').textContent = counts[2];
        document.getElementById('countIzin').textContent = counts[3];
        document.getElementById('countAlpa').textContent = counts[4];
    }

    document.getElementById('hadirSemua').addEventListener('click', function () {
        selects().forEach(s => s.value = '1');
        updateCounts();
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('status-select')) updateCounts();
    });

    updateCounts();
})();
</script>
<?= $this->endSection() ?>
