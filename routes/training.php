<?php

/*
|--------------------------------------------------------------------------
| Training Institute Routes  (Sprint 2 · Wave 4a)
|--------------------------------------------------------------------------
| Decisions: D-023, D-025, D-046, D-057, D-058, D-059.
|
| REGISTER in bootstrap/app.php withRouting(then: ...):
|     Route::middleware('api')->group(base_path('routes/training.php'));
|
| Access is ENROLLMENT-gated (D-057) — NOT AccountScope/ContentAccessService/
| HasAssignmentVisibility. Catalogue + certificate verification are public. Lesson content,
| assessments, and learner data require auth + enrollment. Certificate verification is public
| + minimal-disclosure (D-059). Lifecycle events audited under TRAINING_MANAGEMENT (D-058).
*/

use App\Http\Controllers\Training\Admin\CourseController;
use App\Http\Controllers\Training\Admin\InstructorController;
use App\Http\Controllers\Training\AssessmentController;
use App\Http\Controllers\Training\CertificateController;
use App\Http\Controllers\Training\CourseCatalogController;
use App\Http\Controllers\Training\EnrollmentController;
use App\Http\Controllers\Training\TrainingReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/training')->group(function () {

    // Public: catalogue + certificate verification (no auth)
    Route::get('courses', [CourseCatalogController::class, 'index']);
    Route::get('courses/{slug}', [CourseCatalogController::class, 'show']);
    Route::get('certificates/verify/{number}', [CertificateController::class, 'verify'])
        ->middleware('throttle:public-forms'); // D-059 §2 anti-enumeration

    // Authenticated learner + staff
    Route::middleware('auth:sanctum')->group(function () {
        // Enrollment + enrollment-gated content
        Route::post('courses/{course}/enrol', [EnrollmentController::class, 'enrol']);
        Route::get('my/courses', [EnrollmentController::class, 'myCourses']);
        Route::get('courses/{course}/lessons/{lesson}', [EnrollmentController::class, 'viewLesson']);
        Route::post('courses/{course}/lessons/{lesson}/complete', [EnrollmentController::class, 'completeLesson']);

        // Assessments (W4-5)
        Route::get('assessments/{assessment}', [AssessmentController::class, 'show']);
        Route::post('assessments/{assessment}/submit', [AssessmentController::class, 'submit']);
        Route::post('submissions/{submission}/grade', [AssessmentController::class, 'grade']);

        // Certificates (D-059)
        Route::get('my/certificates', [CertificateController::class, 'mine']);
        Route::get('certificates/{certificate}/download', [CertificateController::class, 'download']);
        Route::post('certificates/{certificate}/revoke', [CertificateController::class, 'revoke']);   // staff
        Route::post('certificates/{certificate}/reissue', [CertificateController::class, 'reissue']); // staff

        // Instructor onboarding
        Route::post('instructors/apply', [InstructorController::class, 'apply']);
        Route::get('admin/instructors', [InstructorController::class, 'index']);
        Route::post('admin/instructors/{instructor}/approve', [InstructorController::class, 'approve']);

        // Admin course management
        Route::get('admin/courses', [CourseController::class, 'index']);
        Route::post('admin/courses', [CourseController::class, 'store']);
        Route::put('admin/courses/{course}', [CourseController::class, 'update']);
        Route::post('admin/courses/{course}/publish', [CourseController::class, 'publish']);
        Route::post('admin/courses/{course}/lessons', [CourseController::class, 'addLesson']);

        // Analytics
        Route::get('reports', [TrainingReportController::class, 'index']);
    });
});
