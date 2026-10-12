# LearnSpace

Native PHP and MySQL e-learning application with three roles:

- **Administrator:** creates user profiles, classes, subjects, student enrollments, and course assignments.
- **Professor:** writes or AI-drafts lessons, publishes course content, creates exams, and generates multiple-choice questions with AI for review.
- **Student:** accesses enrolled published courses, asks AI for a simpler explanation of a lesson, and completes automatically graded multiple-choice exams.

## Requirements

- PHP 8.1+ with `pdo_mysql` and `curl` extensions
- MySQL 8+
- An OpenAI-compatible chat-completions API key for AI features

## Setup

1. Create the database and tables:

   ```sh
   mysql -u root -p < app/Core/schema.sql
   ```

2. Replace the placeholders in `config/database.php` with the database values
   shown in your hosting control panel. This file is outside `public/`, so it
   is not web-accessible. Environment variables override this file when
   available:

   ```text
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=elearning
   DB_USER=root
   DB_PASSWORD=your-password
   AI_API_KEY=your-provider-key
   AI_API_URL=https://api.openai.com/v1/chat/completions
   AI_MODEL=gpt-4o-mini
   ```

3. Serve the `public` directory as the web root. For local development:

   ```sh
   php -S localhost:8000 -t public
   ```

4. Import or create an initial `ADMIN` account directly in `users` (use `password_hash()` to generate its password hash), then sign in at `http://localhost:8000`.

The built-in fallback autoloader makes Composer optional. Do not expose `app`, `config`, or the project root through the web server.

## AI safety and teaching workflow

AI-generated lesson content and exam questions are drafts. Professors should verify factual accuracy, suitability, accessibility, and correct answers before publishing. The app sends only the selected lesson and the learner question to the configured AI provider for the simplification feature.
