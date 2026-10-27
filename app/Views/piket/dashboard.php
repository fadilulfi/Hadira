<?= $this->extend('templates/role_page_layout') ?>
<?= $this->section('content') ?>
<div class="content"><div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h3 class="mb-0">Dashboard Piket</h3><small class="text-muted">Proses pengajuan izin dan sakit siswa.</small></div>
    </div>
    <?php if (session('success')): ?><div class="alert alert-success"><?= esc(session('success')) ?></div><?php endif; ?>
    <?php if (session('error')): ?><div class="alert alert-danger"><?= esc(session('error')) ?></div><?php endif; ?>
    <div class="mb-3">
        <?php foreach (['Pending', 'Disetujui', 'Ditolak'] as $item): ?>
            <a class="btn btn-sm <?= $status === $item ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= base_url('piket/dashboard?status=' . $item) ?>"><?= esc($item) ?></a>
        <?php endforeach; ?>
    </div>
    <div class="card"><div class="card-body table-responsive">
        <table class="table table-hover">
            <thead class="text-primary"><tr><th>Siswa</th><th>Kelas</th><th>Tanggal</th><th>Jenis</th><th>Alasan</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted">Tidak ada pengajuan.</td></tr><?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><strong><?= esc($row['nama_siswa']) ?></strong><br><small><?= esc($row['nis']) ?></small></td>
                    <td><?= esc(trim(($row['tingkat'] ?? '') . ' ' . ($row['jurusan'] ?? '') . ' ' . ($row['index_kelas'] ?? ''))) ?></td>
                    <td><?= esc($row['tanggal_mulai']) ?><?= $row['tanggal_selesai'] !== $row['tanggal_mulai'] ? ' s/d ' . esc($row['tanggal_selesai']) : '' ?></td>
                    <td><?= esc($row['tipe_izin']) ?></td><td><?= esc($row['alasan']) ?></td>
                    <td><span class="badge badge-<?= $row['status'] === 'Pending' ? 'warning' : ($row['status'] === 'Disetujui' ? 'success' : 'danger') ?>"><?= esc($row['status']) ?></span></td>
                    <td><?php if ($row['status'] === 'Pending'): ?>
                        <form method="post" action="<?= base_url('piket/perizinan/status') ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id_perizinan" value="<?= esc($row['id_perizinan']) ?>"><input type="hidden" name="status" value="Disetujui"><button class="btn btn-success btn-sm">Setujui</button></form>
                        <form method="post" action="<?= base_url('piket/perizinan/status') ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id_perizinan" value="<?= esc($row['id_perizinan']) ?>"><input type="hidden" name="status" value="Ditolak"><button class="btn btn-danger btn-sm">Tolak</button></form>
                    <?php else: ?>—<?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
</div></div>
<?= $this->endSection() ?>
