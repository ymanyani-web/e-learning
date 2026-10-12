<?php
use App\Core\Csrf;
function h(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
$role = $user['role'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title ?? 'LearnSpace') ?></title>
    <style>
        :root{--ink:#17223b;--brand:#5b5ce2;--brand-dark:#4445c4;--mist:#f5f7fb;--line:#e4e9f2;--ok:#087a4d;--bad:#b42318;--sun:#f5a524}
        *{box-sizing:border-box}body{margin:0;background:var(--mist);color:var(--ink);font:15px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif}
        header{background:var(--ink);color:#fff;padding:0 max(24px,calc((100% - 1220px)/2));display:flex;gap:26px;align-items:center;min-height:68px}header a{color:#fff;text-decoration:none;font-weight:700}.brand{font-size:20px;letter-spacing:-.5px}.nav{display:flex;align-self:stretch;align-items:center;gap:4px;flex:1}.nav a{padding:22px 12px;color:#cbd5e1;font-size:14px}.nav a:hover{color:#fff;background:#ffffff10}.account{font-size:13px;color:#cbd5e1}.account a{margin-left:10px}.wrap{max-width:1220px;margin:34px auto;padding:0 24px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px}.card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:21px;box-shadow:0 8px 25px #15233d08}.wide{grid-column:1/-1}.muted{color:#64748b}.pill{display:inline-block;background:#eef2ff;color:#3730a3;border-radius:999px;padding:3px 9px;font-size:12px;font-weight:700}.notice{padding:12px 15px;border-radius:10px;margin:0 0 18px}.success{background:#eaf8f0;color:var(--ok)}.error{background:#fff0ef;color:var(--bad)}
        h1{margin:0 0 8px;letter-spacing:-.7px}h2{font-size:18px;margin:0 0 14px}h3{margin:0 0 7px}form{display:grid;gap:10px}input,select,textarea,button{font:inherit;border-radius:9px;padding:10px 11px;border:1px solid var(--line)}textarea{min-height:92px;resize:vertical}button,.button{background:var(--brand);color:#fff;border:0;cursor:pointer;font-weight:700;text-decoration:none;text-align:center;transition:background .15s}.button{display:inline-block;padding:10px 13px;border-radius:9px}.button:hover,button:hover{background:var(--brand-dark)}.secondary{background:#eceeff;color:#3534a3}.secondary:hover{background:#dfe2ff}.danger{background:#fff0ef;color:var(--bad);border:1px solid #ffd4cf}.danger:hover{background:#ffe3df}label{font-weight:650;font-size:13px}.row{display:flex;gap:9px;align-items:center;flex-wrap:wrap}.item{border-top:1px solid var(--line);padding:13px 0}.item:first-of-type{border-top:0}.content{white-space:pre-wrap}.options{display:grid;gap:8px}.options label{font-weight:400;font-size:15px}.score{font-size:25px;font-weight:800;color:var(--brand)}.hero{display:flex;justify-content:space-between;gap:24px;align-items:flex-end;margin-bottom:25px}.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}.kpi{background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px}.kpi span{display:block;color:#64748b;font-size:13px;font-weight:650}.kpi strong{display:block;font-size:30px;margin-top:4px;letter-spacing:-1px}.section-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse}th,td{padding:11px 10px;border-bottom:1px solid var(--line);text-align:left;vertical-align:middle}th{font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#64748b}.empty{padding:24px 0;text-align:center;color:#64748b}.compact-form{display:flex;align-items:center;gap:8px}.compact-form button{padding:7px 10px;font-size:12px}@media(max-width:760px){header{padding:13px 18px;align-items:flex-start;flex-wrap:wrap}.nav{order:3;flex-basis:100%;overflow-x:auto}.nav a{padding:8px 9px}.account{margin-left:auto}.wrap{padding:0 15px}.hero{align-items:flex-start;flex-direction:column}.kpis{grid-template-columns:repeat(2,1fr)}}@media(max-width:390px){.kpis{grid-template-columns:1fr}}
    </style>
</head>
<body>
<header>
    <a class="brand" href="/">LearnSpace</a>
    <nav class="nav">
        <?php if ($role === 'ADMIN'): ?><a href="/admin">Overview</a><a href="/admin#students">Learner records</a><a href="/admin/users">People</a><?php endif; ?>
        <?php if ($role === 'TEACHER'): ?><a href="/teacher">My courses</a><?php endif; ?>
        <?php if ($role === 'STUDENT'): ?><a href="/student">My learning</a><?php endif; ?>
    </nav>
    <div class="account"><?= h($user['first_name'] ?? '') ?> <a href="/logout">Log out</a></div>
</header>
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
