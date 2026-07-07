# ARCHITECTURE.md

## Overview

HSG Campaign Manager is a WooCommerce admin plugin built around a fixed layered architecture:

Plugin -> Admin -> AJAX -> Service -> Repository -> WordPress/WooCommerce

The current implementation is admin-focused. It registers a hidden campaign post type, renders a WooCommerce submenu page, supports campaign CRUD through authenticated AJAX, and searches WooCommerce products for campaign assignment.

## Plugin Layer

Files:

- `hsg-campaign-manager.php`
- `includes/Loader.php`
- `includes/Plugin.php`

Responsibilities:

- Declare the WordPress plugin metadata.
- Define `HSGCM_VERSION`, `HSGCM_PATH`, and `HSGCM_URL`.
- Register the project autoloader.
- Bootstrap the singleton plugin instance.
- Wait for `plugins_loaded` before initialization.
- Check that WooCommerce is active before loading campaign functionality.
- Register the campaign post type, AJAX controllers, product search controller, and admin UI.
- Register the pricing service so WooCommerce price filters can call the pricing engine foundation.

The plugin layer is orchestration only. It must not contain campaign business logic.

## Admin Layer

Files:

- `includes/Admin/Admin.php`

Responsibilities:

- Add the Campaign Manager submenu under WooCommerce.
- Restrict access through the `manage_woocommerce` menu capability.
- Enqueue WooCommerce Select2/selectWoo dependencies on the campaign manager page only.
- Enqueue `assets/css/admin.css` and `assets/js/admin.js`.
- Localize `admin-ajax.php` URL and the `hsgcm_admin` nonce.
- Load campaigns from the repository for display.
- Render the admin template.

The admin layer coordinates the wp-admin screen. It must not validate campaign rules or persist campaign state directly.

## AJAX Layer

Files:

- `includes/Admin/AjaxController.php`
- `includes/Admin/ProductSearchController.php`

Responsibilities:

- Register authenticated `wp_ajax_*` actions.
- Verify the `hsgcm_admin` nonce.
- Check the `manage_woocommerce` capability.
- Read and sanitize request parameters.
- Delegate campaign operations to `CampaignService`.
- Return JSON success or error responses.
- Search WooCommerce products and variations for Select2 product assignment.

Current AJAX actions:

- `hsgcm_get_campaign`
- `hsgcm_save_campaign`
- `hsgcm_delete_campaign`
- `hsgcm_product_search`

AJAX controllers are request adapters. They should not contain pricing rules, conflict rules, scheduling rules, coupon rules, or persistence decisions.

## Service Layer

Files:

- `includes/Campaign/CampaignService.php`
- `includes/Pricing/CampaignLoader.php`
- `includes/Pricing/CampaignEvaluator.php`
- `includes/Pricing/ConflictResolver.php`
- `includes/Pricing/PriceCalculator.php`
- `includes/Pricing/PricingService.php`

Responsibilities:

- Normalize and sanitize campaign data passed from controllers.
- Validate campaign rules.
- Decide whether a save operation creates or updates a campaign.
- Return consistent success/error arrays for controllers.
- Delegate persistence to `CampaignRepository`.

Current campaign fields handled by the service:

- `id`
- `name`
- `status`
- `start_date`
- `end_date`
- `priority`
- `products`
- `type`
- `value`
- `coupon`
- `stackable`

Current validation covers required campaign name, valid status, valid campaign type, valid date format, end date not earlier than start date, non-negative integer priority, required numeric campaign value, non-negative fixed values, and percentage discounts between 1 and 100. Future campaign rules must be added here before they are exposed through controllers or templates.

Pricing service responsibilities:

- `CampaignLoader` loads published campaigns, filters them by schedule, and normalizes them for pricing.
- `CampaignEvaluator` determines whether a campaign applies to a product or variation.
- `ConflictResolver` selects the winning campaign set using priority and stackability.
- `PriceCalculator` applies fixed price, percentage discount, and fixed discount calculations.
- `PricingService` exposes `getProductPrice( int $productId, float $regularPrice ): float` and connects WooCommerce price filters to the pricing engine.

Campaign admin validation remains in `CampaignService`. Runtime pricing decisions live in the pricing service classes.

## Repository Layer

Files:

- `includes/Campaign/CampaignRepository.php`
- `includes/Campaign/Campaign.php`

Responsibilities:

- Register the internal campaign post type.
- Retrieve campaign posts.
- Load and merge campaign metadata defaults.
- Load published campaigns and raw campaign data for service use.
- Resolve assigned WooCommerce products into Select2-compatible data.
- Create, update, delete, and count campaigns using WordPress APIs.

Repositories must stay focused on persistence and retrieval. They should not decide whether a campaign is active, whether a discount applies, or whether campaigns conflict.

## Template Layer

Files:

- `templates/admin/campaigns.php`
- `assets/css/admin.css`
- `assets/js/admin.js`

Responsibilities:

- Render the campaign list and editor form.
- Display campaign ID, title, status, edit action, and delete action.
- Provide a product multi-select field backed by AJAX product search.
- Submit create/update/delete requests through admin AJAX.
- Show success and error notices.

Templates must escape output and should receive prepared data. JavaScript may improve the admin interaction, but server-side services remain the source of truth.

## Data Flow

Campaign list:

1. WordPress loads the WooCommerce admin submenu page.
2. `Admin::render_page()` calls `CampaignRepository::all()`.
3. The repository loads `hsg_campaign` posts with `draft` and `publish` statuses.
4. `templates/admin/campaigns.php` renders the table and editor.

Create or update campaign:

1. `assets/js/admin.js` posts form data to `admin-ajax.php`.
2. `AjaxController::save_campaign()` verifies nonce and capability.
3. The controller sanitizes request input and calls `CampaignService::save()`.
4. The service sanitizes, validates, and chooses create or update.
5. `CampaignRepository` writes the post and `_hsgcm_campaign` metadata.
6. The AJAX controller returns JSON to the admin UI.

Edit campaign:

1. The admin clicks Edit.
2. JavaScript posts `hsgcm_get_campaign` with the campaign ID.
3. `CampaignService::get()` delegates to the repository.
4. The repository loads the post, metadata, and assigned product labels.
5. JSON data is returned to populate the editor.

Delete campaign:

1. The admin confirms deletion.
2. JavaScript posts `hsgcm_delete_campaign`.
3. `CampaignService::delete()` delegates to the repository.
4. The repository permanently deletes the campaign post.

Product search:

1. Select2 sends `hsgcm_product_search` with a search term.
2. `ProductSearchController` verifies the request.
3. A `WP_Query` searches published products and variations.
4. Matching WooCommerce products are returned as `{ id, text }` rows.

## Campaign Storage

Campaigns are stored as WordPress posts:

- Post type: `hsg_campaign`
- Visibility: private internal post type, no public UI, no REST exposure.
- Supported post fields: title.
- Statuses used by the current UI: `draft` and `publish`.

Campaign details are stored in a single post meta array:

- Meta key: `_hsgcm_campaign`
- Fields: `id`, `name`, `status`, `start_date`, `end_date`, `priority`, `products`, `type`, `value`, `coupon`, `stackable`

The repository merges stored metadata with defaults when loading a campaign. Product IDs are stored in metadata and converted to formatted product names for admin editing.

## WooCommerce Integration

WooCommerce is required. If WooCommerce is not active, the plugin shows an admin error notice and does not register campaign functionality.

Current WooCommerce touchpoints:

- Admin menu is registered under WooCommerce.
- Admin capability uses `manage_woocommerce`.
- Admin styles and enhanced product select scripts are loaded from WooCommerce.
- Product search uses `product` and `product_variation` post types.
- Products are resolved with `wc_get_product()`.
- Product values are formatted through WooCommerce helpers such as `wc_format_decimal()`.
- Pricing is connected through WooCommerce price filters for simple products and variations.

The current pricing foundation can calculate campaign-adjusted product prices from active campaigns. Coupon behavior, checkout-specific logic, orders, and analytics data are not implemented yet.

## Adding Future Features

Future features must extend the existing layers:

- Add admin controls in `templates/admin/campaigns.php` and `assets/js/admin.js`.
- Add request handling in `AjaxController` or a new focused admin controller.
- Add validation and business rules in a service class.
- Add persistence queries in repository classes.
- Register WooCommerce hooks from the plugin/bootstrap layer or a focused integration class.
- Keep pricing, coupon, scheduling, and conflict decisions out of templates and controllers.
- Update storage defaults and migration notes when campaign metadata changes.
- Update `FEATURES.md` and `TODO.md` when feature status changes.

For example, a pricing engine should be introduced as a service that evaluates active campaigns and product eligibility. WooCommerce price filters should call that service, and repositories should only load the campaigns needed by the service.
