<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\AppSettingsController;
use App\Http\Controllers\Auth\AuthenticateController;
use App\Http\Controllers\Auth\EmailVerifyController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UserCountryController;
use App\Http\Controllers\UserDocumentController;
use App\Http\Controllers\UserLanguageController;
use App\Http\Controllers\UserNotificationsController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Orion\Facades\Orion;

Route::group(['middleware' => ['language']], function () {

    // Routes de relation entre User et Language (hasMany)
    Route::post('register', [RegisteredUserController::class, 'store']);
    // verifier l'email
    Route::get('/email/verify/{id}/{hash}/{uuid}', [EmailVerifyController::class, 'verify'])
        ->middleware(['signed'])->name('email.verify');

    Route::post('login', [AuthenticateController::class, 'login']);

    Route::post('forgot-password', [ForgotPasswordController::class, 'index']);

    Route::get('/reset-password', function (string $token) {})->name('password-reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);

    Route::post('/email/verification-resend', [AuthenticateController::class, 'resendVerificationEmail']);

    Route::resource('settings', AppSettingsController::class)
        ->only(['index', 'store']);
    Route::get('/users/stats', [UsersController::class, 'stats']);
});

Broadcast::routes(['middleware' => ['auth:sanctum']]);
Route::get('/roles/count', function () {
    return response()->json(['count' => \Spatie\Permission\Models\Role::count()]);
});
Route::get('/permissions/count', function () {
    return response()->json(['count' => \Spatie\Permission\Models\Permission::count()]);
});

Route::group(['middleware' => ['auth:sanctum', 'verified', 'language']], function () {
    Orion::resource('users', UsersController::class);
    Orion::resource('documents', DocumentController::class);
    // Orion::hasManyResource('users', 'documents', UserDocumentController::class);
    Orion::resource('languages', LanguageController::class);
    Orion::hasManyResource('users', 'languages', UserLanguageController::class);

    Orion::belongsToResource('users', 'country', UserCountryController::class);
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    Orion::resource('countries', CountryController::class);
    Orion::resource('roles', RolesController::class);
    Orion::resource('permissions', PermissionController::class);
    Route::get('/sendmessage', [UserNotificationsController::class, 'triggerNewNotification']);
    Route::get('/notifications', [UserNotificationsController::class, 'index']);
    Route::put('/read-notification', [UserNotificationsController::class, 'markAsRead']);
    Route::put('/read-notification-chat/{chatId}', [UserNotificationsController::class, 'markAsReadChat']);

    Route::get('/read-notifications', [UserNotificationsController::class, 'listAllReadNotifications']);
    Route::get('/read-all-others-notifications', [UserNotificationsController::class, 'markReceivedNotificationsAsRead']);
    Route::post('/send-otp', [OtpController::class, 'sendOtp']);
    Route::post('/toggle-otp', [OtpController::class, 'toggleOtp']);
    Route::post('/verify-otp', [OtpController::class, 'verifyOtp']);
    // logout route api
    Route::get('logout', [AuthenticateController::class, 'logout']);
    Route::get('/authenticate', [AuthenticateController::class, 'status']);

    // Récupérer la liste des chats de l'utilisateur (pour la sidebar)
    Route::get('/chats', [ChatController::class, 'index']);

    // Récupérer les messages d'un chat spécifique
    Route::get('/chats/{conversationId}/messages', [ChatController::class, 'getMessages']);

    // Envoyer un nouveau message
    Route::post('/chats/{conversationId}/messages', [ChatController::class, 'sendMessage']);

    // (Optionnel) Marquer les messages comme lus
    Route::post('/chats/{conversationId}/messages/read', [ChatController::class, 'markAsRead']);

    // (Optionnel) Créer ou trouver une conversation avec un autre utilisateur
    Route::post('/chats/find-or-create', [ChatController::class, 'findOrCreateConversation']);
    Route::prefix('backup')->controller(BackupController::class)->group(function () {
        Route::post('/run', 'run');
        Route::get('/progress', 'getProgress');
        Route::get('/debug', 'debug');
    });
    Route::post('documents/files/{media}', [DocumentController::class, 'updateFile'])->where('media', '[0-9]+');
});
