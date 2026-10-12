ALTER TABLE users
    ADD COLUMN phone VARCHAR(40) NULL AFTER email,
    ADD COLUMN personal_email VARCHAR(255) NULL AFTER phone,
    ADD COLUMN birth_date DATE NULL AFTER personal_email,
    ADD COLUMN preferred_language VARCHAR(5) NOT NULL DEFAULT 'en' AFTER birth_date;
