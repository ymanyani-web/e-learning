<?php $title = 'My learning | LearnSpace'; require __DIR__ . '/_header.php'; ?>
<h1>My learning</h1><p class="muted">Continue with your published courses and assessments.</p>
<div class="grid"><?php if (!$courses): ?><section class="card"><p class="muted">You are not enrolled in a class with published courses yet.</p></section><?php endif; ?><?php foreach ($courses as $course): ?><section class="card"><span class="pill"><?= h($course['subject_name']) ?></span><h2 style="margin-top:9px"><?= h($course['title']) ?></h2><p><?= h($course['description']) ?></p><p class="muted">Professor: <?= h($course['teacher_name']) ?></p><a class="button" href="/student/course?id=<?= $course['id'] ?>">Open course</a></section><?php endforeach; ?></div>
<?php require __DIR__ . '/_footer.php'; ?>
