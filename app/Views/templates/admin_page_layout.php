<?php
// Keep the shared shell stable while allowing safe sidebar-only Turbo visits.
$pageContent = (string) $this->renderSection("content");
$pageScripts = (string) $this->renderSection("scripts");
$pageHasScripts = preg_match('/<script\\b/i', $pageContent . $pageScripts) === 1;
?>
<!DOCTYPE html>
<html lang="id">

<?= $this->include("templates/head") ?>

<body data-hadira-page-scripted="<?= $pageHasScripts ? 'true' : 'false'; ?>">
   <div>
      <?= $this->include("templates/sidebar", ['pageHasScripts' => $pageHasScripts]) ?>
      <div class="main-panel">

         <?= $this->include("templates/navbar") ?>

         <?= $pageContent ?>

         <?= $this->include("templates/footer") ?>

         <!-- komentar jika tidak dipakai -->
         <?php
         // echo $this->include('templates/fixed_plugin')
         ?>

      </div>
   </div>

   <?= $this->include("templates/js") ?>

   <script data-turbo-eval="false">
      var BaseConfig = {
         baseURL: '<?= base_url() ?>',
         csrfTokenName: '<?= csrf_token() ?>',
         textOk: "Ok",
         textCancel: "Batalkan"
      };
   </script>

   <?= $pageScripts ?>
</body>

</html>
