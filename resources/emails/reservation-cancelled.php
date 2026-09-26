<?php
$body = 'Rezervace na ' . ($starts_at ?? '') . ' byla zrušena.';
$note = trim((string) ($reason ?? ''));
if ($note !== '') {
    $body .= "\n\nDůvod: " . $note;
}
include __DIR__ . '/generic.php';