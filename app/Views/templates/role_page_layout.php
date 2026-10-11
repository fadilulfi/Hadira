<?php
// Role pages (Guru, BK, Waka) use Material Dashboard's normal wrapper/content hierarchy.
$pageContent = (string) $this->renderSection("content");
$pageScripts = (string) $this->renderSection("scripts");
$pageHasScripts = preg_match('/<script\b/i', $pageContent . $pageScripts) === 1;

$pageTitleAttr = esc($title ?? 'HADIRA', 'attr');
$pageContextAttr = esc($ctx ?? '', 'attr');
// Each full role page must have a root .content element so the theme styles it as
// a direct child of .main-panel. Put the navigation metadata on that node.
$pageRootPattern = '/<div\b(?=[^>]*\bclass="[^"]*\bcontent\b[^"]*")[^>]*>/i';

if (preg_match($pageRootPattern, $pageContent) === 1) {
    $pageContent = preg_replace_callback(
        $pageRootPattern,
        static function (array $matches) use ($pageTitleAttr, $pageContextAttr): string {
            $openingTag = $matches[0];

            if (preg_match('/\bid="hadira-page-content"/i', $openingTag) === 1) {
                return $openingTag;
            }

            $attributes = ' id="hadira-page-content"'
                . ' data-hadira-page-content'
                . ' data-page-title="' . $pageTitleAttr . '"'
                . ' data-page-context="' . $pageContextAttr . '"';

            return preg_replace('/^<div\b/i', '<div' . $attributes, $openingTag, 1) ?? $openingTag;
        },
        $pageContent,
        1
    ) ?? $pageContent;
}
?>
<!DOCTYPE html>
<html lang="id">

<?= $this->include("templates/head") ?>

<body class="hadira-shell-page" data-hadira-page-scripted="<?= $pageHasScripts ? 'true' : 'false'; ?>">
   <div class="wrapper hadira-app-shell hadira-role-shell">
      <?= $this->include("templates/sidebar") ?>
      <div class="main-panel">
         <?= $this->include("templates/navbar") ?>

         <?= $pageContent ?>

         <?= $this->include("templates/footer") ?>
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
