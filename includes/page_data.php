<?php
/** Server data consumed by the shared JavaScript bundle. */
if (isset($page_data)):
?>
<script type="application/json" id="sirah-page-data"><?= json_encode($page_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php endif; ?>