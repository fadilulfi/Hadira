<?= $this->extend('templates/admin_page_layout') ?>
<?= $this->section('content') ?>
<div class="content">
   <div class="container-fluid">
      <?php $errors = $accountErrors ?? []; ?>
      <?php if (isset($validation) && $validation && $validation->getErrors()): ?>
         <?php $errors = array_merge($validation->getErrors(), $errors); ?>
      <?php endif; ?>
      <?php if (session()->getFlashdata('errors')): $flashErrors = session()->getFlashdata('errors'); ?>
         <?php $errors = array_merge(is_array($flashErrors) ? $flashErrors : [], $errors); ?>
      <?php endif; ?>

      <?php if ($errors !== []): ?>
         <div class="alert alert-danger">
            <ul class="mb-0">
               <?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?>
            </ul>
         </div>
      <?php endif; ?>

      <?php $account = $account ?? null; ?>
      <div class="card">
         <div class="card-header card-header-success">
            <h4 class="card-title"><b>Form Edit Guru</b></h4>
            <p class="card-category">Profil guru dan akun login dikelola dari satu tempat.</p>
         </div>
         <div class="card-body">
            <form action="<?= base_url('admin/guru/edit') ?>" method="post" autocomplete="off">
               <?= csrf_field() ?>
               <input type="hidden" name="id" value="<?= esc($data['id_guru']) ?>">

               <div class="row">
                  <div class="col-md-6">
                     <div class="form-group">
                        <label>NUPTK</label>
                        <input type="text" name="nuptk" class="form-control" value="<?= esc(old('nuptk', $data['nuptk'])) ?>" required>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" value="<?= esc(old('nama', $data['nama_guru'])) ?>" required>
                     </div>
                  </div>
               </div>

               <div class="form-group">
                  <label class="d-block">Jenis Kelamin</label>
                  <label class="mr-4"><input type="radio" name="jk" value="Laki-laki" <?= old('jk', $data['jenis_kelamin']) === 'Laki-laki' ? 'checked' : '' ?> required> Laki-laki</label>
                  <label><input type="radio" name="jk" value="Perempuan" <?= old('jk', $data['jenis_kelamin']) === 'Perempuan' ? 'checked' : '' ?>> Perempuan</label>
               </div>

               <div class="form-group">
                  <label>Alamat</label>
                  <textarea name="alamat" class="form-control" rows="2" required><?= esc(old('alamat', $data['alamat'])) ?></textarea>
               </div>

               <div class="row">
                  <div class="col-md-6">
                     <div class="form-group">
                        <label>No HP</label>
                        <input type="text" name="no_hp" class="form-control" value="<?= esc(old('no_hp', $data['no_hp'])) ?>" required>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="form-group">
                        <label>RFID Code <small class="text-muted">(opsional)</small></label>
                        <input type="text" name="rfid" class="form-control" value="<?= esc(old('rfid', $data['rfid_code'] ?? '')) ?>">
                     </div>
                  </div>
               </div>

               <hr>
               <h5 class="text-success"><b>Akun Login Guru</b></h5>

               <?php if ($account): ?>
                  <div class="alert alert-success">
                     <b>Akun login sudah terhubung.</b> Role <code>guru</code> dipastikan tetap ada. Role tambahan yang sudah dimiliki akun tidak dihapus.
                  </div>
               <?php else: ?>
                  <div class="alert alert-warning">
                     Guru ini <b>belum memiliki akun login</b>. Isi username, email, dan password untuk membuat akun Guru sekarang.
                  </div>
               <?php endif; ?>

               <div class="row">
                  <div class="col-md-6">
                     <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" value="<?= esc(old('username', $account['username'] ?? '')) ?>" minlength="3" maxlength="30" required>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="form-group">
                        <label>Email Login</label>
                        <input type="email" name="email" class="form-control" value="<?= esc(old('email', $account['email'] ?? '')) ?>" maxlength="254" required>
                     </div>
                  </div>
               </div>

               <div class="row">
                  <div class="col-md-6">
                     <div class="form-group">
                        <label><?= $account ? 'Password Baru' : 'Password' ?></label>
                        <input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password" <?= $account ? '' : 'required' ?> >
                        <?php if ($account): ?><small class="form-text text-muted">Kosongkan jika password tidak ingin diubah.</small><?php endif; ?>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="form-group">
                        <label>Konfirmasi <?= $account ? 'Password Baru' : 'Password' ?></label>
                        <input type="password" name="pass_confirm" class="form-control" minlength="8" autocomplete="new-password" <?= $account ? '' : 'required' ?> >
                     </div>
                  </div>
               </div>

               <div class="form-group">
                  <label>Role</label><br>
                  <span class="badge badge-success">Guru (otomatis)</span>
                  <?php foreach (explode(',', (string) ($account['roles'] ?? '')) as $role): ?>
                     <?php if ($role !== '' && $role !== 'guru'): ?>
                        <span class="badge badge-secondary ml-1 text-capitalize"><?= esc($role) ?></span>
                     <?php endif; ?>
                  <?php endforeach; ?>
               </div>

               <a href="<?= base_url('admin/guru') ?>" class="btn btn-secondary">Batal</a>
               <button type="submit" class="btn btn-success">Simpan Perubahan</button>
            </form>
         </div>
      </div>
   </div>
</div>
<?= $this->endSection() ?>
