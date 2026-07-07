# FEATURES.md

## Roadmap

This roadmap reflects the current repository state. Items marked Completed are implemented in the codebase now. Items in later sections are planned around the established architecture and should be moved forward only when implemented and verified.

## Completed

### Campaign Editor

- WooCommerce submenu page for Campaign Manager.
- Campaign list showing ID, name, status, and actions.
- Admin editor form for name, status, priority, products, pricing type, pricing value, coupon code, start date, end date, and stacking.
- Create, update, edit, and delete flows through authenticated AJAX.
- Product search powered by WooCommerce products and variations.
- Service-layer validation for required name, date ranges, non-negative priority, required pricing value, numeric pricing value, and percentage range.

### Campaign Storage

- Internal `hsg_campaign` post type.
- Campaign metadata stored under `_hsgcm_campaign`.
- Defaults for scheduling, priority, pricing type, coupon, stackability, and products.

### Pricing Engine Foundation

- Active published campaigns are loaded and filtered by schedule.
- Campaign applicability is evaluated against products and parent products for variations.
- Conflict resolution uses priority and stackability.
- Price calculation supports fixed price, percentage discount, and fixed discount.
- WooCommerce product and variation price filters call the pricing service.

### Admin Security Baseline

- Admin nonce localized to JavaScript.
- AJAX nonce checks.
- `manage_woocommerce` capability checks.
- Basic sanitization for campaign input.

## In Progress

### Pricing Engine

- Metadata fields exist for `type` and `value`.
- Foundation classes exist for loading, evaluating, resolving, and calculating campaign prices.
- Further catalog, cart, checkout, caching, and edge-case hardening remains.

### Coupon Engine

- Metadata fields exist for `coupon` and `stackable`.
- Coupon generation, coupon validation, and WooCommerce coupon integration are not implemented yet.

### Scheduling

- Metadata fields exist for `start_date` and `end_date`.
- The admin editor can save and reload schedule dates.
- Active-window evaluation is implemented for pricing campaign loading.

### Conflict Resolution

- Metadata field exists for `priority`.
- Runtime priority and stackability resolution is implemented for pricing.
- Admin conflict detection before publish is not implemented yet.

## Planned

### Frontend Pricing

- Harden active campaign pricing across product loops, product pages, cart, and checkout.
- Keep all price decisions in service classes.
- Use WooCommerce hooks and public APIs only.

### Conflict Resolution

- Detect overlapping campaigns for the same products.
- Resolve conflicts using priority and stackability rules.
- Surface conflicts in the admin UI before publishing.

### Bulk Actions

- Bulk publish, draft, delete, duplicate, and product assignment actions.
- Server-side validation before applying changes.

### Import / Export

- Export campaign definitions to a portable file format.
- Import campaigns with validation and conflict reporting.
- Preserve product references by ID and provide fallback matching strategy if needed.

### REST API

- Provide authenticated endpoints for campaign listing, creation, updates, deletion, and status changes.
- Reuse service-layer validation.
- Avoid exposing internal post-meta shape as the public API contract.

## Future

### Analytics

- Track campaign performance, affected products, revenue impact, discount totals, and coupon usage.
- Integrate reporting with WooCommerce data where practical.

### Advanced Pricing Engine

- Support multiple discount models such as fixed price, percentage discount, fixed discount, bundle rules, and category-level campaigns.
- Add preview tools before publishing a campaign.

### Advanced Coupon Engine

- Generate WooCommerce coupons from campaign settings.
- Support campaign-specific coupon restrictions and usage reporting.

### Automation

- Scheduled activation and expiration checks.
- Admin notices for expiring or conflicting campaigns.
- Optional background processing for large catalogs.
