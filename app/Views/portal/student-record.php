<?php $title = h($student['first_name'] . ' ' . $student['last_name']) . ' | LearnSpace'; require __DIR__ . '/_header.php'; ?>
<section class="hero">
    <div><a class="muted" href="/admin#students">← Learner records</a><h1><?= h($student['first_name'] . ' ' . $student['last_name']) ?></h1><p class="muted"><?= h($student['email']) ?></p></div>
</section>

<div class="grid">
    <section class="card">
        <h2>Class enrolments</h2>
        <?php if (!$enrollments): ?><p class="empty">No class enrolments.</p><?php else: ?><?php foreach ($enrollments as $enrollment): ?><div class="item"><strong><?= h($enrollment['name']) ?></strong><div class="muted"><?= h($enrollment['academic_year']) ?> · <?= h($enrollment['status']) ?></div></div><?php endforeach; ?><?php endif; ?>
    </section>
    <section class="card">
        <h2>Attendance overview</h2>
        <?php if (!$attendance): ?><p class="empty">No attendance has been recorded.</p><?php else: ?><?php foreach ($attendance as $record): ?><div class="item"><strong><?= h($record['course_title']) ?></strong><div><span class="pill"><?= h($record['status']) ?></span> <span class="muted"><?= h($record['session_date']) ?></span></div><?php if ($record['comment']): ?><div class="muted"><?= h($record['comment']) ?></div><?php endif; ?></div><?php endforeach; ?><?php endif; ?>
    </section>
    <section class="card wide">
        <h2>Grades and marks</h2>
        <?php if (!$grades): ?><p class="empty">No grades have been recorded.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Course</th><th>Type</th><th>Mark</th><th>Percentage</th><th>Recorded</th></tr></thead><tbody><?php foreach ($grades as $grade): ?><tr><td><?= h($grade['course_title']) ?></td><td><?= h($grade['type']) ?></td><td><?= h($grade['score']) ?> / <?= h($grade['max_score']) ?></td><td><?= h(round(((float) $grade['score'] / (float) $grade['max_score']) * 100, 1)) ?>%</td><td><?= h($grade['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
