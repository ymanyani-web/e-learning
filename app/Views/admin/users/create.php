<?php $title = 'Create person | LearnSpace'; require dirname(__DIR__, 2) . '/portal/_header.php'; ?>
<section class="hero">
    <div><a class="muted" href="/admin/users">← People</a><h1>Create an account</h1><p class="muted">Add a student, professor, or another administrator.</p></div>
</section>

<section class="card" style="max-width:620px">
    <form method="post" action="/admin/users">
        <?= \App\Core\Csrf::field() ?>
        <label>First name <input type="text" name="first_name" required autocomplete="given-name"></label>
        <label>Last name <input type="text" name="last_name" required autocomplete="family-name"></label>
        <label>Email <input type="email" name="email" required autocomplete="email"></label>
        <label>Temporary password <input type="password" name="password" minlength="8" required autocomplete="new-password"></label>
        <label>Role
            <select name="role">
                <option value="STUDENT" <?= $selectedRole === 'STUDENT' ? 'selected' : '' ?>>Student</option>
                <option value="TEACHER" <?= $selectedRole === 'TEACHER' ? 'selected' : '' ?>>Professor</option>
                <option value="ADMIN" <?= $selectedRole === 'ADMIN' ? 'selected' : '' ?>>Administrator</option>
            </select>
        </label>
        <button type="submit">Create account</button>
    </form>
</section>
<?php require dirname(__DIR__, 2) . '/portal/_footer.php'; ?>
