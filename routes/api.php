<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Actions\Health\CheckHealthAction;
use App\Http\Actions\Auth\LoginAction;
use App\Http\Actions\Auth\RefreshAction;
use App\Http\Actions\Auth\LogoutAction;
use App\Http\Actions\Auth\MeAction;
use App\Http\Actions\Auth\ForgotPasswordAction;
use App\Http\Actions\Auth\ResetPasswordAction;
use App\Http\Actions\Admin\User\GetUsersAction;
use App\Http\Actions\Admin\User\GetUserAction;
use App\Http\Actions\Admin\User\CreateUserAction;
use App\Http\Actions\Admin\User\UpdateUserAction;
use App\Http\Actions\Admin\User\DeleteUserAction;
use App\Http\Actions\Admin\Role\GetRolesAction;
use App\Http\Actions\Admin\Role\GetRoleAction;
use App\Http\Actions\Admin\Role\CreateRoleAction;
use App\Http\Actions\Admin\Role\UpdateRoleAction;
use App\Http\Actions\Admin\Role\DeleteRoleAction;
use App\Http\Actions\Admin\Role\GetPermissionsAction;
use App\Http\Actions\Patients\GetPatientsAction;
use App\Http\Actions\Patients\CreatePatientAction;
use App\Http\Actions\Patients\UpdatePatientAction;
use App\Http\Actions\Patients\GetPatientAction;
use App\Http\Actions\Admin\ReportTemplate\ListReportTemplatesAction;
use App\Http\Actions\Admin\ReportTemplate\CreateReportTemplateAction;
use App\Http\Actions\Admin\ReportTemplate\GetReportTemplateAction;
use App\Http\Actions\Admin\ReportTemplate\UpdateReportTemplateAction;
use App\Http\Actions\Admin\ReportTemplate\DeleteReportTemplateAction;
use App\Http\Actions\Admin\SystemVariable\GetSystemVariablesAction;
use App\Http\Actions\Reports\ListReportsAction;
use App\Http\Actions\Reports\InitReportAction;
use App\Http\Actions\Reports\GetReportAction;
use App\Http\Actions\Reports\SaveDraftReportAction;
use App\Http\Actions\Reports\SignReportAction;
use App\Http\Actions\Reports\CloseReportAction;
use App\Http\Actions\Reports\DownloadPdfReportAction;
use App\Http\Actions\Reports\GetActiveTemplatesAction;
use App\Http\Actions\Reports\ExtractReportDataAction;
use App\Http\Actions\Reports\TranscribeReportAction;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/health', CheckHealthAction::class);

// Auth routes (login, refresh, logout, me, forgot/reset password)
Route::prefix('auth')->group(function () {
    Route::post('/login', LoginAction::class)->middleware('throttle:5,1');
    Route::post('/refresh', RefreshAction::class);
    Route::post('/logout', LogoutAction::class);
    Route::get('/me', MeAction::class)->middleware('auth.jwt');

    Route::post('/forgot', ForgotPasswordAction::class)->middleware('throttle:5,1');
    Route::post('/reset', ResetPasswordAction::class);
});


// Admin routes (grupo protegido por JWT)
Route::prefix('admin')->middleware('auth.jwt')->group(function () {
    // List permissions
    Route::get('/permissions', GetPermissionsAction::class)
        ->middleware('require_permissions:admin.permission.view');

    // List users (requires view)
    Route::get('/users', GetUsersAction::class)->middleware('require_permissions:admin.user.view');

    // Get single user
    Route::get('/users/{id}', GetUserAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:admin.user.view');

    // Create user
    Route::post('/users', CreateUserAction::class)
        ->middleware('require_permissions:admin.user.create');

    // Update user
    Route::put('/users/{id}', UpdateUserAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:admin.user.update');

    // Delete user
    Route::delete('/users/{id}', DeleteUserAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:admin.user.delete');

    // List roles
    Route::get('/roles', GetRolesAction::class)
        ->middleware('require_permissions:admin.role.view');

    // Get single role
    Route::get('/roles/{id}', GetRoleAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:admin.role.view');

    // Create role
    Route::post('/roles', CreateRoleAction::class)
        ->middleware('require_permissions:admin.role.create');

    // Update role
    Route::put('/roles/{id}', UpdateRoleAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:admin.role.update');

    // Delete role
    Route::delete('/roles/{id}', DeleteRoleAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:admin.role.delete');

    // Report templates CRUD
    Route::get('/report-templates', ListReportTemplatesAction::class)
        ->middleware('require_permissions:admin.reporttemplate.view');
    Route::post('/report-templates', CreateReportTemplateAction::class)
        ->middleware('require_permissions:admin.reporttemplate.create');
    Route::get('/report-templates/{id}', GetReportTemplateAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:admin.reporttemplate.view');
    Route::put('/report-templates/{id}', UpdateReportTemplateAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:admin.reporttemplate.update');
    Route::delete('/report-templates/{id}', DeleteReportTemplateAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:admin.reporttemplate.delete');

    // System variables catalog (for report template builder autocomplete)
    Route::get('/system-variables', GetSystemVariablesAction::class);
});

// Patients routes (grouped) - protected by JWT
Route::prefix('patients')->middleware('auth.jwt')->group(function () {
    Route::get('/find', GetPatientsAction::class)->middleware('require_permissions:patient.view');
    Route::post('/', CreatePatientAction::class)->middleware('require_permissions:patient.create');
    Route::get('/{id}', GetPatientAction::class)->whereNumber('id')->middleware('require_permissions:patient.view');
    Route::put('/{id}', UpdatePatientAction::class)->middleware('require_permissions:patient.update');
});

// Reports routes - protected by JWT
Route::prefix('reports')->middleware('auth.jwt')->group(function () {
    Route::get('/', ListReportsAction::class)
        ->middleware('require_permissions:report.view');
    Route::post('/', InitReportAction::class)
        ->middleware('require_permissions:report.create');
    Route::get('/{id}', GetReportAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:report.view');
    Route::put('/{id}', SaveDraftReportAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:report.edit');
    Route::post('/{id}/sign', SignReportAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:report.sign');
    Route::post('/{id}/close', CloseReportAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:report.close');
    Route::get('/{id}/pdf', DownloadPdfReportAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:report.download-pdf');
    Route::post('/{id}/extract-data', ExtractReportDataAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:report.edit');
    Route::post('/{id}/transcribe', TranscribeReportAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:report.edit');
});

// Templates routes - protected by JWT
Route::prefix('templates')->middleware('auth.jwt')->group(function () {
    Route::get('/active', GetActiveTemplatesAction::class)
        ->middleware('require_permissions:report.create');
});
