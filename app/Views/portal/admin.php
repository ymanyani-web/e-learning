<?php use App\Core\Csrf; $title = 'Administrator | LearnSpace'; require __DIR__ . '/_header.php'; ?>
<section class="hero">
    <div><p class="pill">ADMIN WORKSPACE</p><h1>School administration</h1><p class="muted">Create people and classes, make course assignments, and follow learner progress from one place.</p></div>
    <div class="row"><a class="button secondary" href="/admin/users/create?role=STUDENT">Add student</a><a class="button" href="/admin/users/create?role=TEACHER">Add professor</a></div>
</section>

<section class="kpis">
    <div class="kpi"><span>Active learners</span><strong><?= $stats['students'] ?></strong></div>
    <div class="kpi"><span>Professors</span><strong><?= $stats['teachers'] ?></strong></div>
    <div class="kpi"><span>Active classes</span><strong><?= $stats['classes'] ?></strong></div>
    <div class="kpi"><span>Course assignments</span><strong><?= $stats['courses'] ?></strong></div>
</section>

<div class="grid">
    <section class="card"><h2>Create a class</h2><p class="muted">Set up a learning group before enrolment.</p><form method="post" action="/admin/classes"><?= Csrf::field() ?><input name="name" required placeholder="e.g. Grade 10 A"><input name="academic_year" required placeholder="e.g. 2026-2027"><textarea name="description" placeholder="Optional description"></textarea><button>Create class</button></form></section>
    <section class="card"><h2>Create a subject</h2><p class="muted">Subjects are used when assigning courses.</p><form method="post" action="/admin/subjects"><?= Csrf::field() ?><input name="name" required placeholder="e.g. Physics"><input name="code" placeholder="e.g. PHY-101"><input name="coefficient" type="number" min="0" step=".01" value="1"><textarea name="description" placeholder="Optional description"></textarea><button>Create subject</button></form></section>
    <section class="card"><h2>Enrol a learner</h2><p class="muted">Connect a student to an active class.</p><form method="post" action="/admin/enrollments"><?= Csrf::field() ?><select name="student_id" required><option value="">Select student</option><?php foreach ($students as $student): ?><option value="<?= $student['id'] ?>"><?= h($student['first_name'] . ' ' . $student['last_name']) ?></option><?php endforeach; ?></select><select name="class_id" required><option value="">Select class</option><?php foreach ($classes as $class): ?><option value="<?= $class['id'] ?>"><?= h($class['name'] . ' · ' . $class['academic_year']) ?></option><?php endforeach; ?></select><p class="muted">The academic year is taken from the selected class.</p><button>Enrol student</button></form></section>
    <section class="card"><h2>Assign a course</h2><p class="muted">Choose the class, subject, and professor.</p><form method="post" action="/admin/courses"><?= Csrf::field() ?><input name="title" required placeholder="Course title"><textarea name="description" placeholder="What will students learn?"></textarea><select name="subject_id" required><option value="">Select subject</option><?php foreach ($subjects as $subject): ?><option value="<?= $subject['id'] ?>"><?= h($subject['name']) ?></option><?php endforeach; ?></select><select name="teacher_id" required><option value="">Select professor</option><?php foreach ($teachers as $teacher): ?><option value="<?= $teacher['id'] ?>"><?= h($teacher['first_name'] . ' ' . $teacher['last_name']) ?></option><?php endforeach; ?></select><select name="class_id" required><option value="">Select class</option><?php foreach ($classes as $class): ?><option value="<?= $class['id'] ?>"><?= h($class['name']) ?></option><?php endforeach; ?></select><select name="status"><option value="DRAFT">Draft</option><option value="PUBLISHED">Published</option></select><button>Assign course</button></form></section>

    <section class="card wide">
        <div class="section-heading"><div><h2>Recent enrolments</h2><p class="muted">Remove a learner from a class when their assignment changes.</p></div></div>
        <?php if (!$enrollments): ?><p class="empty">No active enrolments yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Student</th><th>Class</th><th>Academic year</th><th></th></tr></thead><tbody><?php foreach ($enrollments as $enrollment): ?><tr><td><?= h($enrollment['student_name']) ?></td><td><?= h($enrollment['class_name']) ?></td><td><?= h($enrollment['academic_year']) ?></td><td><form class="compact-form" method="post" action="/admin/enrollments/remove"><?= Csrf::field() ?><input type="hidden" name="enrollment_id" value="<?= $enrollment['id'] ?>"><button class="danger" type="submit">Remove</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
    </section>

    <section class="card wide" id="students">
        <div class="section-heading"><div><h2>Learner records</h2><p class="muted">Review class assignments, grade averages, and attendance activity.</p></div><a class="button secondary" href="/admin/users">Manage people</a></div>
        <?php if (!$studentSummaries): ?><p class="empty">Add students to begin tracking learner records.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Learner</th><th>Classes</th><th>Grade average</th><th>Attendance entries</th><th></th></tr></thead><tbody><?php foreach ($studentSummaries as $student): ?><tr><td><strong><?= h($student['student_name']) ?></strong><br><span class="muted"><?= h($student['email']) ?></span></td><td><?= h($student['classes'] ?: 'Not enrolled') ?></td><td><?= $student['grade_average'] === null ? 'No grades yet' : h($student['grade_average']) . '%' ?></td><td><?= h($student['attendance_entries']) ?></td><td><a class="button secondary" href="/admin/students?id=<?= $student['id'] ?>">View record</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
    </section>

    <section class="card wide">
        <div class="section-heading"><div><h2>Course assignments</h2><p class="muted">Current responsibility across classes and professors.</p></div></div>
        <?php if (!$courses): ?><p class="empty">No courses assigned yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Course</th><th>Subject</th><th>Class</th><th>Professor</th><th>Status</th></tr></thead><tbody><?php foreach ($courses as $course): ?><tr><td><strong><?= h($course['title']) ?></strong></td><td><?= h($course['subject_name']) ?></td><td><?= h($course['class_name']) ?></td><td><?= h($course['teacher_name']) ?></td><td><span class="pill"><?= h($course['status']) ?></span></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
