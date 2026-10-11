<?= $this->extend('templates/role_page_layout') ?>
<?= $this->section('content') ?>
<div class="content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Data Izin &amp; Sakit</h3>
                <small class="text-muted">Monitoring dan tindak lanjut perizinan siswa.</small>
            </div>
        </div>

        <?php if (session('success')): ?><div class="alert alert-success"><?= esc(session('success')) ?></div><?php endif; ?>
        <?php if (session('error')): ?><div class="alert alert-danger"><?= esc(session('error')) ?></div><?php endif; ?>

        <form class="card mb-3" method="get">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label>Status</label>
                        <select class="form-control" name="status">
                            <option value="">Semua Status</option>
                            <?php foreach (['Pending','Disetujui','Ditolak'] as $st): ?>
                                <option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Tipe</label>
                        <select class="form-control" name="tipe">
                            <option value="">Semua</option>
                            <option value="Sakit" <?= $type === 'Sakit' ? 'selected' : '' ?>>Sakit</option>
                            <option value="Izin" <?= $type === 'Izin' ? 'selected' : '' ?>>Izin</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Tanggal</label>
                        <input type="date" class="form-control" name="tanggal" value="<?= esc($date) ?>">
                    </div>
                    <div class="col-md-3 align-self-end">
                        <button class="btn btn-info btn-block">Filter</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Daftar Pengajuan</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Kelas</th>
                            <th>Tipe</th>
                            <th>Periode</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th style="min-width:180px">Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data perizinan.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><strong><?= esc($r['nama_siswa']) ?></strong><br><small><?= esc($r['nis']) ?></small></td>
                                <td><?= esc(trim(($r['tingkat'] ?? '') . ' ' . ($r['jurusan'] ?? '') . ' ' . ($r['index_kelas'] ?? ''))) ?></td>
                                <td><span class="badge badge-info"><?= esc($r['tipe_izin']) ?></span></td>
                                <td><?= esc($r['tanggal_mulai']) ?><br><?= esc($r['tanggal_selesai']) ?></td>
                                <td><?= esc($r['alasan'] ?: '-') ?></td>
                                <td><span class="badge badge-<?= $r['status'] === 'Disetujui' ? 'success' : ($r['status'] === 'Ditolak' ? 'danger' : 'warning') ?>"><?= esc($r['status']) ?></span></td>
                                <td>
                                    <?php if ($r['status'] === 'Pending'): ?>
                                        <div class="d-flex">
                                            <form method="post" action="<?= base_url('waka/perizinan/status') ?>" class="mr-1">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id_perizinan" value="<?= esc($r['id_perizinan']) ?>">
                                                <input type="hidden" name="status" value="Disetujui">
                                                <button class="btn btn-success btn-sm">Setujui</button>
                                            </form>
                                            <form method="post" action="<?= base_url('waka/perizinan/status') ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id_perizinan" value="<?= esc($r['id_perizinan']) ?>">
                                                <input type="hidden" name="status" value="Ditolak">
                                                <button class="btn btn-danger btn-sm">Tolak</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Selesai</span>
                                    <?php endif; ?>
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
