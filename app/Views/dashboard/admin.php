<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <style>
        body{font-family:Arial,sans-serif;margin:40px;background:#f8fafc}
        nav a{margin-right:14px}
        .card{background:white;padding:22px;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.06)}
    </style>
</head>
<body>
<nav>
    <a href="/admin/users">Users</a>
    <a href="/logout">Logout</a>
</nav>
<div class="card">
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>.</p>
    <p>Role: <strong><?= htmlspecialchars($user['role']) ?></strong></p>
</div>
</body>
</html>
