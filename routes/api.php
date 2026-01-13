<?php

use App\Http\Controllers\ActivityLogController;

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\AppSettingsController;
use App\Http\Controllers\Auth\AuthenticateController;
use App\Http\Controllers\Auth\EmailVerifyController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\GithubRepositoryController;
use App\Http\Controllers\GithubSettingsController;
use App\Http\Controllers\JobStatusController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\ManageMenuController;
use App\Http\Controllers\ModuleManagerController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\UserCountryController;
use App\Http\Controllers\UserLanguageController;
use App\Http\Controllers\UserNotificationsController;
use App\Http\Controllers\UsersController;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Orion\Facades\Orion;

// Routes pour l'admin (non soumises au mode maintenance)
Route::post('admin/login', [AuthenticateController::class, 'login'])->middleware('isadmin:admin');
Route::get('maintenance/status', [MaintenanceController::class, 'status']);

Route::group(['middleware' => ['language', 'user.is.blocked', 'maintenance']], function () {
    Route::post('forgot-password', [ForgotPasswordController::class, 'index']);
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name(('password-reset'));
    Route::post('/email/verification-resend', [AuthenticateController::class, 'resendVerificationEmail']);
    Route::get('/email/verify/{id}/{hash}/{uuid}', [EmailVerifyController::class, 'verify'])->name('email.verify');

    Route::post('register', [RegisterController::class, 'store']);
    Route::post('login', [AuthenticateController::class, 'login'])->middleware('isadmin:user');
    Route::resource('settings', AppSettingsController::class)->only(['index', 'store']);
    Route::get('/users/stats', [UsersController::class, 'stats']);
});

Broadcast::routes(['middleware' => 'auth:sanctum', 'user.is.blocked']);

Route::group(['middleware' => ['user.is.blocked', 'auth:sanctum', 'verified', 'language', 'maintenance']], function () {

    Route::get('/roles/count', fn () => response()->json(['count' => \Spatie\Permission\Models\Role::count()]));
    Route::get('/permissions/count', fn () => response()->json(['count' => \Spatie\Permission\Models\Permission::count()]));

    // User blocking routes
    Route::post('/users/{slug}/block', [UsersController::class, 'blockUser']);
    Route::post('/users/{slug}/unblock', [UsersController::class, 'unblockUser']);
    Route::post('/users/{slug}/toggle-block', [UsersController::class, 'toggleBlock']);

    // Routes admin pour la maintenance
    Route::post('/maintenance/toggle', [MaintenanceController::class, 'toggle']);

    // Resources
    Orion::resource('users', UsersController::class);
    Orion::resource('documents', DocumentController::class);
    Orion::resource('documents', DocumentController::class)->middleware([HandleCors::class]);
    Orion::hasManyResource('users', 'languages', UserLanguageController::class);
    Orion::belongsToResource('users', 'country', UserCountryController::class);
    Orion::resource('countries', CountryController::class);
    Orion::resource('roles', RolesController::class);
    Orion::resource('permissions', PermissionController::class);
    Orion::resource('categories', CategoryController::class);
    Orion::resource('menus', ManageMenuController::class)->middleware('invalidate.menu.cache');
    Route::get('/user-menus', [ManageMenuController::class, 'getMenusForCurrentUser'])->name('menus.user');

    // Module Manager routes
    Orion::resource('admin/modules', ModuleManagerController::class);
    Route::post('/admin/modules/generate', [ModuleManagerController::class, 'generateModule']);
    Route::patch('/admin/modules/{slug}/toggle', [ModuleManagerController::class, 'toggleModule']);
    Route::post('/admin/modules/{slug}/regenerate', [ModuleManagerController::class, 'regenerateModule']);
    Route::get('/admin/modules/{slug}/files', [ModuleManagerController::class, 'getModuleFiles']);

    // GitHub Repository routes
    // IMPORTANT: Route sans paramètre AVANT la resource pour éviter les conflits
    Route::post('/github-repositories/sync-from-github', [GithubRepositoryController::class, 'syncFromGithub']);

    Orion::resource('github-repositories', GithubRepositoryController::class);

    Route::get('/github-repositories/{slug}/branches', [GithubRepositoryController::class, 'getBranches']);
    Route::post('/github-repositories/{slug}/branches', [GithubRepositoryController::class, 'createBranch']);
    Route::delete('/github-repositories/{slug}/branches/{branchName}', [GithubRepositoryController::class, 'deleteBranch']);
    // GitHub Settings routes
    // GitHub Settings routes
    Route::get('/github-settings/test', [GithubSettingsController::class, 'test']);
    Orion::resource('github-settings', GithubSettingsController::class);

    Route::post('/github-repositories/{slug}/branches/{branchName}/protect', [GithubRepositoryController::class, 'protectBranch']);
    Route::post('/github-repositories/{slug}/branches/{branchName}/unprotect', [GithubRepositoryController::class, 'unprotectBranch']);
    Route::post('/github-repositories/{slug}/sync', [GithubRepositoryController::class, 'syncRepository']);

    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);

    // Notifications
    Route::get('/sendmessage', [UserNotificationsController::class, 'triggerNewNotification']);
    Route::get('/notifications', [UserNotificationsController::class, 'index']);
    Route::put('/read-notification', [UserNotificationsController::class, 'markAsRead']);
    Route::put('/read-notification-chat/{chatId}', [UserNotificationsController::class, 'markAsReadChat']);
    Route::get('/read-notifications', [UserNotificationsController::class, 'listAllReadNotifications']);
    Route::get('/read-all-others-notifications', [UserNotificationsController::class, 'markReceivedNotificationsAsRead']);

    // OTP
    Route::post('/send-otp', [OtpController::class, 'sendOtp']);
    Route::post('/toggle-otp', [OtpController::class, 'toggleOtp']);
    Route::post('/verify-otp', [OtpController::class, 'verifyOtp']);

    // Auth Status & Logout
    Route::get('logout', [AuthenticateController::class, 'logout']);
    Route::get('/authenticate', [AuthenticateController::class, 'status']);

    // Chat
    Route::get('/chats', [ChatController::class, 'index']);
    Route::get('/chats/{conversationId}/messages', [ChatController::class, 'getMessages']);
    Route::post('/chats/{conversationId}/messages', [ChatController::class, 'sendMessage']);
    Route::post('/chats/{conversationId}/messages/read', [ChatController::class, 'markAsRead']);
    Route::post('/chats/find-or-create', [ChatController::class, 'findOrCreateConversation']);

    // Backup
    Route::prefix('backup')->controller(BackupController::class)->group(function () {
        Route::post('/run', 'run');
        Route::get('/progress', 'getProgress');
        Route::get('/debug', 'debug');
    });

    // Documents
    Route::post('documents/files/{media}', [DocumentController::class, 'updateFile'])->where('media', '[0-9]+');

    // File Upload (Chunking)
    Route::post('/files/upload-chunk', [FileUploadController::class, 'uploadChunk']);
    Route::post('/files/upload-complete', [FileUploadController::class, 'uploadComplete']);
    Route::delete('/files/{fileId}', [FileUploadController::class, 'deleteFile'])->where('fileId', '[0-9]+');
    Route::get('/files/user-files', [FileUploadController::class, 'getUserFiles']);
    Route::delete('/files/user-files', [FileUploadController::class, 'clearUserFiles']);

    // Translation
    Route::post('/translate', [TranslationController::class, 'translate']);
    Route::post('/translate/batch', [TranslationController::class, 'translateBatch']);
    Route::get('/translate/status', [TranslationController::class, 'status']);
    Route::get('/translate/job-status/{moduleName}', [TranslationController::class, 'getJobStatus']);
    Route::get('/translate/completed/{moduleName}', [TranslationController::class, 'getCompletedTranslations']);

    Route::get('/user-jobs', [JobStatusController::class, 'indexForUser'])->name('user-jobs.index');
});

Route::group(['middleware' => ['auth:sanctum', 'localFix']], function () {
    Route::get('documents/{document:slug}/{filename}', [DocumentController::class, 'serveFile'])->name('documents.serve');

    
});
