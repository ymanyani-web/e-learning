ALTER TABLE courses
    ADD COLUMN start_date DATE NULL AFTER description,
    ADD COLUMN end_date DATE NULL AFTER start_date;

CREATE TABLE course_schedule_slots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    weekday VARCHAR(10) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    CONSTRAINT fk_course_schedule_slots_course
        FOREIGN KEY (course_id)
        REFERENCES courses(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT chk_course_schedule_weekday
        CHECK (weekday IN ('MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY')),
    CONSTRAINT chk_course_schedule_time
        CHECK (end_time > start_time),
    CONSTRAINT uq_course_schedule_slot
        UNIQUE (course_id, weekday, start_time)
);
