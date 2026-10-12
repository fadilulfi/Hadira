<?= $this->extend('templates/role_page_layout') ?>
<?= $this->section('content') ?>
<div class="content">
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
      <div>
        <h3 class="mb-0">Jadwal Pelajaran</h3>
        <small class="text-muted">Atur durasi jadwal 1, 2, atau 3 jam pelajaran berturut-turut.</small>
      </div>
    </div>

    <style>
      .table-responsive { overflow-x: auto; }
      .jp-preview { border-left: 4px solid #26a69a; background: #f5f9f8; padding: 10px 12px; border-radius: 4px; min-height: 42px; }
      .jp-preview.invalid { border-left-color: #e53935; background: #fff6f6; color: #b71c1c; }
      @media print {
        .no-print { display: none !important; }
        .sidebar, .navbar, .footer { display: none !important; }
        .main-panel { width: 100% !important; }
        .content { margin: 0 !important; }
      }
    </style>

    <?php if (session('success')): ?>
      <div class="alert alert-success no-print"><?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
      <div class="alert alert-danger no-print"><?= esc(session('error')) ?></div>
    <?php endif; ?>

    <div class="card no-print">
      <div class="card-header">
        <h4 class="card-title"><?= $editRow ? 'Edit Jadwal' : 'Tambah Jadwal' ?></h4>
      </div>
      <div class="card-body">
        <?php if (empty($jams)): ?>
          <div class="alert alert-warning">
            Belum ada jam pelajaran aktif. Buat jam satuan dan atur urutannya terlebih dahulu di menu Jam Pelajaran.
          </div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('waka/jadwal/simpan') ?>" id="scheduleForm">
          <input type="hidden" name="id_jadwal" value="<?= esc($editRow['id_jadwal'] ?? '') ?>">
          <?= csrf_field() ?>

          <div class="row">
            <div class="col-md-2 form-group">
              <label for="scheduleDay">Hari</label>
              <select class="form-control" name="hari" id="scheduleDay" required>
                <?php foreach ([1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'] as $i => $n): ?>
                  <option value="<?= $i ?>" <?= (int) ($editRow['hari'] ?? 1) === $i ? 'selected' : '' ?>><?= $n ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3 form-group">
              <label for="scheduleTeacher">Guru</label>
              <select class="form-control" name="id_guru" id="scheduleTeacher" required>
                <option value="">Pilih Guru</option>
                <?php foreach ($teachers as $g): ?>
                  <option value="<?= $g['id_guru'] ?>" <?= (int) ($editRow['id_guru'] ?? 0) === (int) $g['id_guru'] ? 'selected' : '' ?>>
                    <?= esc($g['nama_guru']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-2 form-group">
              <label for="scheduleMapel">Mata Pelajaran</label>
              <select class="form-control" name="id_mapel" id="scheduleMapel" required>
                <option value="">Pilih Mapel</option>
                <?php foreach ($mapels as $m): ?>
                  <option value="<?= $m['id_mapel'] ?>" <?= (int) ($editRow['id_mapel'] ?? 0) === (int) $m['id_mapel'] ? 'selected' : '' ?>>
                    <?= esc($m['nama_mapel']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-2 form-group">
              <label for="scheduleClass">Kelas</label>
              <select class="form-control" name="id_kelas" id="scheduleClass" required>
                <option value="">Pilih Kelas</option>
                <?php foreach ($classes as $c): ?>
                  <option value="<?= $c['id_kelas'] ?>" <?= (int) ($editRow['id_kelas'] ?? 0) === (int) $c['id_kelas'] ? 'selected' : '' ?>>
                    <?= esc(trim($c['tingkat'] . ' ' . $c['jurusan'] . ' ' . $c['index_kelas'])) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3 form-group">
              <label for="startPeriod">Jam Mulai</label>
              <select class="form-control" name="id_jam_mulai" id="startPeriod" required>
                <option value="">Pilih JP awal</option>
                <?php foreach ($jams as $j): ?>
                  <option
                    value="<?= $j['id_jam'] ?>"
                    data-order="<?= (int) $j['urutan'] ?>"
                    data-start="<?= esc($j['jam_mulai']) ?>"
                    data-end="<?= esc($j['jam_selesai']) ?>"
                    data-label="<?= esc($j['nama_jam']) ?>"
                    <?= (int) ($editStartId ?? 0) === (int) $j['id_jam'] ? 'selected' : '' ?>>
                    JP <?= (int) $j['urutan'] ?> — <?= esc($j['nama_jam']) ?> (<?= esc(substr($j['jam_mulai'], 0, 5)) ?>–<?= esc(substr($j['jam_selesai'], 0, 5)) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row mt-2">
            <div class="col-md-3 form-group">
              <label for="durationPeriods">Durasi</label>
              <select class="form-control" name="durasi_jp" id="durationPeriods" required>
                <option value="1" <?= (int) ($editDuration ?? 1) === 1 ? 'selected' : '' ?>>1 JP</option>
                <option value="2" <?= (int) ($editDuration ?? 1) === 2 ? 'selected' : '' ?>>2 JP berturut-turut</option>
                <option value="3" <?= (int) ($editDuration ?? 1) === 3 ? 'selected' : '' ?>>3 JP berturut-turut</option>
              </select>
            </div>

            <div class="col-md-5 form-group">
              <label>Rentang jadwal</label>
              <div class="jp-preview" id="jpRangePreview" aria-live="polite">Pilih JP awal dan durasi untuk melihat rentang waktu.</div>
            </div>

            <div class="col-md-2 form-group">
              <label for="scheduleActive">Status</label>
              <select class="form-control" name="aktif" id="scheduleActive">
                <option value="1" <?= ($editRow['aktif'] ?? 1) == 1 ? 'selected' : '' ?>>Aktif</option>
                <option value="0" <?= ($editRow['aktif'] ?? 1) == 0 ? 'selected' : '' ?>>Nonaktif</option>
              </select>
            </div>
          </div>

          <div class="row mt-2">
            <div class="col-md-3 form-group">
              <label for="schoolYear">Tahun Ajaran</label>
              <input class="form-control" name="tahun_ajaran" id="schoolYear" value="<?= esc($editRow['tahun_ajaran'] ?? '2026/2027') ?>" required>
            </div>
            <div class="col-md-3 form-group">
              <label for="semester">Semester</label>
              <select class="form-control" name="semester" id="semester">
                <option <?= ($editRow['semester'] ?? 'Ganjil') === 'Ganjil' ? 'selected' : '' ?>>Ganjil</option>
                <option <?= ($editRow['semester'] ?? 'Ganjil') === 'Genap' ? 'selected' : '' ?>>Genap</option>
              </select>
            </div>
            <div class="col-md-3 align-self-end form-group">
              <button class="btn btn-info btn-block" type="submit" <?= empty($jams) ? 'disabled' : '' ?>>Simpan Jadwal</button>
              <?php if ($editRow): ?>
                <a class="btn btn-secondary btn-block" href="<?= base_url('waka/jadwal') ?>">Batal Edit</a>
              <?php endif; ?>
            </div>
          </div>
          <small class="text-muted">Untuk durasi 2–3 JP, HADIRA akan menyimpan blok waktu seperti <b>3-4</b> atau <b>3-5</b> secara otomatis. JP yang digunakan harus berurutan dan tidak boleh bertabrakan dengan jadwal guru atau kelas lain.</small>
        </form>
      </div>
    </div>

    <form class="card no-print mb-3">
      <div class="card-body">
        <div class="row">
          <div class="col-md-2">
            <label>Hari</label>
            <select class="form-control" name="hari">
              <option value="0">Semua</option>
              <?php foreach ([1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'] as $i => $n): ?>
                <option value="<?= $i ?>" <?= $day == $i ? 'selected' : '' ?>><?= $n ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label>Guru</label>
            <select class="form-control" name="guru">
              <option value="0">Semua</option>
              <?php foreach ($teachers as $g): ?>
                <option value="<?= $g['id_guru'] ?>" <?= $teacher == $g['id_guru'] ? 'selected' : '' ?>><?= esc($g['nama_guru']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label>Kelas</label>
            <select class="form-control" name="kelas">
              <option value="0">Semua</option>
              <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id_kelas'] ?>" <?= $class == $c['id_kelas'] ? 'selected' : '' ?>>
                  <?= esc(trim($c['tingkat'] . ' ' . $c['jurusan'] . ' ' . $c['index_kelas'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2 align-self-end"><button class="btn btn-default btn-block">Filter</button></div>
        </div>
      </div>
    </form>

    <div class="card">
      <div class="card-header"><h4 class="card-title">Daftar Jadwal</h4></div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>Hari</th><th>Jam</th><th>Guru</th><th>Mapel</th><th>Kelas</th><th>Tahun/Sem</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
              <?php $hariNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu']; ?>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= $hariNames[(int) $r['hari']] ?? '-' ?></td>
                  <td><strong><?= esc($r['nama_jam']) ?></strong><br><small><?= esc(substr($r['jam_mulai'], 0, 5)) ?>–<?= esc(substr($r['jam_selesai'], 0, 5)) ?></small></td>
                  <td><?= esc($r['nama_guru']) ?></td>
                  <td><?= esc($r['nama_mapel']) ?></td>
                  <td><?= esc(trim($r['tingkat'] . ' ' . $r['jurusan'] . ' ' . $r['index_kelas'])) ?></td>
                  <td><?= esc($r['tahun_ajaran']) ?><br><?= esc($r['semester']) ?></td>
                  <td><span class="badge badge-<?= $r['aktif'] ? 'success' : 'secondary' ?>"><?= $r['aktif'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                  <td>
                    <a class="btn btn-info btn-sm" href="<?= base_url('waka/jadwal?edit=' . $r['id_jadwal']) ?>">Edit</a>
                    <a class="btn btn-sm btn-<?= $r['aktif'] ? 'warning' : 'success' ?>" href="<?= base_url('waka/jadwal/toggle/' . $r['id_jadwal']) ?>"><?= $r['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></a>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted">Belum ada jadwal.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  const startSelect = document.getElementById('startPeriod');
  const durationSelect = document.getElementById('durationPeriods');
  const preview = document.getElementById('jpRangePreview');
  if (!startSelect || !durationSelect || !preview) return;

  const periods = Array.from(startSelect.options)
    .filter(option => option.value)
    .map(option => ({
      id: Number(option.value),
      order: Number(option.dataset.order),
      start: option.dataset.start,
      end: option.dataset.end,
      label: option.dataset.label
    }));
  const periodsByOrder = new Map(periods.map(period => [period.order, period]));

  function renderPreview() {
    const selected = periods.find(period => String(period.id) === startSelect.value);
    const duration = Number(durationSelect.value || 1);

    if (!selected) {
      preview.classList.add('invalid');
      preview.textContent = 'Pilih JP awal terlebih dahulu.';
      return;
    }

    const endPeriod = periodsByOrder.get(selected.order + duration - 1);
    if (!endPeriod) {
      preview.classList.add('invalid');
      preview.textContent = 'JP ' + selected.order + ' sampai ' + (selected.order + duration - 1) + ' belum lengkap. Periksa urutan pada menu Jam Pelajaran.';
      return;
    }

    if (endPeriod.end <= selected.start) {
      preview.classList.add('invalid');
      preview.textContent = 'Rentang waktu tidak valid. Periksa jam mulai dan selesai.';
      return;
    }

    preview.classList.remove('invalid');
    const range = duration === 1
      ? 'JP ' + selected.order
      : 'JP ' + selected.order + '–' + endPeriod.order + ' (' + duration + ' JP)';
    preview.textContent = range + ' · ' + selected.start.slice(0, 5) + '–' + endPeriod.end.slice(0, 5);
  }

  startSelect.addEventListener('change', renderPreview);
  durationSelect.addEventListener('change', renderPreview);
  renderPreview();
})();
</script>
<?= $this->endSection() ?>
