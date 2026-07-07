# TODO.md

## High

### Strengthen Campaign Validation

Description:
Continue expanding service-layer validation for product IDs, coupon format, and stackability rules.

Expected files:
- `includes/Campaign/CampaignService.php`
- `includes/Campaign/CampaignRepository.php`

Acceptance criteria:
- Product IDs are normalized to existing WooCommerce products where required.
- Coupon format is validated before save.
- Stackability rules are validated before save.

### Implement Frontend Pricing Engine

Description:
Apply active campaign pricing to WooCommerce frontend product prices while keeping pricing decisions in services.

Expected files:
- `includes/Plugin.php`
- `includes/Campaign/CampaignService.php`
- `includes/Campaign/CampaignRepository.php`
- New focused pricing integration/service files under `includes/`

Acceptance criteria:
- Active published campaigns affect eligible product prices.
- Draft, expired, and future campaigns do not affect prices.
- Price behavior is consistent on product pages, loops, cart, and checkout.
- WooCommerce core is not modified.

### Implement Scheduling Rules

Description:
Use `start_date` and `end_date` to determine when a campaign is active.

Expected files:
- `includes/Campaign/CampaignService.php`
- `includes/Campaign/CampaignRepository.php`
- `templates/admin/campaigns.php`
- `assets/js/admin.js`

Acceptance criteria:
- Campaigns can be scheduled from the admin UI.
- Active campaign queries respect start and end dates.
- Date validation prevents impossible ranges.
- Timezone behavior follows WordPress site settings.

### Implement Conflict Resolution

Description:
Detect and resolve overlapping campaigns that target the same product.

Expected files:
- `includes/Campaign/CampaignService.php`
- `includes/Campaign/CampaignRepository.php`
- `templates/admin/campaigns.php`
- `assets/js/admin.js`

Acceptance criteria:
- Overlapping campaigns are detected before publish.
- Priority determines the winning campaign when stacking is disabled.
- Stackable campaigns follow explicit rules.
- Admins receive actionable conflict messages.

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
- AJAX response reports successes and failures.

### Add Import / Export

Description:
Allow campaign definitions to be exported and imported.

Expected files:
- `templates/admin/campaigns.php`
- `assets/js/admin.js`
- `includes/Admin/AjaxController.php`
- `includes/Campaign/CampaignService.php`
- `includes/Campaign/CampaignRepository.php`
- New importer/exporter service files if needed.

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
- All metadata except identity/status is copied.
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
- Optional new documentation file if the reference becomes large.

Acceptance criteria:
- Every campaign metadata field is documented.
- Defaults and valid values are listed.
- Migration considerations are documented for future schema changes.
