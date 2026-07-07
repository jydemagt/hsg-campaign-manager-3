# ARCHITECTURE.md

## Overview

HSG Campaign Manager follows a fixed WordPress plugin architecture:

Plugin -> Admin -> AJAX -> Service -> Repository -> Template

That layering stays in place. The only change in this update is priority semantics: priority is now a non-negative integer where higher values win.

## Layers

- Plugin: boots the plugin, registers the campaign post type, wires admin and AJAX handlers, and loads runtime helpers.
- Admin: registers the WooCommerce submenu, enqueues assets, localizes AJAX data, and renders the campaign screen.
- AJAX: verifies nonce and capability, sanitizes request data, and delegates save/load/delete actions to the service layer.
- Service: owns campaign validation and normalization.
- Repository: persists and loads campaign data through WordPress APIs.
- Template: renders markup only and receives prepared data.

## Priority Semantics

- Priority must be an integer.
- Priority has a minimum of `0`.
- There is no upper limit.
- Higher numeric priority wins.
- Any sorting done for priority comparison must be descending.

The current implementation stores priority on the campaign record and uses `includes/Pricing/ConflictResolver.php` as the comparison helper for descending priority ordering.

## Data Flow

1. Admin users edit a campaign in `templates/admin/campaigns.php`.
2. `assets/js/admin.js` submits the form through `admin-ajax.php`.
3. `includes/Admin/AjaxController.php` sanitizes the request and calls `CampaignService`.
4. `CampaignService` validates the data, including the priority rule.
5. `CampaignRepository` writes the campaign post and `_hsgcm_campaign` metadata.
6. The admin UI reloads the saved campaign.

## Campaign Storage

- Campaigns are stored as the internal post type `hsg_campaign`.
- Campaign metadata is stored in `_hsgcm_campaign`.
- Priority is stored with the rest of the campaign payload.
- The repository returns a default priority of `0` when metadata is missing.

## WooCommerce Integration

- The plugin requires WooCommerce.
- Admin assets use WooCommerce-enhanced product search scripts.
- Product search queries published products and variations.

## Contributor Rule

When comparing campaigns by priority, always treat the higher value as the winner and sort descending if sorting is required.
