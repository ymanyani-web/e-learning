-- =========================================================
-- E-LEARNING DATABASE SCHEMA
-- MySQL 8+
-- =========================================================

CREATE DATABASE IF NOT EXISTS elearning
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE elearning;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS attendance_records;
DROP TABLE IF EXISTS attendance_sessions;
DROP TABLE IF EXISTS grades;
DROP TABLE IF EXISTS exam_answers;
DROP TABLE IF EXISTS exam_attempts;
DROP TABLE IF EXISTS exam_options;
DROP TABLE IF EXISTS exam_questions;
DROP TABLE IF EXISTS exams;
DROP TABLE IF EXISTS assignment_submissions;
DROP TABLE IF EXISTS assignments;
DROP TABLE IF EXISTS course_resources;
DROP TABLE IF EXISTS course_chapters;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS school_classes;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- 1. USERS
-- =========================================================

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(40) NULL,
    personal_email VARCHAR(255) NULL,
    birth_date DATE NULL,
    preferred_language VARCHAR(5) NOT NULL DEFAULT 'en',
    password VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'STUDENT',
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_users_email UNIQUE (email),
    CONSTRAINT chk_users_role CHECK (role IN ('ADMIN', 'TEACHER', 'STUDENT')),
    CONSTRAINT chk_users_status CHECK (status IN (0, 1))
) ENGINE=InnoDB;

CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_name ON users(last_name, first_name);

-- =========================================================
-- 2. SCHOOL CLASSES
-- =========================================================

CREATE TABLE school_classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    academic_year VARCHAR(20) NOT NULL,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_school_classes UNIQUE (name, academic_year),
    CONSTRAINT chk_school_classes_status CHECK (status IN (0, 1))
) ENGINE=InnoDB;

CREATE INDEX idx_school_classes_year ON school_classes(academic_year);
CREATE INDEX idx_school_classes_status ON school_classes(status);

-- =========================================================
-- 3. ENROLLMENTS
-- =========================================================

CREATE TABLE enrollments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    enrolled_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE',

    CONSTRAINT fk_enrollments_student
        FOREIGN KEY (student_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_enrollments_class
        FOREIGN KEY (class_id)
        REFERENCES school_classes(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT uq_enrollment_student_class_year
        UNIQUE (student_id, class_id, academic_year),

    CONSTRAINT chk_enrollment_status
        CHECK (status IN ('ACTIVE', 'INACTIVE', 'COMPLETED', 'CANCELLED'))
) ENGINE=InnoDB;

CREATE INDEX idx_enrollments_student ON enrollments(student_id);
CREATE INDEX idx_enrollments_class ON enrollments(class_id);
CREATE INDEX idx_enrollments_year ON enrollments(academic_year);

-- =========================================================
-- 4. SUBJECTS
-- =========================================================

CREATE TABLE subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NULL,
    description TEXT NULL,
    coefficient DECIMAL(6,2) NOT NULL DEFAULT 1.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_subject_code UNIQUE (code),
    CONSTRAINT chk_subject_coefficient CHECK (coefficient >= 0)
) ENGINE=InnoDB;

CREATE INDEX idx_subjects_name ON subjects(name);

-- =========================================================
-- 5. COURSES
-- =========================================================

CREATE TABLE courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'DRAFT',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_courses_subject
        FOREIGN KEY (subject_id)
        REFERENCES subjects(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_courses_teacher
        FOREIGN KEY (teacher_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_courses_class
        FOREIGN KEY (class_id)
        REFERENCES school_classes(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT chk_course_status
        CHECK (status IN ('DRAFT', 'PUBLISHED', 'ARCHIVED'))
) ENGINE=InnoDB;

CREATE INDEX idx_courses_subject ON courses(subject_id);
CREATE INDEX idx_courses_teacher ON courses(teacher_id);
CREATE INDEX idx_courses_class ON courses(class_id);
CREATE INDEX idx_courses_status ON courses(status);

-- =========================================================
-- 6. COURSE CHAPTERS
-- =========================================================

CREATE TABLE course_chapters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NULL,
    position INT NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'DRAFT',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_course_chapters_course
        FOREIGN KEY (course_id)
        REFERENCES courses(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT chk_course_chapter_position CHECK (position >= 0),
    CONSTRAINT chk_course_chapter_status
        CHECK (status IN ('DRAFT', 'PUBLISHED', 'ARCHIVED'))
) ENGINE=InnoDB;

CREATE INDEX idx_course_chapters_course ON course_chapters(course_id);
CREATE INDEX idx_course_chapters_position ON course_chapters(course_id, position);

-- =========================================================
-- 7. COURSE RESOURCES
-- =========================================================

CREATE TABLE course_resources (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chapter_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'OTHER',
    file_path VARCHAR(500) NULL,
    url VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_course_resources_chapter
        FOREIGN KEY (chapter_id)
        REFERENCES course_chapters(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT chk_course_resource_type
        CHECK (type IN ('PDF', 'VIDEO', 'IMAGE', 'DOCUMENT', 'LINK', 'OTHER'))
) ENGINE=InnoDB;

CREATE INDEX idx_course_resources_chapter ON course_resources(chapter_id);
CREATE INDEX idx_course_resources_type ON course_resources(type);

-- =========================================================
-- 8. ASSIGNMENTS
-- =========================================================

CREATE TABLE assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT NULL,
    max_score DECIMAL(7,2) NOT NULL DEFAULT 20.00,
    due_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_assignments_course
        FOREIGN KEY (course_id)
        REFERENCES courses(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT chk_assignment_max_score CHECK (max_score >= 0)
) ENGINE=InnoDB;

CREATE INDEX idx_assignments_course ON assignments(course_id);
CREATE INDEX idx_assignments_due_at ON assignments(due_at);

-- =========================================================
-- 9. ASSIGNMENT SUBMISSIONS
-- =========================================================

CREATE TABLE assignment_submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(500) NULL,
    comment TEXT NULL,
    submitted_at DATETIME NULL,
    score DECIMAL(7,2) NULL,
    teacher_feedback TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'SUBMITTED',

    CONSTRAINT fk_assignment_submissions_assignment
        FOREIGN KEY (assignment_id)
        REFERENCES assignments(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_assignment_submissions_student
        FOREIGN KEY (student_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT uq_assignment_student
        UNIQUE (assignment_id, student_id),

    CONSTRAINT chk_assignment_submission_score CHECK (score IS NULL OR score >= 0),

    CONSTRAINT chk_assignment_submission_status
        CHECK (status IN ('SUBMITTED', 'LATE', 'GRADED'))
) ENGINE=InnoDB;

CREATE INDEX idx_assignment_submissions_student ON assignment_submissions(student_id);
CREATE INDEX idx_assignment_submissions_status ON assignment_submissions(status);

-- =========================================================
-- 10. EXAMS
-- =========================================================

CREATE TABLE exams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT NULL,
    duration_minutes INT NOT NULL DEFAULT 60,
    total_points DECIMAL(7,2) NOT NULL DEFAULT 20.00,
    max_attempts INT NOT NULL DEFAULT 1,
    start_at DATETIME NULL,
    end_at DATETIME NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'DRAFT',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_exams_course
        FOREIGN KEY (course_id)
        REFERENCES courses(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT chk_exam_duration CHECK (duration_minutes > 0),
    CONSTRAINT chk_exam_total_points CHECK (total_points >= 0),
    CONSTRAINT chk_exam_max_attempts CHECK (max_attempts > 0),
    CONSTRAINT chk_exam_status
        CHECK (status IN ('DRAFT', 'PUBLISHED', 'CLOSED', 'ARCHIVED')),
    CONSTRAINT chk_exam_dates CHECK (
        end_at IS NULL OR start_at IS NULL OR end_at >= start_at
    )
) ENGINE=InnoDB;

CREATE INDEX idx_exams_course ON exams(course_id);
CREATE INDEX idx_exams_status ON exams(status);
CREATE INDEX idx_exams_dates ON exams(start_at, end_at);

-- =========================================================
-- 11. EXAM QUESTIONS
-- =========================================================

CREATE TABLE exam_questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_id INT UNSIGNED NOT NULL,
    question LONGTEXT NOT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'MULTIPLE_CHOICE',
    points DECIMAL(7,2) NOT NULL DEFAULT 1.00,
    position INT NOT NULL DEFAULT 0,

    CONSTRAINT fk_exam_questions_exam
        FOREIGN KEY (exam_id)
        REFERENCES exams(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT chk_exam_question_type
        CHECK (type IN ('MULTIPLE_CHOICE', 'TRUE_FALSE', 'TEXT')),
    CONSTRAINT chk_exam_question_points CHECK (points >= 0),
    CONSTRAINT chk_exam_question_position CHECK (position >= 0)
) ENGINE=InnoDB;

CREATE INDEX idx_exam_questions_exam ON exam_questions(exam_id);
CREATE INDEX idx_exam_questions_position ON exam_questions(exam_id, position);

-- =========================================================
-- 12. EXAM OPTIONS
-- =========================================================

CREATE TABLE exam_options (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id INT UNSIGNED NOT NULL,
    option_text TEXT NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,

    CONSTRAINT fk_exam_options_question
        FOREIGN KEY (question_id)
        REFERENCES exam_questions(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT chk_exam_option_correct CHECK (is_correct IN (0, 1))
) ENGINE=InnoDB;

CREATE INDEX idx_exam_options_question ON exam_options(question_id);

-- =========================================================
-- 13. EXAM ATTEMPTS
-- =========================================================

CREATE TABLE exam_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    attempt_number INT NOT NULL DEFAULT 1,
    started_at DATETIME NULL,
    submitted_at DATETIME NULL,
    score DECIMAL(7,2) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'IN_PROGRESS',

    CONSTRAINT fk_exam_attempts_exam
        FOREIGN KEY (exam_id)
        REFERENCES exams(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_exam_attempts_student
        FOREIGN KEY (student_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT uq_exam_attempt
        UNIQUE (exam_id, student_id, attempt_number),

    CONSTRAINT chk_exam_attempt_number CHECK (attempt_number > 0),
    CONSTRAINT chk_exam_attempt_score CHECK (score IS NULL OR score >= 0),
    CONSTRAINT chk_exam_attempt_status
        CHECK (status IN ('IN_PROGRESS', 'SUBMITTED', 'GRADED')),
    CONSTRAINT chk_exam_attempt_dates CHECK (
        submitted_at IS NULL OR started_at IS NULL OR submitted_at >= started_at
    )
) ENGINE=InnoDB;

CREATE INDEX idx_exam_attempts_exam ON exam_attempts(exam_id);
CREATE INDEX idx_exam_attempts_student ON exam_attempts(student_id);
CREATE INDEX idx_exam_attempts_status ON exam_attempts(status);

-- =========================================================
-- 14. EXAM ANSWERS
-- =========================================================

CREATE TABLE exam_answers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    option_id INT UNSIGNED NULL,
    text_answer LONGTEXT NULL,
    points_awarded DECIMAL(7,2) NULL,

    CONSTRAINT fk_exam_answers_attempt
        FOREIGN KEY (attempt_id)
        REFERENCES exam_attempts(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_exam_answers_question
        FOREIGN KEY (question_id)
        REFERENCES exam_questions(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_exam_answers_option
        FOREIGN KEY (option_id)
        REFERENCES exam_options(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT uq_exam_answer
        UNIQUE (attempt_id, question_id),

    CONSTRAINT chk_exam_answer_points CHECK (
        points_awarded IS NULL OR points_awarded >= 0
    )
) ENGINE=InnoDB;

CREATE INDEX idx_exam_answers_attempt ON exam_answers(attempt_id);
CREATE INDEX idx_exam_answers_question ON exam_answers(question_id);

-- =========================================================
-- 15. GRADES
-- =========================================================

CREATE TABLE grades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    type VARCHAR(30) NOT NULL,
    reference_id INT UNSIGNED NULL,
    score DECIMAL(7,2) NOT NULL,
    max_score DECIMAL(7,2) NOT NULL DEFAULT 20.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_grades_student
        FOREIGN KEY (student_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_grades_course
        FOREIGN KEY (course_id)
        REFERENCES courses(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT chk_grade_type
        CHECK (type IN ('EXAM', 'ASSIGNMENT', 'MANUAL', 'PARTICIPATION', 'PROJECT')),
    CONSTRAINT chk_grade_score CHECK (score >= 0),
    CONSTRAINT chk_grade_max_score CHECK (max_score > 0)
) ENGINE=InnoDB;

CREATE INDEX idx_grades_student ON grades(student_id);
CREATE INDEX idx_grades_course ON grades(course_id);
CREATE INDEX idx_grades_type ON grades(type);
CREATE INDEX idx_grades_reference ON grades(type, reference_id);

-- =========================================================
-- 16. ATTENDANCE SESSIONS
-- =========================================================

CREATE TABLE attendance_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_attendance_sessions_course
        FOREIGN KEY (course_id)
        REFERENCES courses(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_attendance_sessions_creator
        FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_attendance_session_time
        CHECK (end_time > start_time)
) ENGINE=InnoDB;

CREATE INDEX idx_attendance_sessions_course ON attendance_sessions(course_id);
CREATE INDEX idx_attendance_sessions_date ON attendance_sessions(session_date);

-- =========================================================
-- 17. ATTENDANCE RECORDS
-- =========================================================

CREATE TABLE attendance_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attendance_session_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'PRESENT',
    comment TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_attendance_records_session
        FOREIGN KEY (attendance_session_id)
        REFERENCES attendance_sessions(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_attendance_records_student
        FOREIGN KEY (student_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT uq_attendance_record
        UNIQUE (attendance_session_id, student_id),

    CONSTRAINT chk_attendance_status
        CHECK (status IN ('PRESENT', 'ABSENT', 'LATE', 'EXCUSED'))
) ENGINE=InnoDB;

CREATE INDEX idx_attendance_records_student ON attendance_records(student_id);
CREATE INDEX idx_attendance_records_status ON attendance_records(status);

-- =========================================================
-- 18. NOTIFICATIONS
-- =========================================================

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,

    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT chk_notifications_read CHECK (is_read IN (0, 1))
) ENGINE=InnoDB;

CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_unread ON notifications(user_id, is_read);
CREATE INDEX idx_notifications_created ON notifications(created_at);

-- =========================================================
-- OPTIONAL DEFAULT ADMIN
-- Password must be generated with PHP password_hash().
-- Example:
-- INSERT INTO users(first_name,last_name,email,password,role,status)
-- VALUES ('Admin','System','admin@example.com','$2y$...', 'ADMIN', 1);
-- =========================================================