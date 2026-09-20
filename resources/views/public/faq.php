<section class="page-block wrapper">
<h1>Časté otázky</h1>
<div class="faq-list reveal visible">
<?php foreach ($faqs as $faq): ?>
<details><summary><?= e($faq['question']) ?><span>+</span></summary><p><?= nl2br(e($faq['answer'])) ?></p></details>
<?php endforeach; ?>
</div>
</section>
