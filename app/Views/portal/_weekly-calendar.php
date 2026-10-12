<?php
$weekStart = (new \DateTimeImmutable('monday this week'))->setTime(0, 0);
$calendarEventLink = $calendarEventLink ?? null;
?>
<div class="time-calendar">
    <div class="time-scale"><?php for ($hour = 8; $hour <= 18; $hour++): ?><span><?= sprintf('%02d:00', $hour) ?></span><?php endfor; ?></div>
    <div class="time-days"><?php for ($offset = 0; $offset < 7; $offset++): ?><?php $day = $weekStart->modify("+{$offset} days"); $date = $day->format('Y-m-d'); ?>
        <div class="time-day"><header><strong><?= h($day->format('D')) ?></strong><span><?= h($day->format('d M')) ?></span></header><div class="time-day-body">
            <?php foreach ($calendarWeek as $event): ?><?php if ($event['event_date'] !== $date) continue; $start = explode(':', $event['start_time']); $end = explode(':', $event['end_time']); $startMinutes = (int) $start[0] * 60 + (int) $start[1]; $endMinutes = (int) $end[0] * 60 + (int) $end[1]; $top = max(0, $startMinutes - 480); $height = max(28, $endMinutes - $startMinutes); $href = $calendarEventLink ? $calendarEventLink($event) : null; ?>
                <?php if ($href): ?><a class="time-event time-event-link <?= $event['event_type'] === 'EXAM' ? 'exam' : '' ?>" style="top:<?= $top ?>px;height:<?= $height ?>px" href="<?= h($href) ?>"><?php else: ?><div class="time-event <?= $event['event_type'] === 'EXAM' ? 'exam' : '' ?>" style="top:<?= $top ?>px;height:<?= $height ?>px"><?php endif; ?>
                    <small><?= h(substr((string) $event['start_time'], 0, 5)) ?>–<?= h(substr((string) $event['end_time'], 0, 5)) ?></small><strong data-no-translate><?= h($event['course_title']) ?></strong><span><?= h($event['event_type']) ?></span>
                <?= $href ? '</a>' : '</div>' ?>
            <?php endforeach; ?>
        </div></div>
    <?php endfor; ?></div>
</div>
