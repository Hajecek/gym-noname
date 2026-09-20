<section class="section"><div class="container card" style="max-width:760px">
<?php $document = $document ?? $page ?? []; ?>
<h1><?= e(is_array($document) ? ($document['title'] ?? 'Dokument') : $title ?? 'Dokument') ?></h1>
<div class="muted"><?= nl2br(e(is_array($document) ? ($document['body_html'] ?? '') : '')) ?></div>
</div></section>
