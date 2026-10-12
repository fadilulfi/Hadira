<?= $this->extend('templates/role_page_layout') ?>

<?= $this->section('content') ?>
<div class="content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-1"><?= esc($title ?? 'Monitoring Absensi Jam Pelajaran') ?></h3>
                <p class="text-muted mb-0">Pilih daftar hadir yang sudah dibuat guru. Perubahan tersimpan langsung ke database dan tercatat di Audit Log.</p>
            </div>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-warning"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="get" action="<?= base_url('class-attendance') ?>" class="row align-items-end">
                    <div class="col-md-3 mb-2">
                        <label for="tanggal">Tanggal absensi</label>
                        <input id="tanggal" name="tanggal" type="date" class="form-control" max="<?= esc(date('Y-m-d')) ?>" value="<?= esc($date) ?>" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="kelas">Kelas</label>
                        <select id="kelas" name="kelas" class="form-control">
                            <option value="0">Semua kelas</option>
                            <?php foreach ($classes as $class): ?>
                                <?php $classLabel = trim($class['tingkat'] . ' ' . ($class['jurusan'] ?? '') . ' ' . $class['index_kelas']); ?>
                                <option value="<?= (int) $class['id_kelas'] ?>" <?= $classId === (int) $class['id_kelas'] ? 'selected' : '' ?>>
                                    <?= esc($classLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="guru">Guru pengisi</label>
                        <select id="guru" name="guru" class="form-control">
                            <option value="0">Semua guru</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= (int) $teacher['id_guru'] ?>" <?= $teacherId === (int) $teacher['id_guru'] ? 'selected' : '' ?>>
                                    <?= esc($teacher['nama_guru']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button type="submit" class="btn btn-primary">Tampilkan daftar hadir</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header card-header-primary">
                <h4 class="card-title mb-0">Daftar hadir tersimpan · <?= esc($date) ?></h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="text-primary">
                            <tr>
                                <th>Jam pelajaran</th>
                                <th>Mata pelajaran</th>
                                <th>Guru</th>
                                <th>Kelas</th>
                                <th>Terisi</th>
                                <th style="width: 130px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td>
                                        <strong><?= esc($row['nama_jam']) ?></strong><br>
                                        <small class="text-muted"><?= esc(substr($row['jam_mulai'], 0, 5)) ?>–<?= esc(substr($row['jam_selesai'], 0, 5)) ?></small>
                                    </td>
                                    <td><?= esc($row['nama_mapel']) ?></td>
                                    <td><?= esc($row['nama_guru']) ?></td>
                                    <td><?= esc($row['kelas_label']) ?></td>
                                    <td><?= (int) $row['terisi'] ?> / <?= (int) $row['total_siswa'] ?> siswa</td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= base_url('class-attendance/edit/' . (int) $row['id_jadwal'] . '?tanggal=' . rawurlencode($date)) ?>">
                                            <i class="material-icons">fact_check</i> Lihat / Koreksi
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($rows === []): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada daftar hadir jam pelajaran tersimpan untuk tanggal dan filter ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
