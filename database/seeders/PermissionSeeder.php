<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $route_names=[
            'admin.dashboard',

            'admin.categories.index',
            'admin.categories.create',
            'admin.categories.edit',
            'admin.categories.delete',
            'admin.categories.move-category-data',

            'admin.attributes.index',
            'admin.attributes.create',
            'admin.attributes.edit',
            'admin.attributes.delete',

            'admin.attribute_sets.index',
            'admin.attribute_sets.create',
            'admin.attribute_sets.edit',
            'admin.attribute_sets.delete',

            'admin.products.index',
            'admin.products.create',
            'admin.products.edit',
            'admin.products.show',
            'admin.products.delete',

            'admin.product_variants.index',
            'admin.product_variants.create',
            'admin.product_variants.edit',
            'admin.product_variants.show',
            'admin.product_variants.delete',

            'admin.auction_timings.index',
            'admin.auction_timings.create',
            'admin.auction_timings.edit',
            'admin.auction_timings.delete',

            'admin.special_prices.index',
            'admin.special_prices.create',
            'admin.special_prices.edit',
            'admin.special_prices.delete',

            'admin.stocks.index',
            'admin.stocks.create',
            'admin.stocks.edit',
            'admin.stocks.show',
            'admin.stocks.delete',

            'admin.app_banners.index',
            'admin.app_banners.create',
            'admin.app_banners.edit',
            'admin.app_banners.show',
            'admin.app_banners.delete',

            'admin.app_contents.index',
            'admin.app_contents.create',
            'admin.app_contents.edit',
            'admin.app_contents.show',
            'admin.app_contents.delete',

            'admin.faq.index',
            'admin.faq.create',
            'admin.faq.edit',
            'admin.faq.show',
            'admin.faq.delete',
            'admin.faq.update-order',

            'admin.packages.index',
            'admin.packages.create',
            'admin.packages.edit',
            'admin.packages.delete',

            'admin.users.index',
            'admin.users.create',
            'admin.users.edit',
            'admin.users.delete',

            'admin.user_boutiques.index',
            'admin.user_boutiques.create',
            'admin.user_boutiques.edit',
            'admin.user_boutiques.delete',
            'admin.user_boutiques.update-order',

            'admin.sale_orders.index',
            'admin.sale_orders.show',
            'admin.sale_orders.change-status',

            'admin.auction_orders.index',
            'admin.auction_orders.show',
            'admin.auction_orders.change-status',

            'admin.boutique_categories.index',
            'admin.boutique_categories.create',
            'admin.boutique_categories.edit',
            'admin.boutique_categories.delete',

            'admin.app_settings.edit',

            'admin.delivery_charges.index',
            'admin.delivery_charges.create',
            'admin.delivery_charges.edit',
            'admin.delivery_charges.delete',

            'admin.brands.index',
            'admin.brands.create',
            'admin.brands.edit',
            'admin.brands.delete',

            'admin.auctions.index',
            'admin.auctions.show',

            'admin.bulk_uploads.index',

            'admin.admin_roles.index',
            'admin.admin_roles.create',
            'admin.admin_roles.edit',
            'admin.admin_roles.delete',

            'admin.profile_slabs.index',
            'admin.profile_slabs.create',
            'admin.profile_slabs.edit',
            'admin.profile_slabs.delete',

            'admin.sub_admins.index',
            'admin.sub_admins.create',
            'admin.sub_admins.edit',
            'admin.sub_admins.delete',

            'admin.promo_codes.index',
            'admin.promo_codes.create',
            'admin.promo_codes.edit',
            'admin.promo_codes.show',
            'admin.promo_codes.delete',

            'admin.join_requests.index',
            'admin.join_requests.update',
            'admin.join_requests.show',
            'admin.join_requests.delete',

            'admin.sales_reports.index',
            'admin.sales_reports.show',
            'admin.sales_reports.export',

            'admin.payments_reports.index',
            'admin.payments_reports.show',
            'admin.payments_reports.export',

            'admin.psh_notifications.index',


        ];
        $modules=[
            'Dashboard',

            'Categories',
            'Categories',
            'Categories',
            'Categories',
            'Categories',

            'Attributes',
            'Attributes',
            'Attributes',
            'Attributes',

            'AttributeSets',
            'AttributeSets',
            'AttributeSets',
            'AttributeSets',

            'Products',
            'Products',
            'Products',
            'Products',
            'Products',

            'ProductVariants',
            'ProductVariants',
            'ProductVariants',
            'ProductVariants',
            'ProductVariants',

            'AuctionTimings',
            'AuctionTimings',
            'AuctionTimings',
            'AuctionTimings',

            'SpecialPrices',
            'SpecialPrices',
            'SpecialPrices',
            'SpecialPrices',

            'Stocks',
            'Stocks',
            'Stocks',
            'Stocks',
            'Stocks',

            'AppBanner',
            'AppBanner',
            'AppBanner',
            'AppBanner',
            'AppBanner',

            'AppContents',
            'AppContents',
            'AppContents',
            'AppContents',
            'AppContents',

            'Faq',
            'Faq',
            'Faq',
            'Faq',
            'Faq',
            'Faq',

            'Packages',
            'Packages',
            'Packages',
            'Packages',

            'Users',
            'Users',
            'Users',
            'Users',

            'UserBoutiques',
            'UserBoutiques',
            'UserBoutiques',
            'UserBoutiques',
            'UserBoutiques',

            'SaleOrders',
            'SaleOrders',
            'SaleOrders',

            'AuctionOrders',
            'AuctionOrders',
            'AuctionOrders',

            'BoutiqueCategories',
            'BoutiqueCategories',
            'BoutiqueCategories',
            'BoutiqueCategories',

            'Genera Settings',

            'DeliveryCharges',
            'DeliveryCharges',
            'DeliveryCharges',
            'DeliveryCharges',

            'Brands',
            'Brands',
            'Brands',
            'Brands',

            'Auctions',
            'Auctions',

            'BulkUpload',

            'AdminRoles',
            'AdminRoles',
            'AdminRoles',
            'AdminRoles',

            'ProfileSlabs',
            'ProfileSlabs',
            'ProfileSlabs',
            'ProfileSlabs',

            'SubAdmins',
            'SubAdmins',
            'SubAdmins',
            'SubAdmins',

            'PromoCodes',
            'PromoCodes',
            'PromoCodes',
            'PromoCodes',
            'PromoCodes',

            'JoinRequests',
            'JoinRequests',
            'JoinRequests',
            'JoinRequests',

            'SalesReport',
            'SalesReport',
            'SalesReport',

            'PaymentsReport',
            'PaymentsReport',
            'PaymentsReport',

            'PushNotifications',
        ];

        $title=[
            'Dashboard',

            'List',
            'Create',
            'Edit',
            'Delete',
            'Sort',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Show',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Show',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Show',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Show',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Show',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Show',
            'Delete',
            'Sort',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Delete',
            'Sort',

            'List',
            'Show',
            'ChangeStatus',

            'List',
            'Show',
            'ChangeStatus',

            'List',
            'Create',
            'Edit',
            'Delete',

            'Edit',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Show',

            'BulkUpload',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Show',
            'Delete',

            'List',
            'Action',
            'Show',
            'Delete',

            'List',
            'Show',
            'Export',

            'List',
            'Show',
            'Export',

            'Send',
        ];

        $title_ar =[
            'Dashboard',

            'List',
            'Create',
            'Edit',
            'Delete',
            'Sort',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Show',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Show',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Show',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Show',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Show',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Show',
            'Delete',
            'Sort',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Delete',
            'Sort',

            'List',
            'Show',
            'ChangeStatus',

            'List',
            'Show',
            'ChangeStatus',

            'List',
            'Create',
            'Edit',
            'Delete',

            'Edit',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Show',

            'BulkUpload',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Delete',

            'List',
            'Create',
            'Edit',
            'Show',
            'Delete',

            'List',
            'Action',
            'Show',
            'Delete',

            'List',
            'Show',
            'Export',

            'List',
            'Show',
            'Export',

            'Send',
        ];

        foreach ($route_names as $key => $route_name) {
            Permission::create(['route_name' => $route_name,'module'=>$modules[$key], 'user_type'=> 'Admin', 'title'=>$title[$key], 'title_ar'=>$title_ar[$key]]);
        }
    }
}
