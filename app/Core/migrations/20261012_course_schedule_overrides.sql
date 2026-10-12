CREATE TABLE course_schedule_overrides (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_schedule_slot_id INT UNSIGNED NOT NULL,
    occurrence_date DATE NOT NULL,
    override_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_course_schedule_overrides_slot FOREIGN KEY (course_schedule_slot_id) REFERENCES course_schedule_slots(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_course_schedule_override_time CHECK (end_time > start_time),
    CONSTRAINT uq_course_schedule_override UNIQUE (course_schedule_slot_id, occurrence_date)
);

CREATE INDEX idx_course_schedule_overrides_date ON course_schedule_overrides(override_date);
