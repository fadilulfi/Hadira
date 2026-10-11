<?= $this->extend('templates/admin_page_layout') ?>
<?= $this->section('content') ?>
<div class="content">
   <div class="container-fluid">
      <?php $errors = $accountErrors ?? []; ?>
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

      <div class="card">
         <div class="card-header card-header-success">
            <h4 class="card-title"><b>Form Tambah Guru</b></h4>
            <p class="card-category">Data guru sekaligus membuat akun login Guru.</p>
         </div>
         <div class="card-body">
            <form action="<?= base_url('admin/guru/create') ?>" method="post" autocomplete="off">
               <?= csrf_field() ?>

               <div class="row">
                  <div class="col-md-6">
                     <div class="form-group">
                        <label>NUPTK</label>
                        <input type="text" name="nuptk" class="form-control" value="<?= esc(old('nuptk')) ?>" required>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" value="<?= esc(old('nama')) ?>" required>
                     </div>
                  </div>
               </div>

               <div class="form-group">
                  <label class="d-block">Jenis Kelamin</label>
                  <label class="mr-4"><input type="radio" name="jk" value="Laki-laki" <?= old('jk') === 'Laki-laki' ? 'checked' : '' ?> required> Laki-laki</label>
                  <label><input type="radio" name="jk" value="Perempuan" <?= old('jk') === 'Perempuan' ? 'checked' : '' ?>> Perempuan</label>
               </div>

               <div class="form-group">
                  <label>Alamat</label>
                  <textarea name="alamat" class="form-control" rows="2" required><?= esc(old('alamat')) ?></textarea>
               </div>

               <div class="row">
                  <div class="col-md-6">
                     <div class="form-group">
                        <label>No HP</label>
                        <input type="text" name="no_hp" class="form-control" value="<?= esc(old('no_hp')) ?>" required>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="form-group">
                        <label>RFID Code <small class="text-muted">(opsional)</small></label>
                        <input type="text" name="rfid" class="form-control" value="<?= esc(old('rfid')) ?>">
                     </div>
                  </div>
               </div>

               <hr>
               <h5 class="text-success"><b>Akun Login Guru</b></h5>
               <p class="text-muted">Akun akan otomatis dibuat di <code>users</code> + <code>auth_identities</code> dan diberi role <code>guru</code>.</p>

               <div class="row">
                  <div class="col-md-6">
                     <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" value="<?= esc(old('username')) ?>" minlength="3" maxlength="30" required>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="form-group">
                        <label>Email Login</label>
                        <input type="email" name="email" class="form-control" value="<?= esc(old('email')) ?>" maxlength="254" required>
                     </div>
                  </div>
               </div>

               <div class="row">
                  <div class="col-md-6">
                     <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password" required>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="form-group">
                        <label>Konfirmasi Password</label>
                        <input type="password" name="pass_confirm" class="form-control" minlength="8" autocomplete="new-password" required>
                     </div>
                  </div>
               </div>

               <div class="alert alert-info">
                  <b>Role Guru otomatis.</b> Tidak ada pilihan role di sini. Role tambahan seperti <b>BK</b> atau <b>Waka Kurikulum</b> dikelola sebagai group user tersendiri.
               </div>

               <a href="<?= base_url('admin/guru') ?>" class="btn btn-secondary">Batal</a>
               <button type="submit" class="btn btn-success">Simpan Guru + Akun</button>
            </form>
         </div>
      </div>
   </div>
</div>
<?= $this->endSection() ?>
