<?php
// Keep AJAX navigation limited to pages without page-specific JavaScript.
$pageContent = (string) $this->renderSection("content");
$pageScripts = (string) $this->renderSection("scripts");
$pageHasScripts = preg_match('/<script\\b/i', $pageContent . $pageScripts) === 1;

// Keep Material Dashboard's expected structure: .main-panel > .content.
// Put the navigation target attributes on the page's own .content root rather
// than adding another wrapper between .main-panel and .content.
$pageTitleAttr = esc($title ?? 'HADIRA', 'attr');
$pageContextAttr = esc($ctx ?? '', 'attr');
$pageRootPattern = '/<div\\b(?=[^>]*\\bclass\\s*=\\s*(["\\\'])[^"\\\']*\\bcontent\\b[^"\\\']*\\1)[^>]*>/i';
$pageHasContentRoot = preg_match($pageRootPattern, $pageContent) === 1;

if ($pageHasContentRoot) {
    $pageContent = preg_replace_callback(
        $pageRootPattern,
        static function (array $matches) use ($pageTitleAttr, $pageContextAttr): string {
            $openingTag = $matches[0];

            if (preg_match('/\\bid\\s*=\\s*(["\\\'])hadira-page-content\\1/i', $openingTag) === 1) {
                return $openingTag;
            }

            $attributes = ' id="hadira-page-content"'
                . ' data-hadira-page-content'
                . ' data-page-title="' . $pageTitleAttr . '"'
                . ' data-page-context="' . $pageContextAttr . '"';

            return preg_replace('/^<div\\b/i', '<div' . $attributes, $openingTag, 1) ?? $openingTag;
        },
        $pageContent,
        1
    ) ?? $pageContent;
}
?>
<!DOCTYPE html>
<html lang="id">

<?= $this->include("templates/head") ?>

<body data-hadira-page-scripted="<?= $pageHasScripts ? 'true' : 'false'; ?>">
   <!-- Material Dashboard's shared shell parent is .wrapper. -->
   <div class="wrapper hadira-app-shell">
      <?= $this->include("templates/sidebar") ?>

      <div class="main-panel">
         <?= $this->include("templates/navbar") ?>

         <?= $pageContent ?>

         <?= $this->include("templates/footer") ?>

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
