<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminKitLibraryController;
use App\Http\Controllers\Admin\TripSheetController;
use App\Http\Controllers\Common\LanguageController;
use App\Http\Controllers\Common\SelectController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::group(['middleware' => ['admin']], function () {


    // Auth
    Route::group(['middleware' => 'guest:admin'], function () {
        Route::group(['middleware' => 'prevent-back-history'], function () {
            Route::get('/',[AdminAuthController::class,'showAdminLoginForm']);
            Route::get('/login',[AdminAuthController::class,'showAdminLoginForm'])->name('admin.login-view');
            Route::post('/login',[AdminAuthController::class,'adminLogin'])->name('admin.login');
        });
    });

    // Non Authenticated
    Route::group(['middleware' => 'prevent-back-history'], function () {
        Route::get('change-language',[LanguageController::class,'changeLanguage'])->name('admin.change-language');
    });
    Route::get('select/get-category',[SelectController::class,'getSelectCategory'])->name('admin.select.get-select-category');
    Route::get('select/get-brand',[SelectController::class,'getSelectBrand'])->name('admin.select.get-select-brand');
    Route::get('select/get-product',[SelectController::class,'getSelectProduct'])->name('admin.select.get-select-product');
    Route::get('select/get-users',[SelectController::class,'getSelectUser'])->name('admin.select.get-select-users');

    // Authenticated Routes
    Route::group(['middleware' => 'auth:admin'], function () {
        Route::group(['middleware' => 'prevent-back-history'], function () {
            Route::get('dashboard', [AdminDashboardController::class, 'getAdminDashboard'])->name('admin.dashboard')->middleware('check_admin_role');

            // Attributes
            Route::get('kit_libraries',[AdminKitLibraryController::class,'index'])->name('admin.kit_libraries.index')->middleware('check_admin_role');
            Route::get('kit_libraries/create',[AdminKitLibraryController::class,'create'])->name('admin.kit_libraries.create')->middleware('check_admin_role');
            Route::get('kit_libraries/{id}/edit',[AdminKitLibraryController::class,'edit'])->name('admin.kit_libraries.edit')->middleware('check_admin_role');
            Route::get('kit_libraries/{id}',[AdminKitLibraryController::class,'show'])->name('admin.kit_libraries.show')->middleware('check_admin_role');

            Route::post('kit_libraries',[AdminKitLibraryController::class,'store'])->name('admin.kit_libraries.store');
            Route::post('kit_libraries/{id}/update',[AdminKitLibraryController::class,'update'])->name('admin.kit_libraries.update');
            Route::post('kit_libraries/{id}/delete',[AdminKitLibraryController::class,'destroy'])->name('admin.kit_libraries.delete')->middleware('check_admin_role');
            Route::post('kit_library_features/{id}/delete',[AdminKitLibraryController::class,'destroyKitLibraryFeature'])->name('admin.kit_library_features.delete');

            Route::get('trip_sheet',[TripSheetController::class,'create'])->name('admin.trip_sheet.create')->middleware('check_admin_role');
            Route::get('generate-pdf', [TripSheetController::class, 'generatePDF']);



            Route::get('logout', [AdminAuthController::class, 'adminLogout'])->name('admin.logout');
        });

    });

});
