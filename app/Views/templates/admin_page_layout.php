<?php
// Keep AJAX navigation limited to pages with no page-specific JavaScript.
// Script-heavy pages use normal navigation to preserve their one-page JS lifecycle.
$pageContent = (string) $this->renderSection("content");
$pageScripts = (string) $this->renderSection("scripts");
$pageHasScripts = preg_match('/<script\b/i', $pageContent . $pageScripts) === 1;
?>
<!DOCTYPE html>
<html lang="id">

<?= $this->include("templates/head") ?>

<body class="hadira-shell-page" data-hadira-page-scripted="<?= $pageHasScripts ? 'true' : 'false'; ?>">
   <div class="wrapper hadira-app-shell hadira-admin-shell">
      <?= $this->include("templates/sidebar") ?>
      <div class="main-panel">

         <?= $this->include("templates/navbar") ?>

         <div id="hadira-page-content"
              data-hadira-page-content
              data-page-title="<?= esc($title ?? 'HADIRA'); ?>"
              data-page-context="<?= esc($ctx ?? ''); ?>">
            <?= $pageContent ?>
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
      <?= $pageScripts ?>
   </div>
</body>

</html>
