<?php
$title = \App\Core\Locale::text('Settings | LearnSpace');
require __DIR__ . '/_header.php';
?>
<section class="page-hero">
    <div><p class="eyebrow"><?= \App\Core\Locale::text('ACCOUNT') ?></p><h1><?= \App\Core\Locale::text('settings') ?></h1><p><?= \App\Core\Locale::text('Manage your profile, language, and password.') ?></p></div>
</section>

<div class="admin-grid">
    <section class="card">
        <h2><?= \App\Core\Locale::text('Personal details') ?></h2>
        <form method="post" action="/settings">
            <?= \App\Core\Csrf::field() ?>
            <label><?= \App\Core\Locale::text('First name') ?> <input value="<?= h($profile['first_name'] ?? '') ?>" disabled></label>
            <label><?= \App\Core\Locale::text('Last name') ?> <input value="<?= h($profile['last_name'] ?? '') ?>" disabled></label>
            <label><?= \App\Core\Locale::text('School email') ?> <input value="<?= h($profile['email'] ?? '') ?>" disabled></label>
            <label><?= \App\Core\Locale::text('Phone') ?> <input type="tel" name="phone" value="<?= h($profile['phone'] ?? '') ?>" autocomplete="tel"></label>
            <label><?= \App\Core\Locale::text('Personal email') ?> <input type="email" name="personal_email" value="<?= h($profile['personal_email'] ?? '') ?>" autocomplete="email"></label>
            <label><?= \App\Core\Locale::text('Birth date') ?> <input type="date" name="birth_date" value="<?= h($profile['birth_date'] ?? '') ?>" autocomplete="bday"></label>
            <label><?= \App\Core\Locale::text('Preferred language') ?>
                <select name="preferred_language">
                    <option value="en" <?= ($profile['preferred_language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
                    <option value="fr" <?= ($profile['preferred_language'] ?? 'en') === 'fr' ? 'selected' : '' ?>>Français</option>
                </select>
            </label>
            <button type="submit"><?= \App\Core\Locale::text('save_changes') ?></button>
        </form>
    </section>
    <section class="card">
        <h2><?= \App\Core\Locale::text('Password') ?></h2>
        <p class="muted"><?= \App\Core\Locale::text('Leave these fields blank to keep your current password.') ?></p>
        <form method="post" action="/settings">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="preferred_language" value="<?= h($profile['preferred_language'] ?? 'en') ?>">
            <input type="hidden" name="phone" value="<?= h($profile['phone'] ?? '') ?>">
            <input type="hidden" name="personal_email" value="<?= h($profile['personal_email'] ?? '') ?>">
            <input type="hidden" name="birth_date" value="<?= h($profile['birth_date'] ?? '') ?>">
            <label><?= \App\Core\Locale::text('New password') ?> <input type="password" name="password" minlength="8" autocomplete="new-password"></label>
            <label><?= \App\Core\Locale::text('Confirm new password') ?> <input type="password" name="password_confirmation" minlength="8" autocomplete="new-password"></label>
            <button type="submit"><?= \App\Core\Locale::text('Update password') ?></button>
        </form>
    </section>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
