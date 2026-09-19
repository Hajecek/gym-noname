<section class="section"><div class="container faq">
<h1>Časté otázky</h1>
<?php foreach ($faqs as $faq): ?>
<details><summary><?= e($faq['question']) ?></summary><p class="muted"><?= nl2br(e($faq['answer'])) ?></p></details>
<?php endforeach; ?>
</div></section>
