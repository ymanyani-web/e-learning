CREATE TABLE course_chapter_progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    chapter_id INT UNSIGNED NOT NULL,
    completed_at DATETIME NULL,
    last_viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_chapter_progress_student FOREIGN KEY (student_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_chapter_progress_chapter FOREIGN KEY (chapter_id) REFERENCES course_chapters(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT uq_chapter_progress_student_chapter UNIQUE (student_id, chapter_id)
) ENGINE=InnoDB;

CREATE INDEX idx_chapter_progress_student ON course_chapter_progress(student_id, last_viewed_at);
