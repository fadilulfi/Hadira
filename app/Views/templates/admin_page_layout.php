<!DOCTYPE html>
<html lang="id">

<?= $this->include("templates/head") ?>

<body>
   <div>
      <?= $this->include("templates/sidebar") ?>
      <div class="main-panel">

         <?= $this->include("templates/navbar") ?>

         <div id="hadira-page-content"
              data-hadira-page-content
              data-page-title="<?= esc($title ?? 'HADIRA'); ?>"
              data-page-context="<?= esc($ctx ?? ''); ?>">
            <?= $this->renderSection("content") ?>
         </div>

         <?= $this->include("templates/footer") ?>

         <!-- komentar jika tidak dipakai -->
         <?php
         // echo $this->include('templates/fixed_plugin')
         ?>

      </div>
   </div>

   <?= $this->include("templates/js") ?>

   <script>
      var BaseConfig = {
         baseURL: '<?= base_url() ?>',
         csrfTokenName: '<?= csrf_token() ?>',
         textOk: "Ok",
         textCancel: "Batalkan"
      };
      if (window.HadiraNavigation) {
         window.HadiraNavigation.beginPage();
      }
   </script>

   <div id="hadira-page-scripts" data-hadira-page-scripts hidden>
      <?= $this->renderSection("scripts") ?>
   </div>
</body>

</html>
