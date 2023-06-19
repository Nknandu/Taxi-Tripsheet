<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\Common\LanguageController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\User\KitLibraryController;
use App\Http\Controllers\User\AuthController;



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



//Route::get('/home', [HomeController::class, 'index'])->name('home');
//Route::get('/test_time_zone', [\App\Http\Controllers\TestController::class, 'testTimeZone']);
//Route::get('/test_code', [\App\Http\Controllers\TestController::class, 'testCode']);

Route::get('/', [TestController::class, 'homePage']);
Route::get('/home', [TestController::class, 'homePage']);
Route::get('/about_us', [TestController::class, 'aboutUsPage']);
Route::get('/pricing', [TestController::class, 'pricingPage']);
//Route::get('/kit-libraries', [TestController::class, 'kitLibraryPage']);
Route::get('/kit-library/detail', [TestController::class, 'kitLibraryDetailPage']);

// User

// Auth
Route::group(['middleware' => 'guest:web'], function () {
    Route::group(['middleware' => 'prevent-back-history'], function () {
        Route::get('/sign-in',[AuthController::class,'showUserLoginForm'])->name('sign-in-view');
        Route::get('/login',[AuthController::class,'showUserLoginForm'])->name('login-view');
        Route::post('/login',[AuthController::class,'userLogin'])->name('login');

        Route::get('/sign-up',[AuthController::class,'showUserRegisterForm'])->name('sign-up-view');
        Route::get('/register',[AuthController::class,'showUserRegisterForm'])->name('register-view');
        Route::post('/register',[AuthController::class,'userRegister'])->name('register');

        Route::get('/forgot-password',[AuthController::class,'showUserForgotPasswordForm'])->name('forgot-password-view');
        Route::post('/forgot-password',[AuthController::class,'userForgotPassword'])->name('forgot-password');
    });
});

// Non Authenticated
Route::group(['middleware' => 'prevent-back-history'], function () {
    Route::get('change-language',[LanguageController::class,'changeLanguage'])->name('change-language');

    Route::get('kit-libraries',[KitLibraryController::class,'getKitLibraryPage'])->name('kit-libraries');
    Route::get('kit-library/{slug}',[KitLibraryController::class,'getKitLibraryDetail'])->name('kit-library-detail');

});

Route::group(['middleware' => 'auth:web', 'prefix' => 'user'], function () {
    Route::group(['middleware' => 'prevent-back-history'], function () {
        Route::get('dashboard', [DashboardController::class, 'userDashboard'])->name('user.dashboard');
        Route::get('favorites', [DashboardController::class, 'userFavorites'])->name('user.favorites');
        Route::get('settings', [DashboardController::class, 'userSettings'])->name('user.settings');
        Route::get('logout', [AuthController::class, 'userLogout'])->name('logout');
    });
});
