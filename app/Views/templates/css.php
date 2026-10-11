<?php
// Update this when you make changes to CSS files
$assetVersion = '1.0.8';
?>
<link rel="stylesheet" href="<?= base_url('assets/fonts/fonts.css?v=' . $assetVersion); ?>" data-hadira-global-style="true" />
<link rel="stylesheet" href="<?= base_url('assets/css/material-dashboard.min.css?v=' . $assetVersion); ?>" data-hadira-global-style="true" />
<link rel="stylesheet" href="<?= base_url('assets/css/style.min.css?v=' . $assetVersion); ?>" data-hadira-global-style="true" />
<link rel="stylesheet" href="<?= base_url('assets/js/plugins/file-uploader/css/jquery.dm-uploader.min.css?v=' . $assetVersion); ?>" data-hadira-global-style="true" />
<link rel="stylesheet" href="<?= base_url('assets/js/plugins/file-uploader/css/styles-1.0.css?v=' . $assetVersion); ?>" data-hadira-global-style="true" />
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css" data-hadira-global-style="true" />
<link rel="stylesheet" href="<?= base_url('assets/css/navigation-transitions.css?v=' . $assetVersion); ?>" data-hadira-global-style="true" />

<?= $this->renderSection("styles") ?>