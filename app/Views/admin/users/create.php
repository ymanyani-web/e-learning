<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create User</title>
    <style>
        body{font-family:Arial,sans-serif;margin:40px;background:#f8fafc}
        form{max-width:520px;background:white;padding:24px;border-radius:10px}
        input,select,button{box-sizing:border-box;width:100%;padding:11px;margin:7px 0 15px}
        .error{background:#fee2e2;padding:10px;margin-bottom:12px}
    </style>
</head>
<body>
<p><a href="<?= htmlspecialchars(\App\Core\Request::basePath() . '/admin/users') ?>">← Users</a></p>
<h1>Create User</h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="post" action="<?= htmlspecialchars(\App\Core\Request::basePath() . '/admin/users') ?>">
    <?= \App\Core\Csrf::field() ?>
    <label>First name</label>
    <input type="text" name="first_name" required>

    <label>Last name</label>
    <input type="text" name="last_name" required>

    <label>Email</label>
    <input type="email" name="email" required>

    <label>Password</label>
    <input type="password" name="password" minlength="8" required>

    <label>Role</label>
    <select name="role">
        <option value="STUDENT">Student</option>
        <option value="TEACHER">Teacher</option>
        <option value="ADMIN">Admin</option>
    </select>

    <button type="submit">Create user</button>
</form>
</body>
</html>
