<?php use App\Core\Csrf; $title = h($course['title']) . ' | LearnSpace'; require __DIR__ . '/_header.php'; ?>
<a href="/student/courses" class="back-link">← My courses</a><h1 data-no-translate><?= h($course['title']) ?></h1><p class="muted" data-no-translate><?= h($course['subject_name']) ?></p>
<div class="grid">
<section class="card wide"><h2>Slides</h2><?php if (!$chapters): ?><p class="muted">Slides will appear here when your professor publishes them.</p><?php endif; ?><?php foreach ($chapters as $index => $chapter): ?><article class="item course-slide" id="slide-<?= $chapter['id'] ?>" data-chapter-id="<?= $chapter['id'] ?>"><h3><span data-no-translate><?= h($chapter['position']) ?>. <?= h($chapter['title']) ?></span> <?php if (in_array($chapter['id'], $completedChapters, true)): ?><span class="pill">Completed</span><?php endif; ?></h3><div class="content"><?= \App\Core\ContentSanitizer::sanitize((string) $chapter['content']) ?></div><?php foreach ($resources as $resource): ?><?php $mediaSource = $resource['url'] ?: \App\Core\Request::basePath() . '/' . ltrim((string) $resource['file_path'], '/'); ?><?php if ($resource['chapter_id'] === $chapter['id'] && $resource['type'] === 'IMAGE'): ?><img class="course-media" src="<?= h($mediaSource) ?>" alt="<?= h($resource['title']) ?>"><?php elseif ($resource['chapter_id'] === $chapter['id'] && $resource['type'] === 'VIDEO'): ?><video class="course-media" controls src="<?= h($mediaSource) ?>"></video><?php endif; ?><?php endforeach; ?><div class="row slide-actions"><button class="secondary button-icon ai-help" type="button" data-chapter-id="<?= $chapter['id'] ?>"><?= icon('open') ?> I did not understand</button><?php if (isset($chapters[$index + 1])): ?><button class="button button-icon next-slide" type="button" data-chapter-id="<?= $chapter['id'] ?>" data-next-id="<?= $chapters[$index + 1]['id'] ?>">Next slide <?= icon('open') ?></button><?php else: ?><button class="button button-icon complete-course" type="button" data-chapter-id="<?= $chapter['id'] ?>">Complete course <?= icon('open') ?></button><?php endif; ?></div></article><?php endforeach; ?></section>
<section class="card"><h2>Exams</h2><?php if (!$exams): ?><p class="muted">No published exams.</p><?php endif; ?><?php foreach ($exams as $exam): ?><div class="item"><strong data-no-translate><?= h($exam['title']) ?></strong><p class="muted"><?= h($exam['delivery_mode']) ?> · <?= h($exam['start_at']) ?> to <?= h($exam['end_at']) ?></p><?php if ($exam['delivery_mode'] === 'MANUAL'): ?><span class="pill">Oral or paper assessment</span><?php elseif ($exam['is_available']): ?><a class="button" href="/student/exam?id=<?= $exam['id'] ?>">Take exam</a><?php else: ?><span class="pill">Not open at this time</span><?php endif; ?></div><?php endforeach; ?></section>
</div>
<div class="modal" id="slide-ai-help" role="dialog" aria-modal="true" aria-labelledby="slide-ai-help-title"><section class="modal-panel"><button class="modal-close" type="button" data-modal-close aria-label="Close">×</button><p class="eyebrow">AI LEARNING ASSISTANT</p><h2 id="slide-ai-help-title">What did you not understand?</h2><form id="slide-ai-form"><input type="hidden" id="ai-chapter-id"><label>Your question <textarea id="ai-question" required placeholder="Explain what is unclear in this slide."></textarea></label><button>Ask AI</button></form><div id="ai-answer" class="content" hidden></div></section></div>
<script>
const studentToken = <?= json_encode(Csrf::token()) ?>;
const studentBasePath = <?= json_encode(\App\Core\Request::basePath()) ?>;
const sendStudentRequest = async (path, fields) => {
    const body = new URLSearchParams({...fields, _token: studentToken});
    const response = await fetch(studentBasePath + path, {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body, keepalive: true});
    return response.json();
};
document.querySelectorAll('.next-slide, .complete-course').forEach((button) => button.addEventListener('click', async () => {
    const result = await sendStudentRequest('/student/progress', {chapter_id: button.dataset.chapterId, completed: '1'});
    if (!result.ok) return;
    const next = button.dataset.nextId ? document.getElementById('slide-' + button.dataset.nextId) : null;
    if (next) next.scrollIntoView({behavior: 'smooth', block: 'start'});
    else window.location.href = studentBasePath + '/student/courses';
}));
document.querySelectorAll('.ai-help').forEach((button) => button.addEventListener('click', () => {
    document.getElementById('ai-chapter-id').value = button.dataset.chapterId;
    document.getElementById('ai-answer').hidden = true;
    document.getElementById('slide-ai-help').classList.add('is-open');
}));
document.getElementById('slide-ai-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const answer = document.getElementById('ai-answer');
    const button = event.submitter || event.currentTarget.querySelector('button');
    const originalLabel = button.innerHTML;
    answer.hidden = false;
    answer.textContent = 'Thinking…';
    button.disabled = true;
    button.innerHTML = '<span class="ai-thinking">AI is thinking <i></i><i></i><i></i></span>';
    try {
        const result = await sendStudentRequest('/student/ask-ai/live', {chapter_id: document.getElementById('ai-chapter-id').value, question: document.getElementById('ai-question').value});
        answer.textContent = result.answer || result.error || 'The assistant could not answer right now.';
    } catch {
        answer.textContent = 'The assistant could not answer right now.';
    } finally {
        button.disabled = false;
        button.innerHTML = originalLabel;
    }
});
let leaveReported = false;
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState !== 'hidden' || leaveReported) return;
    leaveReported = true;
    sendStudentRequest('/student/activity/left', {course_id: <?= (int) $course['id'] ?>}).catch(() => {});
});
</script>
<?php require __DIR__ . '/_footer.php'; ?>
