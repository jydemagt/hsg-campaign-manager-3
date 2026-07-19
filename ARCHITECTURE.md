# ARCHITECTURE.md

## Overview

HSG Campaign Manager is a WooCommerce admin plugin built on a fixed layered architecture:

Plugin -> Admin -> AJAX -> Service -> Repository -> Template

The architecture is frozen. New behavior must extend the existing layers rather than replace them.

Priority is a non-negative integer. Higher values win, and any priority-based sorting must be descending.

## Plugin Layer

Files:

- `hsg-campaign-manager.php`
- `includes/Loader.php`
- `includes/Plugin.php`

Responsibilities:

- Declare plugin metadata and bootstrap constants.
- Register the autoloader.
- Create the singleton plugin instance.
- Wait for `plugins_loaded` before initialization.
- Check that WooCommerce is active before loading campaign features.
- Register the campaign post type, admin controllers, product search controller, and pricing service.

The plugin layer is orchestration only. It must not contain campaign business logic.

## Admin Layer

Files:

- `includes/Admin/Admin.php`

Responsibilities:

- Register the Campaign Manager submenu under WooCommerce.
- Restrict access through the `manage_woocommerce` capability.
- Enqueue `assets/css/admin.css` and `assets/js/admin.js` only on the campaign page.
- Localize `admin-ajax.php` and the admin nonce.
- Load prepared campaign list rows from `CampaignService` for display.
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
- `hsgcm_update_campaign_status`
- `hsgcm_preview_conflicts`
- `hsgcm_simulate_campaign`
- `hsgcm_product_search`

AJAX controllers are request adapters. They should not contain pricing rules, conflict rules, scheduling rules, coupon rules, or persistence decisions.

## Service Layer

Files:

- `includes/Campaign/CampaignService.php`
- `includes/Pricing/CouponService.php`
- `includes/Pricing/CampaignLoader.php`
- `includes/Pricing/CampaignEvaluator.php`
- `includes/Pricing/ConflictResolver.php`
- `includes/Pricing/PriceCalculator.php`
- `includes/Pricing/CampaignLabelService.php`
- `includes/Pricing/SimulationService.php`
- `includes/Pricing/PricingService.php`
- `includes/Pricing/CartPricingService.php`

Responsibilities:

- Normalize and sanitize campaign data passed from controllers.
- Validate campaign rules.
- Decide whether a save operation creates or updates a campaign.
- Prepare admin list rows, including translated labels, product counts, and conflict status.
- Validate and apply admin quick status changes between draft and published campaigns.
- Return consistent success/error arrays for controllers.
- Delegate persistence to `CampaignRepository`.

Current campaign fields handled by the service:

- `id`
- `name`
- `status`
- `start_date`
- `end_date`
- `priority`
- `quantity`
- `bundle_price`
- `products`
- `type`
- `value`
- `coupon`
- `stackable`

Current validation covers required campaign name, valid status, valid campaign type, valid date format, end date not earlier than start date, non-negative integer priority, required numeric campaign value, non-negative fixed values, and percentage discounts between 1 and 100.

Campaign conflict preview is handled in `CampaignService` without saving. It compares the current editor state with draft and published campaigns, reports overlaps only when product assignments and date windows overlap, ignores pairs where both campaigns are stackable, and uses descending priority followed by descending campaign ID to identify the winning campaign.

Pricing service responsibilities:

- `CampaignLoader` loads published campaigns, filters them by schedule, and normalizes them for pricing.
- `CampaignEvaluator` determines whether a campaign applies to a product or variation.
- `ConflictResolver` sorts campaigns by descending priority, then by descending ID, and applies stackability rules.
- `PriceCalculator` applies fixed price, percentage discount, and fixed discount calculations.
- `CampaignLabelService` builds de-duplicated customer-facing labels for resolved active campaigns without changing pricing.
- `SimulationService` runs admin-only campaign simulations without creating a WooCommerce cart, using the runtime loader, evaluator, resolver, and calculator.
- `CouponService` maps campaign coupon codes to virtual WooCommerce coupons, validates schedule and product eligibility through WooCommerce coupon hooks, and resolves coupon-aware campaign sets for cart pricing.
- `PricingService` exposes `getProductPrice( int $productId, float $regularPrice ): float`, connects WooCommerce price filters to the pricing engine, and renders prepared product campaign labels.
- `CartPricingService` applies multi-buy bundle pricing in cart and checkout through `woocommerce_before_calculate_totals`, uses coupon-aware campaign resolution for base prices, and renders prepared campaign labels through WooCommerce item data filters.

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
- Create, update, update status, delete, and count campaigns using WordPress APIs.

Repository defaults are the source of truth for stored campaign shape. When metadata is missing, the repository returns:

- `priority`: `0`
- `quantity`: `2`
- `bundle_price`: empty price value
- `products`: empty array
- `type`: `fixed_price`
- `value`: empty string
- `coupon`: empty string
- `stackable`: `false`
- `start_date`: empty string
- `end_date`: empty string

Repositories must stay focused on persistence and retrieval. They should not decide whether a campaign is active, whether a discount applies, or whether campaigns conflict.

## Template Layer

Files:

- `templates/admin/campaigns.php`
- `assets/css/admin.css`
- `assets/js/admin.js`

Responsibilities:

- Render the campaign list and editor form.
- Display prepared campaign name, status, campaign type, product count, priority, schedule, stackability, conflict status, edit action, quick status action, and delete action.
- Provide a product multi-select field backed by AJAX product search.
- Submit create/update/delete requests through admin AJAX.
- Show success and error notices.
- Show admin-only campaign conflict preview results from AJAX.

Templates must escape output and should receive prepared data. JavaScript may improve the admin interaction, but server-side services remain the source of truth.

## Data Flow

Campaign list:

1. WordPress loads the WooCommerce admin submenu page.
2. `Admin::render_page()` calls `CampaignService::list_rows()`.
3. The service loads editable campaign data through `CampaignRepository::all_raw()`.
4. The service prepares translated labels, counts assigned products, and reuses conflict preview logic to classify each campaign as `OK`, `Conflict`, or `Not checked`.
5. `templates/admin/campaigns.php` renders the table and editor.

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

Conflict preview:

1. JavaScript posts the current editor state to `hsgcm_preview_conflicts`.
2. `AjaxController::preview_conflicts()` verifies nonce and capability, sanitizes request input, and delegates to `CampaignService`.
3. `CampaignService::preview_conflicts()` loads draft and published raw campaigns through `CampaignRepository`.
4. The service checks product overlap, date-window overlap, stackability, and winner precedence.
5. JSON data is returned to the admin UI for warnings only; saving is not blocked.

Delete campaign:

1. The admin confirms deletion.
2. JavaScript posts `hsgcm_delete_campaign`.
3. `CampaignService::delete()` delegates to the repository.
4. The repository permanently deletes the campaign post.

Quick status action:

1. The admin clicks Activate or Deactivate in the campaign list.
2. JavaScript posts `hsgcm_update_campaign_status` with campaign ID and target status.
3. `AjaxController::update_campaign_status()` verifies nonce and capability, sanitizes request input, and delegates to `CampaignService`.
4. `CampaignService::update_status()` validates the campaign and requested status.
5. `CampaignRepository::update_status()` updates the campaign post status and stored metadata status.
6. The AJAX controller returns JSON and the admin list reloads.

Product search:

1. Select2 sends `hsgcm_product_search` with a search term.
2. `ProductSearchController` verifies the request.
3. A `WP_Query` searches published products and variations.
4. Matching WooCommerce products are returned as `{ id, text }` rows.

Pricing runtime:

1. WooCommerce calls the registered price filters.
2. `PricingService::filter_price()` ignores admin-only non-AJAX requests.
3. `PricingService::getProductPrice()` asks `CouponService` for the active campaign set on the product.
4. `CouponService` filters campaigns through `CampaignEvaluator` and `ConflictResolver`.
5. `PriceCalculator` applies the winning standard campaign set for product-page pricing.
6. `CartPricingService` resolves the same campaign set for cart items and applies complete multi-buy bundles in cart and checkout through `woocommerce_before_calculate_totals`.
7. `CartPricingService::filter_item_data()` renders line-item notices for completed multi-buy bundles in cart and checkout.

Coupon application:

1. A customer enters a campaign coupon code in cart or checkout.
2. `CouponService` returns virtual WooCommerce coupon data for matching campaign coupons.
3. WooCommerce validates the coupon through the `woocommerce_coupon_is_valid` and `woocommerce_coupon_is_valid_for_product` hooks.
4. `CouponService` checks schedule, product eligibility, priority, and stackability against the active campaign set.
5. `CartPricingService` resolves the same campaign set for each cart item so coupon campaigns can suppress or combine with other campaign discounts correctly.

Campaign labels:

1. WooCommerce renders a product page, product loop item, cart item, or checkout item.
2. `PricingService` or `CartPricingService` asks `CampaignLabelService` for prepared labels.
3. `CampaignLabelService` resolves active campaigns for the product through `CouponService`.
4. The label service builds de-duplicated labels for fixed price, percentage discount, fixed discount, and multi-buy campaigns.
5. The WooCommerce hook renders escaped prepared labels only; price calculation, coupon creation, and conflict resolution are unchanged.

Campaign simulator:

1. The admin selects a product, quantity, customer role, coupon, and date on the Campaign Manager page.
2. JavaScript posts `hsgcm_simulate_campaign` to `admin-ajax.php`.
3. `AjaxController::simulate_campaign()` verifies nonce and `manage_woocommerce`, sanitizes request input, and delegates to `SimulationService`.
4. `SimulationService` loads campaigns for the selected date through `CampaignLoader`, checks product applicability through `CampaignEvaluator`, resolves winners through `ConflictResolver`, and calculates totals through `PriceCalculator`.
5. The service returns regular price, applicable campaigns, rejected campaigns, winning campaign, final price, discount amount, and explanation for admin display.

## Campaign Storage

Campaigns are stored as WordPress posts:

- Post type: `hsg_campaign`
- Visibility: private internal post type, no public UI, no REST exposure.
- Supported post fields: title.
- Statuses used by the current UI: `draft` and `publish`.

Campaign details are stored in a single post meta array:

- Meta key: `_hsgcm_campaign`
- Fields: `id`, `name`, `status`, `start_date`, `end_date`, `priority`, `quantity`, `bundle_price`, `products`, `type`, `value`, `coupon`, `stackable`

The repository merges stored metadata with defaults when loading a campaign. Product IDs are stored in metadata and converted to formatted product names for admin editing.

Priority is stored as a non-negative integer. Higher values win in conflict resolution, and the default value is `0`.

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
- Campaign labels are rendered through WooCommerce product summary, loop, and item data hooks.
- Virtual campaign coupons are surfaced through `woocommerce_get_shop_coupon_data`.
- Campaign coupon validation uses `woocommerce_coupon_is_valid`, `woocommerce_coupon_is_valid_for_product`, and `woocommerce_coupon_error`.
- Multi-buy bundle pricing is applied in cart and checkout with `woocommerce_before_calculate_totals`.
- Multi-buy notices are rendered with WooCommerce cart item data filters.

The current pricing foundation can calculate campaign-adjusted product prices from active campaigns, apply multi-buy bundles in cart and checkout, and validate campaign coupons dynamically through WooCommerce hooks. Order analytics and REST exposure are not implemented yet.

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

When adding or changing campaign comparison logic, keep the descending priority rule intact and keep stackability decisions inside the service layer.
