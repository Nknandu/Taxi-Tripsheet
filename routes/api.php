<?php

use App\Http\Controllers\Api\User\LiveController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\User\UserAuthController;
use App\Http\Controllers\Api\User\UserCategoryController;
use App\Http\Controllers\Api\User\UserProductController;
use App\Http\Controllers\Api\User\UserHomeController;
use App\Http\Controllers\Api\User\UserCartController;
use App\Http\Controllers\Api\User\UserPackageController;
use App\Http\Controllers\Api\User\UserBoutiqueController;
use App\Http\Controllers\Api\User\UserAddressController;
use App\Http\Controllers\Api\User\UserStockController;
use App\Http\Controllers\Api\User\UserCheckoutController;
use App\Http\Controllers\Api\User\UserVendorController;
use App\Http\Controllers\Api\User\UserOrderController;
use App\Http\Controllers\Api\User\UserBiddingController;
use App\Http\Controllers\Api\User\UserRatingController;
use App\Http\Controllers\Api\User\UserContactUsController;
use App\Http\Controllers\Api\User\UserBrandController;
use App\Http\Controllers\Api\User\UserFaqController;
use App\Http\Controllers\Api\User\UserContentController;
use App\Http\Controllers\Api\User\UserJoinRequestController;
use App\Http\Controllers\Api\User\UserProductCrudController;
use App\Http\Controllers\Api\User\UserPromoCodeController;
use App\Http\Controllers\Api\User\UserOrderRatingController;
use App\Http\Controllers\Api\User\UserSearchController;
use App\Http\Controllers\TestController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'api', 'prefix' => 'v1/user'], function () {
    Route::any('login', [UserAuthController::class, 'userLogin'])->name('user.login');
    Route::any('register', [UserAuthController::class, 'userRegister'])->name('user.register');
    Route::any('forgot-password', [UserAuthController::class, 'userForgotPassword'])->name('user.forgot-password');

    Route::get('get-home-data', [UserHomeController::class, 'getUserHomeData'])->name('user.get-home-data');
    Route::get('search-suggestions', [UserSearchController::class, 'getSearchSuggestions'])->name('user.search-suggestions');

    Route::get('get-categories', [UserCategoryController::class, 'getUserCategories'])->name('user.get-categories');
    Route::get('get-brands', [UserBrandController::class, 'getUserBrands'])->name('user.get-brands');

    Route::any('get-products', [UserProductController::class, 'getUserProducts'])->name('user.get-products');
    Route::get('get-product-details', [UserProductController::class, 'getUserProductDetails'])->name('user.get-product-details');
    Route::get('get-product-combination/{sale_or_auction_id}', [UserProductController::class, 'getUserProductCombination'])->name('user.get-product-combination');

    Route::get('get-cart-items', [UserCartController::class, 'getUserCartItems'])->name('user.get-cart-items');

    Route::any('add-to-cart', [UserCartController::class, 'addUserCartItem'])->name('user.add-to-cart');
    Route::any('update-cart', [UserCartController::class, 'updateUserCartItem'])->name('user.update-cart');
    Route::any('delete-cart', [UserCartController::class, 'deleteUserCartItem'])->name('user.delete-cart');
    Route::any('clear-cart', [UserCartController::class, 'clearUserCartItems'])->name('user.clear-cart');

    Route::get('get-packages', [UserPackageController::class, 'getUserPackages'])->name('user.get-packages');

    Route::get('get-boutiques', [UserBoutiqueController::class, 'getUserBoutiques'])->name('user.get-boutiques');
    Route::get('get-boutique-categories', [UserBoutiqueController::class, 'getUserBoutiqueCategory'])->name('user.get-boutique-categories');
    Route::get('get-boutique-details', [UserBoutiqueController::class, 'getUserBoutiqueDetails'])->name('user.get-boutique-details');

    Route::get('get-vendors', [UserVendorController::class, 'getUserVendors'])->name('user.get-vendors');

    Route::get('get-countries', [UserAddressController::class, 'getCountries'])->name('user.get-countries');
    Route::get('get-governorates', [UserAddressController::class, 'getGovernorates'])->name('user.get-governorates');
    Route::get('get-areas', [UserAddressController::class, 'getAreas'])->name('user.get-areas');


    Route::get('get-faqs', [UserFaqController::class, 'getFaqs'])->name('user.get-faqs');
    Route::get('get-cms', [UserContentController::class, 'getCms'])->name('user.get-cms');
    Route::get('get-user-types', [UserJoinRequestController::class, 'getUserTypes'])->name('user.get-user-types');

    Route::any('contact-us', [UserContactUsController::class, 'userContactUs'])->name('user.contact-us');
    Route::any('join-us', [UserJoinRequestController::class, 'userJoinUs'])->name('user.join-us');


    Route::any('proceed-to-checkout-test', [TestController::class, 'userProceedToCheckoutTest'])->name('user.proceed-to-checkout-test');


    Route::group(['middleware' => 'auth:api', 'api_auth'], function () {
        Route::get('user-info', [UserAuthController::class, 'userInfo'])->name('user.info');
        Route::any('logout', [UserAuthController::class, 'userLogout'])->name('user.logout');
        Route::any('add-address', [UserAddressController::class, 'addUserAddress'])->name('user.add-address');
        Route::get('get-addresses', [UserAddressController::class, 'getUserAddresses'])->name('user.get-addresses');
        Route::any('update-address', [UserAddressController::class, 'updateUserAddress'])->name('user.update-address');
        Route::any('delete-address', [UserAddressController::class, 'deleteUserAddress'])->name('user.delete-address');
        Route::any('make-address-default', [UserAddressController::class, 'makeUserAddressDefault'])->name('user.make-address-default');

        Route::any('follow-boutique', [UserBoutiqueController::class, 'userFollowBoutique'])->name('user.follow-boutique');
        Route::any('wishlist-product', [UserProductController::class, 'userFavoriteProduct'])->name('user.favorite-product');
        Route::get('wishlists', [UserProductController::class, 'userFavoriteProductLists'])->name('user.wishlists');

        Route::get('get-my-rating', [UserRatingController::class, 'userGetMyRating'])->name('user.get-my-rating');
        Route::any('add-rating', [UserRatingController::class, 'userAddRating'])->name('user.add-rating');
        Route::any('update-rating', [UserRatingController::class, 'userUpdateRating'])->name('user.update-rating');
        Route::any('delete-rating', [UserRatingController::class, 'userDeleteRating'])->name('user.delete-rating');

        Route::any('make-a-bid', [UserBiddingController::class, 'userMakeBid'])->name('user.make-a-bid');
        Route::get('bidding-details', [UserBiddingController::class, 'userBiddingDetails'])->name('user.bidding-details');
        Route::get('get-bid-members', [UserBiddingController::class, 'getUserBidMembers'])->name('user.get-bid-members');

        Route::any('check-stock-items', [UserStockController::class, 'userCheckStockItems'])->name('user.check-stock-items');
        Route::any('proceed-to-checkout', [UserCheckoutController::class, 'userProceedToCheckout'])->name('user.proceed-to-checkout');

        Route::get('get-ordered-items', [UserOrderController::class, 'getUserOrderedItems'])->name('user.get-ordered-items');
        Route::get('get-order-detail', [UserOrderController::class, 'getUserOrderDetail'])->name('user.get-order-detail');

        Route::get('get-product-crud-details-step-1', [UserProductCrudController::class, 'getProductCrudDetailsStep1'])->name('user.get-product-crud-details-step-1');
        Route::get('get-product-crud-details-step-2', [UserProductCrudController::class, 'getProductCrudDetailsStep2'])->name('user.get-product-crud-details-step-2');
        Route::any('create-product', [UserProductCrudController::class, 'createProduct'])->name('user.create-product');
        Route::any('update-product', [UserProductCrudController::class, 'updateProduct'])->name('user.update-product');
        Route::any('enable-disable-product', [UserProductCrudController::class, 'enableDisableProduct'])->name('user.enable-disable-product');

        Route::get('my-boutique', [UserBoutiqueController::class, 'myBoutique'])->name('user.my-boutique');
        Route::get('my-boutique-products', [UserBoutiqueController::class, 'myBoutiqueProducts'])->name('user.my-boutique-products');
        Route::get('my-boutique-orders', [UserBoutiqueController::class, 'myBoutiqueOrders'])->name('user.my-boutique-orders');
        Route::get('my-boutique-sales', [UserBoutiqueController::class, 'myBoutiqueSales'])->name('user.my-boutique-sales');
        Route::get('my-boutique-order-details', [UserBoutiqueController::class, 'myBoutiqueOrderDetails'])->name('user.my-boutique-order-details');
        Route::any('change-order-status', [UserBoutiqueController::class, 'changeOrderStatus'])->name('user.change-order-status');

        Route::get('get-my-order-rating', [UserOrderRatingController::class, 'userGetMyOrderRating'])->name('user.get-my-order-rating');
        Route::any('add-order-rating', [UserOrderRatingController::class, 'userAddOrderRating'])->name('user.add-order-rating');
        Route::any('update-order-rating', [UserOrderRatingController::class, 'userUpdateOrderRating'])->name('user.update-order-rating');
        Route::any('delete-order-rating', [UserOrderRatingController::class, 'userDeleteOrderRating'])->name('user.delete-order-rating');

        Route::any('promo-code', [UserPromoCodeController::class, 'promoCode'])->name('user.promo-code');

        Route::any('change-password', [UserAuthController::class, 'changePassword'])->name('user.change-password');
        Route::any('update-profile', [UserAuthController::class, 'updateProfile'])->name('user.update-profile');

        // Live
        Route::any('create-live', [LiveController::class, 'createLive'])->name('live.create-live');
        Route::any('live-rooms', [LiveController::class, 'liveRooms'])->name('live.live-rooms');
        Route::any('join-live', [LiveController::class, 'joinLive'])->name('live.join-live');
        Route::any('go-live', [LiveController::class, 'goLive'])->name('live.go-live');
        Route::any('mylive-list', [LiveController::class, 'myLiveList'])->name('live.mylive-list');
        Route::any('live-list', [LiveController::class, 'liveList'])->name('live.live-list');
    });

});


