<?php

use App\Http\Controllers\Admin\ConferenceValidationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Author\SubmissionController;
use App\Http\Controllers\Chair\ConferenceController;
use App\Http\Controllers\Chair\ReviewerController;
use App\Http\Controllers\Chair\SessionController;
use App\Http\Controllers\Chair\SubmissionController as ChairSubmissionController;
use App\Http\Controllers\Chair\SubmissionReviewerController;
use App\Http\Controllers\Chair\TopicController;
use App\Http\Controllers\ConferencePublicController;
use App\Http\Controllers\Reviewer\SubmissionReviewController;
use App\Http\Controllers\Reviewer\TopicController as ReviewerTopicController;
use App\Http\Controllers\SubmissionFileController;
use App\Http\Controllers\TopicPublicController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/conferences', [ConferencePublicController::class, 'index']);
Route::get('/conferences/{conference}', [ConferencePublicController::class, 'show']);
Route::get('/conferences/{conference}/topics', [TopicPublicController::class, 'index']);
Route::get('/conferences/{conference}/programme', [ConferencePublicController::class, 'programme']);

Route::middleware(['auth:sanctum', 'password.changed'])->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
    Route::put('/auth/me', [AuthController::class, 'updateProfile'])->name('auth.update-profile');
    Route::post('/auth/change-password', [AuthController::class, 'changePassword'])->name('auth.change-password');

    Route::get('/submissions/{submission}/download/{type}', [SubmissionFileController::class, 'download'])
        ->whereNumber('submission')
        ->whereIn('type', ['initial', 'revised', 'camera-ready']);

    Route::middleware('role:chair')->prefix('chair')->group(function (): void {
        Route::post('/conferences', [ConferenceController::class, 'store']);
        Route::put('/conferences/{conference}', [ConferenceController::class, 'update']);
        Route::get('/conference', [ConferenceController::class, 'mine']);
        Route::get('/dashboard', [ConferenceController::class, 'dashboard'])->middleware('chair.validated');

        Route::middleware('chair.validated')->scopeBindings()->group(function (): void {
            Route::get('/conferences/{conference}/topics', [TopicController::class, 'index']);
            Route::post('/conferences/{conference}/topics', [TopicController::class, 'store']);
            Route::put('/conferences/{conference}/topics/{topic}', [TopicController::class, 'update']);
            Route::delete('/conferences/{conference}/topics/{topic}', [TopicController::class, 'destroy']);
            Route::get('/conferences/{conference}/topics/{topic}/reviewers', [TopicController::class, 'assignedReviewers']);
            Route::post('/conferences/{conference}/topics/{topic}/reviewers', [TopicController::class, 'assignReviewer']);
            Route::delete('/conferences/{conference}/topics/{topic}/reviewers', [TopicController::class, 'unassignReviewer']);
            Route::get('/conferences/{conference}/submissions', [ChairSubmissionController::class, 'index']);
            Route::get('/conferences/{conference}/submissions/{submission}', [ChairSubmissionController::class, 'show']);
            Route::get('/conferences/{conference}/submissions/{submission}/reviewers', [SubmissionReviewerController::class, 'index']);
            Route::post('/conferences/{conference}/submissions/{submission}/reviewers', [SubmissionReviewerController::class, 'store']);
            Route::delete('/conferences/{conference}/submissions/{submission}/reviewers', [SubmissionReviewerController::class, 'destroy']);
            Route::get('/conferences/{conference}/submissions/{submission}/reviews-summary', [SubmissionReviewerController::class, 'reviewsSummary']);
            Route::post('/conferences/{conference}/submissions/{submission}/decision', [SubmissionReviewerController::class, 'recordDecision']);
            Route::get('/conferences/{conference}/reviewers', [ReviewerController::class, 'index']);
            Route::post('/conferences/{conference}/reviewers', [ReviewerController::class, 'store']);
            Route::post('/conferences/{conference}/reviewers/{reviewer}/resend-credentials', [ReviewerController::class, 'resendCredentials']);
            Route::delete('/conferences/{conference}/reviewers/{reviewer}', [ReviewerController::class, 'destroy']);
            Route::get('/conferences/{conference}/sessions', [SessionController::class, 'index']);
            Route::post('/conferences/{conference}/sessions', [SessionController::class, 'store']);
            Route::put('/conferences/{conference}/sessions/{session}', [SessionController::class, 'update']);
            Route::delete('/conferences/{conference}/sessions/{session}', [SessionController::class, 'destroy']);
            Route::post('/conferences/{conference}/sessions/{session}/papers', [SessionController::class, 'assignSubmission']);
            Route::delete('/conferences/{conference}/sessions/{session}/papers/{submission}', [SessionController::class, 'removeSubmission']);
            Route::post('/conferences/{conference}/publish', [ConferenceController::class, 'publish']);
            Route::post('/conferences/{conference}/unpublish', [ConferenceController::class, 'unpublish']);
        });
    });

    Route::middleware('role:author')->prefix('author')->scopeBindings()->group(function (): void {
        Route::get('/submissions', [SubmissionController::class, 'index']);
        Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->whereNumber('submission');
        Route::post('/submissions/{submission}/rebuttal', [SubmissionController::class, 'submitRebuttal'])->whereNumber('submission');
        Route::post('/submissions/{submission}/revision', [SubmissionController::class, 'submitRevision'])->whereNumber('submission');
        Route::post('/submissions/{submission}/camera-ready', [SubmissionController::class, 'submitCameraReady'])->whereNumber('submission');
        Route::post('/conferences/{conference}/topics/{topic}/submissions', [SubmissionController::class, 'store']);
    });

    Route::middleware('role:reviewer')->prefix('reviewer')->group(function (): void {
        Route::get('/topics', [ReviewerTopicController::class, 'index']);
        Route::get('/submissions', [SubmissionReviewController::class, 'index']);
        Route::put('/submissions/{id}/review', [SubmissionReviewController::class, 'saveDraft'])->whereNumber('id');
        Route::post('/submissions/{id}/review/submit', [SubmissionReviewController::class, 'submit'])->whereNumber('id');
        Route::post('/submissions/{id}/conflict', [SubmissionReviewController::class, 'declareConflict'])->whereNumber('id');
    });

    Route::middleware('role:admin')->prefix('admin')->group(function (): void {
        Route::get('/conferences', [ConferenceValidationController::class, 'index']);
        Route::post('/conferences/{conference}/validate', [ConferenceValidationController::class, 'validateConference']);
        Route::post('/conferences/{conference}/reject', [ConferenceValidationController::class, 'reject']);
        Route::get('/users', [AdminUserController::class, 'index']);
    });
});
