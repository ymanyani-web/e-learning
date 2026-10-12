ALTER TABLE exams
    ADD COLUMN passing_score DECIMAL(7,2) NOT NULL DEFAULT 0.00 AFTER total_points;

ALTER TABLE exam_questions
    ADD COLUMN allow_multiple TINYINT(1) NOT NULL DEFAULT 0 AFTER type;

ALTER TABLE exam_answers
    ADD COLUMN selected_option_ids JSON NULL AFTER option_id;
