<?php
use App\Core\Csrf;
use App\Core\Locale;
function h(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function t(string $key): string { return Locale::text($key); }
function icon(string $name): string {
    $paths = [
        'settings' => '<path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.12 2.12-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V20h-3v-.08a1.7 1.7 0 0 0-1.03-1.56 1.7 1.7 0 0 0-1.88.34l-.06.06-2.12-2.12.06-.06A1.7 1.7 0 0 0 7 14.7a1.7 1.7 0 0 0-1.56-1.03H5.3v-3h.08A1.7 1.7 0 0 0 6.94 9.64 1.7 1.7 0 0 0 6.6 7.76l-.06-.06 2.12-2.12.06.06a1.7 1.7 0 0 0 1.88.34A1.7 1.7 0 0 0 11.63 4.4V4.3h3v.08a1.7 1.7 0 0 0 1.03 1.56 1.7 1.7 0 0 0 1.88-.34l.06-.06 2.12 2.12-.06.06a1.7 1.7 0 0 0-.34 1.88 1.7 1.7 0 0 0 1.56 1.03H21v3h-.08A1.7 1.7 0 0 0 19.4 15Z"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'open' => '<path d="M14 5h5v5M19 5l-8 8"/><path d="M19 14v4a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h4"/>',
        'logout' => '<path d="M10 17l5-5-5-5M15 12H3"/><path d="M15 5h4a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-4"/>',
        'overview' => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',
        'student' => '<circle cx="12" cy="8" r="3"/><path d="M5 20c.7-3.2 3-5 7-5s6.3 1.8 7 5"/>',
        'teacher' => '<path d="m4 10 8-4 8 4-8 4-8-4Z"/><path d="M7 12.3V16c2.7 1.9 7.3 1.9 10 0v-3.7M20 10v6"/>',
        'class' => '<rect x="4" y="5" width="16" height="14" rx="2"/><path d="M8 9h8M8 13h5"/>',
        'subject' => '<path d="M5 4.5A3.5 3.5 0 0 1 8.5 4H20v15H8.5A3.5 3.5 0 0 0 5 22V4.5Z"/><path d="M5 5v14"/>',
    ];
    return '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . ($paths[$name] ?? '') . '</svg>';
}
$role = $user['role'] ?? '';
$language = Locale::language();
?>
<!doctype html>
<html lang="<?= h($language) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title ?? 'LearnSpace') ?></title>
    <style>
        :root{--ink:#17223b;--brand:#5b5ce2;--brand-dark:#4445c4;--mist:#f5f7fb;--line:#e4e9f2;--ok:#087a4d;--bad:#b42318;--sun:#f5a524}
        *{box-sizing:border-box}body{min-height:100vh;margin:0;background:var(--mist);color:var(--ink);font:15px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif}
        header{background:var(--ink);color:#fff;padding:0 max(24px,calc((100% - 1220px)/2));display:flex;gap:26px;align-items:center;min-height:68px}header a{color:#fff;text-decoration:none;font-weight:700}.brand{font-size:20px;letter-spacing:-.5px}.nav{display:flex;align-self:stretch;align-items:center;gap:4px;flex:1}.nav a{padding:22px 12px;color:#cbd5e1;font-size:14px}.nav a:hover{color:#fff;background:#ffffff10}.account{font-size:13px;color:#cbd5e1}.account a{margin-left:10px}.wrap{max-width:1220px;margin:34px auto;padding:0 24px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px}.card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:21px;box-shadow:0 8px 25px #15233d08}.wide{grid-column:1/-1}.muted{color:#64748b}.pill{display:inline-block;background:#eef2ff;color:#3730a3;border-radius:999px;padding:3px 9px;font-size:12px;font-weight:700}.notice{padding:12px 15px;border-radius:10px;margin:0 0 18px}.success{background:#eaf8f0;color:var(--ok)}.error{background:#fff0ef;color:var(--bad)}
        h1{margin:0 0 8px;letter-spacing:-.7px}h2{font-size:18px;margin:0 0 14px}h3{margin:0 0 7px}form{display:grid;gap:10px}input,select,textarea,button{font:inherit;border-radius:9px;padding:10px 11px;border:1px solid var(--line)}textarea{min-height:92px;resize:vertical}button,.button{background:var(--brand);color:#fff;border:0;cursor:pointer;font-weight:700;text-decoration:none;text-align:center;transition:background .15s}.button{display:inline-block;padding:10px 13px;border-radius:9px}.button:hover,button:hover{background:var(--brand-dark)}.secondary{background:#eceeff;color:#3534a3}.secondary:hover{background:#dfe2ff}.danger{background:#fff0ef;color:var(--bad);border:1px solid #ffd4cf}.danger:hover{background:#ffe3df}label{font-weight:650;font-size:13px}.row{display:flex;gap:9px;align-items:center;flex-wrap:wrap}.item{border-top:1px solid var(--line);padding:13px 0}.item:first-of-type{border-top:0}.content{white-space:pre-wrap}.options{display:grid;gap:8px}.options label{font-weight:400;font-size:15px}.score{font-size:25px;font-weight:800;color:var(--brand)}.hero{display:flex;justify-content:space-between;gap:24px;align-items:flex-end;margin-bottom:25px}.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}.kpi{background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px}.kpi span{display:block;color:#64748b;font-size:13px;font-weight:650}.kpi strong{display:block;font-size:30px;margin-top:4px;letter-spacing:-1px}.section-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse}th,td{padding:11px 10px;border-bottom:1px solid var(--line);text-align:left;vertical-align:middle}th{font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#64748b}.empty{padding:24px 0;text-align:center;color:#64748b}.compact-form{display:flex;align-items:center;gap:8px}.compact-form button{padding:7px 10px;font-size:12px}@media(max-width:760px){header{padding:13px 18px;align-items:flex-start;flex-wrap:wrap}.nav{order:3;flex-basis:100%;overflow-x:auto}.nav a{padding:8px 9px}.account{margin-left:auto}.wrap{padding:0 15px}.hero{align-items:flex-start;flex-direction:column}.kpis{grid-template-columns:repeat(2,1fr)}}@media(max-width:390px){.kpis{grid-template-columns:1fr}}
        body:has(.admin-hero),body:has(.page-hero){background-image:radial-gradient(circle at 15% -5%,#dcddff 0,transparent 34%),radial-gradient(circle at 100% 4%,#c6f6ef 0,transparent 28%);background-color:var(--mist);background-repeat:no-repeat;background-size:100% 100%,100% 100%;background-attachment:fixed}.icon{width:16px;height:16px;vertical-align:-3px}.icon-button,.button-icon{display:inline-flex;align-items:center;gap:7px}.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}.modal{display:none;position:fixed;z-index:30;inset:0;padding:24px;place-items:center;background:#10182d99;backdrop-filter:blur(9px)}.modal.is-open{display:grid}.modal-panel{position:relative;width:min(540px,100%);max-height:calc(100vh - 48px);overflow:auto;padding:26px;border:1px solid #ffffff4d;border-radius:22px;background:#fff;box-shadow:0 28px 80px #10182d66}.modal-close{position:absolute;right:15px;top:15px;width:34px;height:34px;padding:0;border-radius:50%;background:#eef0ff;color:#393ba5;font-size:22px}.admin-hero{min-height:230px;padding:42px;border-radius:26px;background:linear-gradient(115deg,#171a47,#4d4fd7 60%,#8a7bf1);color:#fff;overflow:hidden;position:relative;isolation:isolate;margin-bottom:24px}.admin-hero h1{max-width:630px;font-size:clamp(31px,5vw,54px);line-height:1.02;letter-spacing:-2px}.admin-hero p:not(.eyebrow){max-width:570px;color:#dce5ff;font-size:17px}.hero-orb{position:absolute;right:6%;top:-35px;width:250px;height:250px;border:1px solid #ffffff64;border-radius:50%;box-shadow:0 0 0 36px #ffffff0f,0 0 0 74px #ffffff0c;z-index:-1}.eyebrow{font-size:11px!important;letter-spacing:.14em;text-transform:uppercase;font-weight:800;color:#7075e8;margin:0 0 9px}.admin-hero .eyebrow{color:#bfc6ff}.page-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin:8px 0 24px}.page-hero h1{font-size:37px;letter-spacing:-1.5px}.page-hero>div>p:not(.eyebrow){color:#64748b;margin:0}.back-link{display:inline-block;margin-bottom:13px;color:#5154bf;font-weight:750;text-decoration:none}.admin-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.admin-grid>.wide{grid-column:1/-1}.kpi{position:relative;overflow:hidden;background:linear-gradient(130deg,#fff,#f7f8ff)}.kpi:after{content:"";position:absolute;width:90px;height:90px;border-radius:50%;background:#5b5ce212;right:-30px;bottom:-40px}.kpi small{color:#8a94a7;font-size:12px}.chart-card{min-height:235px}.bar-chart{display:grid;gap:16px;padding-top:8px}.bar-row{display:grid;grid-template-columns:82px 1fr 28px;align-items:center;gap:12px;font-size:12px;font-weight:700}.bar-track{height:11px;border-radius:20px;background:#edf0fb;overflow:hidden}.bar-fill{height:11px;border-radius:inherit;background:linear-gradient(90deg,#5b5ce2,#9b8cf7)}.feed-item{display:flex;gap:10px;align-items:flex-start;padding:12px 0;border-top:1px solid var(--line)}.feed-item:first-of-type{border-top:0}.feed-item p,.feed-item time{margin:3px 0 0;color:#718096;font-size:12px}.feed-item time{margin-left:auto;min-width:74px;text-align:right}.event-dot{display:block;width:9px;height:9px;margin-top:6px;border-radius:50%;background:#5b5ce2;box-shadow:0 0 0 4px #5b5ce21c}.event-dot.exam{background:#e05a92;box-shadow:0 0 0 4px #e05a921c}.person-row{display:flex;align-items:center;gap:11px;padding:11px 0;border-top:1px solid var(--line);text-decoration:none;color:inherit}.person-row:first-of-type{border-top:0}.person-row>span:last-child{margin-left:auto;color:#777ae0}.person-row small{display:block;color:#718096;font-size:12px}.avatar{display:grid;place-items:center;width:34px;height:34px;border-radius:12px;background:linear-gradient(135deg,#d9d7ff,#bdeee8);color:#34369c;font-size:12px;font-weight:850;flex:none}.avatar.large{width:64px;height:64px;border-radius:21px;font-size:20px}.filter-bar{grid-template-columns:minmax(180px,1fr) minmax(160px,.55fr) auto auto;align-items:center;margin-bottom:18px}.filter-bar .button{padding:10px 13px}.calendar-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px}.calendar-event{display:flex;gap:13px;padding:14px;border:1px solid var(--line);border-radius:14px;background:#fafbff}.calendar-event time{display:grid;align-content:center;min-width:73px;padding-right:12px;border-right:1px solid var(--line)}.calendar-event time strong{font-size:12px}.calendar-event time span{font-size:12px;color:#718096}.calendar-event>div{display:grid;gap:5px}.calendar-event .pill{width:max-content;font-size:10px}@media(max-width:760px){.admin-hero{padding:29px 24px;min-height:205px}.admin-hero h1{font-size:34px}.page-hero{align-items:flex-start;flex-direction:column}.admin-grid{grid-template-columns:1fr}.filter-bar{grid-template-columns:1fr 1fr}.filter-bar button,.filter-bar .button{width:100%}.feed-item time{display:none}}@media(max-width:430px){.filter-bar{grid-template-columns:1fr}.admin-hero{border-radius:20px}.calendar-list{grid-template-columns:1fr}}
        .editor-toolbar{display:flex;flex-wrap:wrap;gap:6px;padding:8px;border:1px solid var(--line);border-bottom:0;border-radius:9px 9px 0 0;background:#f8f9ff}.editor-toolbar button{padding:6px 8px;background:#fff;color:var(--ink);border:1px solid var(--line);font-size:12px}.rich-editor{min-height:180px;padding:13px;border:1px solid var(--line);border-radius:0 0 9px 9px;background:#fff;outline:0}.rich-editor:empty:before{content:attr(data-placeholder);color:#94a3b8}.rich-editor h2,.rich-editor h3{margin-top:0}.media-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px}.media-card{padding:14px;border:1px solid var(--line);border-radius:14px;background:#fbfcff}.media-card p{margin:5px 0;color:#64748b;font-size:13px}.media-card img,.media-card video,.course-media{display:block;max-width:100%;max-height:380px;margin-top:12px;border-radius:12px}.question-builder .choice-fields{display:grid;gap:8px;padding:10px;border:1px solid var(--line);border-radius:10px}.question-builder .choice-fields label{display:grid;grid-template-columns:1fr auto auto;align-items:center;gap:8px}.question-builder .choice-fields input[type="radio"],.question-builder .choice-fields input[type="checkbox"]{width:16px;height:16px}.question-builder .choice-fields[hidden]{display:none}
    </style>
</head>
<body>
<header>
    <a class="brand" href="/">LearnSpace</a>
    <nav class="nav">
        <?php if ($role === 'ADMIN'): ?><a href="/admin"><?= icon('overview') ?><?= h(Locale::text('overview')) ?></a><a href="/admin/students"><?= icon('student') ?><?= h(Locale::text('students')) ?></a><a href="/admin/teachers"><?= icon('teacher') ?><?= h(Locale::text('teachers')) ?></a><a href="/admin/classes"><?= icon('class') ?><?= h(Locale::text('classes')) ?></a><a href="/admin/subjects"><?= icon('subject') ?><?= h(Locale::text('subjects')) ?></a><?php endif; ?>
        <?php if ($role === 'TEACHER'): ?><a href="/teacher"><?= icon('overview') ?>Dashboard</a><a href="/teacher/classes"><?= icon('class') ?>Classes</a><a href="/teacher/courses"><?= icon('subject') ?>Courses</a><?php endif; ?>
        <?php if ($role === 'STUDENT'): ?><a href="/student"><?= icon('overview') ?>Dashboard</a><a href="/student/courses"><?= icon('subject') ?>Courses</a><a href="/student/exams"><?= icon('open') ?>Exams</a><?php endif; ?>
    </nav>
    <div class="account"><?= h($user['first_name'] ?? '') ?> <a href="/settings" title="<?= h(Locale::text('settings')) ?>"><?= icon('settings') ?></a> <a href="/logout" title="<?= h(Locale::text('logout')) ?>"><?= icon('logout') ?></a></div>
</header>
<main class="wrap">
<?php if (!empty($message)): ?><div class="notice success"><?= h($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="notice error"><?= h($error) ?></div><?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const basePath = <?= json_encode(\App\Core\Request::basePath()) ?>;
    if (basePath) {
        document.querySelectorAll('a[href^="/"], form[action^="/"]').forEach((element) => {
            const attribute = element.tagName === 'FORM' ? 'action' : 'href';
            element.setAttribute(attribute, basePath + element.getAttribute(attribute));
        });
    }
    document.querySelectorAll('[data-modal-open]').forEach((trigger) => trigger.addEventListener('click', () => {
        document.getElementById(trigger.dataset.modalOpen)?.classList.add('is-open');
    }));
    document.querySelectorAll('[data-modal-close]').forEach((trigger) => trigger.addEventListener('click', () => {
        trigger.closest('.modal')?.classList.remove('is-open');
    }));
    document.querySelectorAll('.modal').forEach((modal) => modal.addEventListener('click', (event) => {
        if (event.target === modal) modal.classList.remove('is-open');
    }));
    const translations = <?= json_encode(Locale::clientTranslations(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const translate = (value) => translations[value.trim()] || value;
    if (Object.keys(translations).length) {
        document.title = document.title.split(' | ').map(translate).join(' | ');
        document.querySelectorAll('[title], [placeholder], [aria-label]').forEach((element) => {
            ['title', 'placeholder', 'aria-label'].forEach((attribute) => {
                if (element.hasAttribute(attribute)) element.setAttribute(attribute, translate(element.getAttribute(attribute)));
            });
        });
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        const nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        nodes.forEach((node) => {
            const parent = node.parentElement;
            if (!parent || parent.closest('.content, .rich-editor, script, style, textarea, [data-no-translate]')) return;
            node.nodeValue = translate(node.nodeValue);
        });
    }
});
</script>
