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
use App\Http\Actions\Admin\GetUsersAction;
use App\Http\Actions\Admin\GetUserAction;
use App\Http\Actions\Admin\CreateUserAction;
use App\Http\Actions\Admin\UpdateUserAction;
use App\Http\Actions\Admin\DeleteUserAction;
use App\Http\Actions\Patients\GetPatientsAction;
use App\Http\Actions\Patients\CreatePatientAction;
use App\Http\Actions\Patients\UpdatePatientAction;

use App\Http\Actions\Admin\GetRolesAction;
use App\Http\Actions\Admin\GetRoleAction;
use App\Http\Actions\Admin\CreateRoleAction;
use App\Http\Actions\Admin\UpdateRoleAction;
use App\Http\Actions\Admin\DeleteRoleAction;

use App\Http\Actions\Admin\GetPermissionsAction;
use App\Http\Actions\Admin\TipoInforme\ListTiposInformeAction;
use App\Http\Actions\Admin\TipoInforme\CreateTipoInformeAction;
use App\Http\Actions\Admin\TipoInforme\GetTipoInformeAction;
use App\Http\Actions\Admin\TipoInforme\UpdateTipoInformeAction;
use App\Http\Actions\Admin\TipoInforme\DeleteTipoInformeAction;

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

    // Tipos de informe (template CRUD)
    Route::get('/tipos-informe', ListTiposInformeAction::class)
        ->middleware('require_permissions:admin.tipoinforme.view');
    Route::post('/tipos-informe', CreateTipoInformeAction::class)
        ->middleware('require_permissions:admin.tipoinforme.create');
    Route::get('/tipos-informe/{id}', GetTipoInformeAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:admin.tipoinforme.view');
    Route::put('/tipos-informe/{id}', UpdateTipoInformeAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:admin.tipoinforme.update');
    Route::delete('/tipos-informe/{id}', DeleteTipoInformeAction::class)
        ->whereNumber('id')
        ->middleware('require_permissions:admin.tipoinforme.delete');
});

// Patients routes (grouped) - protected by JWT
Route::prefix('patients')->middleware('auth.jwt')->group(function () {
    Route::get('/find', GetPatientsAction::class)->middleware('require_permissions:patient.view');
    Route::post('/', CreatePatientAction::class)->middleware('require_permissions:patient.create');
    Route::put('/{id}', UpdatePatientAction::class)->middleware('require_permissions:patient.update');
});
