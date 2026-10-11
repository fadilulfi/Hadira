<?php
$pageContent = (string) $this->renderSection("content");
$pageScripts = (string) $this->renderSection("scripts");

// Keep only lifecycle-sensitive pages on normal browser navigation.
// Other legacy inline scripts are scoped and re-run by the persistent-shell navigator.
$navigationSource = $pageContent . $pageScripts;
$pageRequiresFullNavigation = preg_match(
    '~\bDOMContentLoaded\b|\b(?:window|self)\s*\.\s*onload\s*=|\b(?:window|self)\s*\.\s*addEventListener\s*\(\s*["\']load["\']|\bBrowserMultiFormatReader\b|\bdecodeOnceFromVideoDevice\b|\bgetUserMedia\b|\bwindow\s*\.\s*print\s*\(|\bdocument\s*\.\s*write\s*\(~i',
    $navigationSource
) === 1;

// Move any script blocks embedded in the content section to the dedicated script area,
// where jQuery and HadiraNavigation are already available before execution.
$contentScripts = [];
$pageContent = preg_replace_callback(
    '~<script\b([^>]*)>([\s\S]*?)</script\s*>~i',
    static function (array $matches) use (&$contentScripts): string {
        $contentScripts[] = $matches[0];
        return '';
    },
    $pageContent
) ?? $pageContent;
if ($contentScripts !== []) {
    $pageScripts = implode("\n", $contentScripts) . "\n" . $pageScripts;
}

// Find functions referenced by inline HTML event attributes so they can remain callable
// while their page script runs inside an isolated scope.
$inlineHandlerNames = [];
if (preg_match_all('~\bon[a-z][a-z0-9_-]*\s*=\s*(["\'])(.*?)\1~is', $pageContent, $handlerMatches)) {
    foreach ($handlerMatches[2] as $handlerSource) {
        if (preg_match_all('~(?:^|[^\w$.])([A-Za-z_$][A-Za-z0-9_$]*)\s*\(~', $handlerSource, $functionCalls)) {
            foreach ($functionCalls[1] as $functionName) {
                $inlineHandlerNames[$functionName] = true;
            }
        }
    }
}
$inlineHandlerNames = array_keys($inlineHandlerNames);

// Scope inline page scripts to prevent let/const collisions after AJAX navigation.
// External scripts stay as separate tags and retain their normal loading behavior.
$pageScripts = preg_replace_callback(
    '~<script\b([^>]*)>([\s\S]*?)</script\s*>~i',
    static function (array $matches) use ($inlineHandlerNames): string {
        $attributes = $matches[1];
        $scriptCode = $matches[2];

        if (preg_match('/\bsrc\s*=/i', $attributes) === 1 || trim($scriptCode) === '') {
            return $matches[0];
        }

        if (preg_match('/\btype\s*=\s*(["\'])(.*?)\1/i', $attributes, $typeMatch) === 1) {
            $scriptType = strtolower(trim($typeMatch[2]));
            if (!in_array($scriptType, ['text/javascript', 'application/javascript', 'text/ecmascript', 'application/ecmascript'], true)) {
                return $matches[0];
            }
        }

        $exports = [];
        foreach ($inlineHandlerNames as $functionName) {
            $quotedName = preg_quote($functionName, '~');
            $declarationPattern = '~\bfunction\s+' . $quotedName . '\s*\(|\b(?:var|let|const)\s+' . $quotedName . '\b~';
            if (preg_match($declarationPattern, $scriptCode) !== 1) {
                continue;
            }

            $encodedName = json_encode($functionName, JSON_UNESCAPED_SLASHES);
            $exports[] = 'if (typeof ' . $functionName . ' !== "undefined" && window.HadiraNavigation) { '
                . 'window.HadiraNavigation.exposePageFunction(' . $encodedName . ', ' . $functionName . '); }';
        }

        return '<script' . $attributes . ' data-hadira-page-scoped="true">' . "\n(function () {\n"
            . $scriptCode . "\n" . implode("\n", $exports) . "\n})();\n</script>";
    },
    $pageScripts
) ?? $pageScripts;


?>
<!DOCTYPE html>
<html lang="id">

<?= $this->include("templates/head") ?>

<body data-hadira-full-navigation="<?= $pageRequiresFullNavigation ? 'true' : 'false'; ?>">
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
