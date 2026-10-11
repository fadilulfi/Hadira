<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-transparent navbar-absolute fixed-top">
   <div class="container-fluid">
      <div class="navbar-wrapper">
         <p class="navbar-brand my-auto"><b id="hadira-page-title"><?= esc($title ?? 'HADIRA'); ?></b></p>
      </div>

      <button class="navbar-toggler" type="button" data-toggle="collapse" aria-controls="navigation-index" aria-expanded="false" aria-label="Toggle navigation">
         <span class="sr-only">Toggle navigation</span>
         <span class="navbar-toggler-icon icon-bar"></span>
         <span class="navbar-toggler-icon icon-bar"></span>
         <span class="navbar-toggler-icon icon-bar"></span>
      </button>

      <div class="collapse navbar-collapse justify-content-end">
         <ul class="navbar-nav">
            <li class="nav-item dropdown">
               <a class="nav-link <?= is_superadmin() ? 'text-danger' : ''; ?>" href="javascript:;" id="navbarDropdownProfile" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="material-icons">person</i>
                  <p class="d-lg-none d-md-block">Account</p>
                  <span>User : <?= esc(user()->username); ?></span>
               </a>

               <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownProfile">
                  <div class="dropdown-item">
                     <strong>Email</strong><br>
                     <?= esc(user()->email); ?>
                  </div>

                  <div class="dropdown-item">
                     <div class="mb-1"><strong>Role</strong></div>
                     <?php foreach (user()->getGroups() ?? [] as $g): ?>
                        <?php
                           $badge = match ($g) {
                              'superadmin' => 'danger',
                              'admin'      => 'success',
                              'kepsek'     => 'warning',
                              'scanner'    => 'info',
                              'guru'       => 'secondary',
                              'bk'         => 'warning',
                              'waka'       => 'primary',
                              default      => 'secondary',
                           };

                           $label = match ($g) {
                              'superadmin' => 'Super Admin',
                              'admin'      => 'Staf Petugas',
                              'kepsek'     => 'Kepsek',
                              'scanner'    => 'Scanner',
                              'guru'       => 'Guru',
                              'bk'         => 'Guru BK',
                              'waka'       => 'Waka Kurikulum',
                              default      => ucfirst((string) $g),
                           };
                        ?>
                        <span class="h6 badge badge-<?= $badge ?> text-capitalize mx-1 my-auto"><?= esc($label); ?></span>
                     <?php endforeach; ?>

                     <?php if (is_wali_kelas()): ?>
                        <span class="h6 badge badge-primary text-capitalize mx-1 my-auto">Wali Kelas</span>
                     <?php endif; ?>
                  </div>

                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item" href="<?= base_url('/logout'); ?>">
                     <i class="material-icons align-middle mr-2">logout</i> Log Out
                  </a>
               </div>
            </li>
         </ul>
      </div>
   </div>
</nav>
<!-- End Navbar -->
