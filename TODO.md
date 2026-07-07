# TODO.md

## High

### Strengthen Campaign Validation

Description:
Tighten service-layer validation for campaign inputs that still rely on basic sanitization, especially product assignments, coupon format, and stackability edge cases.

Expected files:
- `includes/Campaign/CampaignService.php`
- `includes/Campaign/CampaignRepository.php`
- `includes/Admin/AjaxController.php`

Acceptance criteria:
- Product IDs are normalized to valid WooCommerce products where required.
- Coupon format is validated before save.
- Stackability rules are validated before save.
- Priority remains a non-negative integer with no upper limit.
- Invalid requests return clear admin-facing errors from the service layer.

### Harden Frontend Pricing Engine

Description:
Harden campaign pricing across WooCommerce product displays, cart, checkout, variable price ranges, and edge cases while keeping pricing decisions in services.

Expected files:
- `includes/Plugin.php`
- `includes/Pricing/CampaignLoader.php`
- `includes/Pricing/CampaignEvaluator.php`
- `includes/Pricing/ConflictResolver.php`
- `includes/Pricing/PriceCalculator.php`
- `includes/Pricing/PricingService.php`

Acceptance criteria:
- Price behavior is consistent on product pages, loops, cart, and checkout.
- Variable product price ranges are verified.
- Sale price interactions are explicitly defined.
- Campaign pricing behavior is covered by tests or documented manual checks.
- WooCommerce core is not modified.

### Harden Scheduling Rules

Description:
Harden schedule handling beyond pricing campaign loading, including timezone behavior and admin-facing status.

Expected files:
- `includes/Campaign/CampaignService.php`
- `includes/Campaign/CampaignRepository.php`
- `templates/admin/campaigns.php`
- `assets/js/admin.js`

Acceptance criteria:
- Active campaign queries consistently respect start and end dates.
- Schedule behavior is verified against WordPress site timezone settings.
- Admin UI clearly indicates scheduled, active, and expired campaigns.
- Invalid dates are rejected in the service layer before save.

### Implement Conflict Resolution Visibility

Description:
Detect overlapping campaigns that target the same product and surface the result before publish.

Expected files:
- `includes/Campaign/CampaignService.php`
- `includes/Campaign/CampaignRepository.php`
- `includes/Pricing/ConflictResolver.php`
- `templates/admin/campaigns.php`
- `assets/js/admin.js`

Acceptance criteria:
- Overlapping campaigns are detected before publish.
- Higher priority determines the winner when stacking is disabled.
- Stackable campaigns follow explicit rules.
- Admins receive actionable conflict messages.
- Any priority-based comparison remains descending.

## Medium

### Implement Coupon Engine

Description:
Connect campaign coupon metadata to WooCommerce coupon behavior.

Expected files:
- `includes/Plugin.php`
- `includes/Campaign/CampaignService.php`
- `includes/Campaign/CampaignRepository.php`
- New focused coupon integration/service files under `includes/`

Acceptance criteria:
- Campaign coupons can be configured and validated.
- Coupon behavior respects campaign schedule and product selection.
- Stackability rules are enforced.
- Coupon logic is covered by service-level validation.

### Add Bulk Actions

Description:
Support batch operations for campaign management.

Expected files:
- `templates/admin/campaigns.php`
- `assets/js/admin.js`
- `includes/Admin/AjaxController.php`
- `includes/Campaign/CampaignService.php`
- `includes/Campaign/CampaignRepository.php`

Acceptance criteria:
- Admins can select multiple campaigns.
- Bulk status changes and deletion are supported.
- Each item is validated server-side.
- AJAX responses report successes and failures.

### Add Import / Export

Description:
Allow campaign definitions to be exported and imported.

Expected files:
- `templates/admin/campaigns.php`
- `assets/js/admin.js`
- `includes/Admin/AjaxController.php`
- `includes/Campaign/CampaignService.php`
- `includes/Campaign/CampaignRepository.php`
- New importer/exporter service files if needed

Acceptance criteria:
- Export produces a documented portable format.
- Import validates every campaign before saving.
- Import reports missing products and conflicts.
- Existing campaigns are not overwritten without explicit intent.

### Add REST API

Description:
Expose campaign management through authenticated REST endpoints while preserving service-layer rules.

Expected files:
- `includes/Plugin.php`
- New REST controller files under `includes/`
- `includes/Campaign/CampaignService.php`
- `includes/Campaign/CampaignRepository.php`

Acceptance criteria:
- Endpoints require appropriate authentication and capabilities.
- REST create/update/delete paths reuse `CampaignService`.
- Public response format does not leak internal storage details.
- Invalid requests return useful WordPress REST errors.

### Improve Product Search Performance

Description:
Review product search behavior for large catalogs and reduce unnecessary product loading.

Expected files:
- `includes/Admin/ProductSearchController.php`

Acceptance criteria:
- Search remains responsive on large catalogs.
- Results include products and variations as expected.
- Queries avoid unnecessary work inside loops where practical.
- Output remains Select2-compatible.

## Low

### Add Analytics

Description:
Track and report campaign performance.

Expected files:
- `includes/Plugin.php`
- New analytics service/repository files under `includes/`
- `templates/admin/campaigns.php`
- `assets/js/admin.js`

Acceptance criteria:
- Admins can view campaign performance metrics.
- Metrics are tied to campaign IDs.
- Reporting does not slow normal checkout or product browsing.

### Add Campaign Duplication

Description:
Allow admins to duplicate an existing campaign as a draft.

Expected files:
- `templates/admin/campaigns.php`
- `assets/js/admin.js`
- `includes/Admin/AjaxController.php`
- `includes/Campaign/CampaignService.php`
- `includes/Campaign/CampaignRepository.php`

Acceptance criteria:
- Duplicate action creates a draft copy.
- All metadata except identity and status is copied.
- The copied campaign name is distinguishable from the original.

### Add Admin Empty and Loading States

Description:
Improve admin feedback for product loading, AJAX saves, deletes, validation errors, and empty campaign lists.

Expected files:
- `templates/admin/campaigns.php`
- `assets/js/admin.js`
- `assets/css/admin.css`

Acceptance criteria:
- Save and delete operations show clear progress.
- Product search failures are visible to the admin.
- Empty state explains the next available action.
- Messages are accessible and translation-ready where server-rendered.

### Document Storage Format

Description:
Create a detailed reference for `_hsgcm_campaign` metadata fields, defaults, and expected types.

Expected files:
- `ARCHITECTURE.md`
- Optional new documentation file if the reference becomes large

Acceptance criteria:
- Every campaign metadata field is documented.
- Defaults and valid values are listed.
- Migration considerations are documented for future schema changes.
