<!DOCTYPE html>
<html lang="cs"><head><meta charset="utf-8"><title>PRIVOFIT</title></head>
<body style="margin:0;background:#0B1220;color:#F8FAFC;font-family:Manrope,Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0B1220;padding:32px 16px">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#1A2940;border:1px solid #293B54;border-radius:18px;padding:28px">
<tr><td><img src="<?= htmlspecialchars(url('/assets/brand/logo-transparent.png') . '?v=2', ENT_QUOTES) ?>" alt="PRIVOFIT" width="180" height="34" style="display:block;height:34px;width:auto"></td></tr>
<tr><td style="padding-top:18px"><h1 style="margin:0;font-size:24px"><?= htmlspecialchars($subject ?? 'PRIVOFIT', ENT_QUOTES) ?></h1></td></tr>
<tr><td style="padding-top:12px;color:#94A3B8">Ahoj <?= htmlspecialchars($first_name ?? '', ENT_QUOTES) ?>,</td></tr>
<tr><td style="padding-top:12px;color:#F8FAFC"><?= nl2br(htmlspecialchars($body ?? 'Děkujeme, že využíváte PRIVOFIT.', ENT_QUOTES)) ?></td></tr>
<?php if (!empty($action_url)): ?>
<tr><td style="padding-top:22px"><a href="<?= htmlspecialchars($action_url, ENT_QUOTES) ?>" style="background:#42E8B4;color:#04251b;padding:12px 20px;border-radius:999px;text-decoration:none;font-weight:700">Pokračovat</a></td></tr>
<?php endif; ?>
</table>
</td></tr></table>
</body></html>
