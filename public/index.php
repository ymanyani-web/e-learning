<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\AdminController;
use App\Controllers\SettingsController;
use App\Controllers\TeacherController;
use App\Controllers\StudentController;
use App\Controllers\DashboardController;
use App\Controllers\UserController;
use App\Controllers\LearningController;
use App\Core\Auth;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    spl_autoload_register(function (string $class): void {
        $prefix = 'App\\';
        if (str_starts_with($class, $prefix)) {
            $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($path)) require $path;
        }
    });
}

Session::start();

$router = new Router();

$router->get('/', function (): void {
    if (!Auth::check()) {
        Response::redirect('/login');
    }

    match (Auth::role()) {
        'ADMIN' => Response::redirect('/admin'),
        'TEACHER' => Response::redirect('/teacher'),
        'STUDENT' => Response::redirect('/student'),
        default => Response::abort(403, 'Unknown role'),
    };
});

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/settings', [SettingsController::class, 'show']);
$router->post('/settings', [SettingsController::class, 'update']);

$router->get('/admin', [AdminController::class, 'overview']);
$router->get('/admin/students', [AdminController::class, 'students']);
$router->get('/admin/teachers', [AdminController::class, 'teachers']);
$router->get('/admin/classes', [AdminController::class, 'classes']);
$router->get('/admin/subjects', [AdminController::class, 'subjects']);
$router->post('/admin/classes', [LearningController::class, 'createClass']);
$router->post('/admin/subjects', [LearningController::class, 'createSubject']);
$router->post('/admin/courses', [LearningController::class, 'createCourse']);
$router->post('/admin/courses/schedule', [AdminController::class, 'updateCourseSchedule']);
$router->post('/admin/courses/cancel', [AdminController::class, 'cancelCourse']);
$router->post('/admin/exams/schedule', [AdminController::class, 'updateExamSchedule']);
$router->post('/admin/enrollments', [LearningController::class, 'enrollStudent']);
$router->post('/admin/enrollments/remove', [LearningController::class, 'removeEnrollment']);
$router->get('/teacher', [TeacherController::class, 'dashboard']);
$router->get('/teacher/classes', [TeacherController::class, 'classes']);
$router->get('/teacher/courses', [TeacherController::class, 'courses']);
$router->get('/teacher/course', [LearningController::class, 'courseEditor']);
$router->get('/teacher/exam/grading', [TeacherController::class, 'grading']);
$router->get('/teacher/exam/marks', [TeacherController::class, 'manualMarks']);
$router->post('/teacher/chapters', [LearningController::class, 'saveChapter']);
$router->post('/teacher/chapters/update', [LearningController::class, 'updateChapter']);
$router->post('/teacher/inline-image', [LearningController::class, 'uploadInlineImage']);
$router->post('/teacher/courses/ai', [LearningController::class, 'generateCourseSlides']);
$router->post('/teacher/exams', [LearningController::class, 'createExam']);
$router->post('/teacher/questions', [LearningController::class, 'saveQuestion']);
$router->post('/teacher/questions/ai', [LearningController::class, 'generateQuestions']);
$router->post('/teacher/exam/answers/grade', [LearningController::class, 'gradeTextAnswer']);
$router->post('/teacher/exam/marks', [TeacherController::class, 'saveManualMark']);
$router->post('/teacher/attendance/sessions', [TeacherController::class, 'createAttendanceSession']);
$router->post('/teacher/attendance/records', [TeacherController::class, 'updateAttendanceRecord']);
$router->post('/teacher/attendance/records/remove', [TeacherController::class, 'removeAttendanceRecord']);
$router->get('/student', [StudentController::class, 'dashboard']);
$router->get('/student/courses', [StudentController::class, 'courses']);
$router->get('/student/exams', [StudentController::class, 'exams']);
$router->get('/student/course', [LearningController::class, 'courseStudy']);
$router->post('/student/ask-ai', [LearningController::class, 'askAi']);
$router->post('/student/ask-ai/live', [StudentController::class, 'askAiLive']);
$router->post('/student/progress', [StudentController::class, 'saveProgress']);
$router->post('/student/activity/left', [StudentController::class, 'reportTabLeave']);
$router->get('/student/exam', [LearningController::class, 'takeExam']);
$router->post('/student/exam', [LearningController::class, 'submitExam']);
$router->get('/student/exam/result', [LearningController::class, 'examResult']);

$router->get('/admin/users', [UserController::class, 'index']);
$router->get('/admin/users/create', [UserController::class, 'create']);
$router->post('/admin/users', [UserController::class, 'store']);

$router->dispatch();
