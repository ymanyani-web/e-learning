<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Users</title>
    <style>
        body{font-family:Arial,sans-serif;margin:40px;background:#f8fafc}
        table{width:100%;border-collapse:collapse;background:white}
        th,td{padding:12px;border-bottom:1px solid #e5e7eb;text-align:left}
        .success{background:#dcfce7;padding:10px;margin:12px 0}
        .error{background:#fee2e2;padding:10px;margin:12px 0}
    </style>
</head>
<body>
<p><a href="<?= htmlspecialchars(\App\Core\Request::basePath() . '/admin') ?>">← Dashboard</a> | <a href="<?= htmlspecialchars(\App\Core\Request::basePath() . '/admin/users/create') ?>">+ Create user</a> | <a href="<?= htmlspecialchars(\App\Core\Request::basePath() . '/logout') ?>">Logout</a></p>
<h1>Users</h1>

<?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<table>
    <thead>
    <tr>
        <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $item): ?>
        <tr>
            <td><?= (int) $item->getId() ?></td>
            <td><?= htmlspecialchars($item->getFullName()) ?></td>
            <td><?= htmlspecialchars($item->getEmail()) ?></td>
            <td><?= htmlspecialchars($item->getRole()) ?></td>
            <td><?= $item->isActive() ? 'Active' : 'Inactive' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
