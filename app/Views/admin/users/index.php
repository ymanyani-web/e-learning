<?php
$title = 'People | LearnSpace';
$message = $success ?? null;
require dirname(__DIR__, 2) . '/portal/_header.php';
?>
<section class="hero">
    <div><p class="pill">PEOPLE</p><h1>Students, professors, and administrators</h1><p class="muted">Create and review every account that can access your school workspace.</p></div>
    <div class="row"><a class="button secondary" href="/admin/users/create?role=STUDENT">Add student</a><a class="button" href="/admin/users/create?role=TEACHER">Add professor</a></div>
</section>

<section class="card">
    <div class="section-heading"><div><h2>All people</h2><p class="muted"><?= count($users) ?> account<?= count($users) === 1 ? '' : 's' ?></p></div></div>
    <?php if (!$users): ?><p class="empty">No user accounts found.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr></thead><tbody><?php foreach ($users as $item): ?><tr><td><strong><?= h($item->getFullName()) ?></strong></td><td><?= h($item->getEmail()) ?></td><td><span class="pill"><?= h($item->getRole()) ?></span></td><td><?= $item->isActive() ? 'Active' : 'Inactive' ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
<?php require dirname(__DIR__, 2) . '/portal/_footer.php'; ?>
