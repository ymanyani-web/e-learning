<?php
use App\Core\Csrf;
function h(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title ?? 'LearnSpace') ?></title>
    <style>
        :root{--ink:#15233d;--brand:#4f46e5;--mist:#f4f7fb;--line:#dbe3ef;--ok:#086b45;--bad:#b42318}
        *{box-sizing:border-box}body{margin:0;background:var(--mist);color:var(--ink);font:15px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif}
        header{background:var(--ink);color:#fff;padding:18px max(24px,calc((100% - 1180px)/2));display:flex;justify-content:space-between;gap:20px;align-items:center}
        header a{color:#fff;text-decoration:none;font-weight:700}.brand{font-size:20px}.wrap{max-width:1180px;margin:30px auto;padding:0 24px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px}.card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:20px;box-shadow:0 5px 20px #15233d08}.wide{grid-column:1/-1}.muted{color:#64748b}.pill{display:inline-block;background:#eef2ff;color:#3730a3;border-radius:999px;padding:3px 9px;font-size:12px;font-weight:700}.notice{padding:12px 15px;border-radius:10px;margin:0 0 18px}.success{background:#eaf8f0;color:var(--ok)}.error{background:#fff0ef;color:var(--bad)}
        h1{margin:0 0 8px}h2{font-size:18px;margin:0 0 14px}h3{margin:0 0 7px}form{display:grid;gap:10px}input,select,textarea,button{font:inherit;border-radius:8px;padding:9px 10px;border:1px solid var(--line)}textarea{min-height:92px;resize:vertical}button,.button{background:var(--brand);color:#fff;border:0;cursor:pointer;font-weight:700;text-decoration:none;text-align:center}.button{display:inline-block;padding:9px 12px;border-radius:8px}.secondary{background:#e9edff;color:#312e81}label{font-weight:650;font-size:13px}.row{display:flex;gap:9px;align-items:center;flex-wrap:wrap}.item{border-top:1px solid var(--line);padding:13px 0}.item:first-of-type{border-top:0}.content{white-space:pre-wrap}.options{display:grid;gap:8px}.options label{font-weight:400;font-size:15px}.score{font-size:25px;font-weight:800;color:var(--brand)}@media(max-width:600px){header{align-items:flex-start;flex-direction:column}.wrap{padding:0 15px}}
    </style>
</head>
<body>
<header><a class="brand" href="/">LearnSpace</a><div><?= h($user['first_name'] ?? '') ?> · <a href="/logout">Log out</a></div></header>
<main class="wrap">
<?php if (!empty($message)): ?><div class="notice success"><?= h($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="notice error"><?= h($error) ?></div><?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const basePath = <?= json_encode(\App\Core\Request::basePath()) ?>;
    if (!basePath) return;
    document.querySelectorAll('a[href^="/"], form[action^="/"]').forEach((element) => {
        const attribute = element.tagName === 'FORM' ? 'action' : 'href';
        element.setAttribute(attribute, basePath + element.getAttribute(attribute));
    });
});
</script>
