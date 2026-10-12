<!DOCTYPE html>
<html lang="<?= htmlspecialchars(\App\Core\Locale::language()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(\App\Core\Locale::text('Login - E-Learning')) ?></title>
    <style>
        body{font-family:Arial,sans-serif;background:#f3f4f6;margin:0;min-height:100vh;display:grid;place-items:center}
        .card{width:min(420px,90%);background:white;padding:28px;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.08)}
        input,button{box-sizing:border-box;width:100%;padding:12px;margin-top:8px;margin-bottom:14px}
        input{border:1px solid #ccc;border-radius:7px}
        button{border:0;border-radius:7px;background:#111827;color:white;cursor:pointer}
        .error{background:#fee2e2;padding:10px;border-radius:6px}
        .success{background:#dcfce7;padding:10px;border-radius:6px}
    </style>
</head>
<body>
<div class="card">
    <h1>E-Learning</h1>
    <p><?= htmlspecialchars(\App\Core\Locale::text('Sign in to your account.')) ?></p>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars(\App\Core\Request::basePath() . '/login') ?>">
        <label><?= htmlspecialchars(\App\Core\Locale::text('Email')) ?></label>
        <input type="email" name="email" required autocomplete="email">

        <label><?= htmlspecialchars(\App\Core\Locale::text('Password')) ?></label>
        <input type="password" name="password" required autocomplete="current-password">

        <button type="submit"><?= htmlspecialchars(\App\Core\Locale::text('Login')) ?></button>
    </form>
</div>
</body>
</html>
