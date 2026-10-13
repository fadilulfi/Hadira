<?= $this->extend('templates/role_page_layout') ?>
<?= $this->section('content') ?>
<div class="content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <div>
                <h3 class="mb-1"><?= esc($title ?? 'Data Referensi') ?></h3>
                <p class="text-muted mb-0">
                    Data referensi untuk monitoring. Perubahan data induk hanya dilakukan melalui modul administrasi.
                </p>
            </div>
            <span class="badge badge-info mt-2">Baca saja</span>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="get" action="<?= esc($listUrl) ?>" class="row align-items-end mb-3">
                    <div class="col-md-8 col-lg-6">
                        <label for="directory-search">Cari <?= ($directoryType ?? '') === 'guru' ? 'guru' : 'siswa' ?> berdasarkan nama atau identitas</label>
                        <input
                            id="directory-search"
                            type="search"
                            name="q"
                            class="form-control"
                            value="<?= esc($keyword ?? '') ?>"
                            placeholder="<?= ($directoryType ?? '') === 'guru' ? 'Nama guru atau NUPTK' : 'Nama siswa, NIS, atau kelas' ?>"
                        >
                    </div>
                    <div class="col-md-4 col-lg-3 mt-2 mt-md-0">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        <a href="<?= esc($listUrl) ?>" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <?php if (($directoryType ?? '') === 'guru'): ?>
                            <thead class="text-primary">
                                <tr>
                                    <th style="width: 60px;">No.</th>
                                    <th>NUPTK</th>
                                    <th>Nama Guru</th>
                                    <th>Jenis Kelamin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $i => $row): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= esc($row['nuptk'] ?? '-') ?></td>
                                        <td><?= esc($row['nama_guru'] ?? '-') ?></td>
                                        <td><?= esc($row['jenis_kelamin'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php else: ?>
                            <thead class="text-primary">
                                <tr>
                                    <th style="width: 60px;">No.</th>
                                    <th>NIS</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Jenis Kelamin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $i => $row): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= esc($row['nis'] ?? '-') ?></td>
                                        <td><?= esc($row['nama_siswa'] ?? '-') ?></td>
                                        <td><?= esc($row['kelas_label'] ?? '-') ?></td>
                                        <td><?= esc($row['jenis_kelamin'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php endif; ?>

                        <?php if ($rows === []): ?>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Data tidak ditemukan atau belum tersedia.
                                    </td>
                                </tr>
                            </tbody>
                        <?php endif; ?>
                    </table>
                </div>
                <small class="text-muted">Jumlah data ditampilkan: <?= count($rows) ?></small>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
