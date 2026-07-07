# FEATURES.md

## Completed

- Campaign Manager admin screen.
- Campaign CRUD through authenticated AJAX.
- Product search for WooCommerce products and variations.
- Campaign priority storage in `_hsgcm_campaign`.
- Priority validation as a non-negative integer.
- Higher priority values win in conflict comparison helpers.

## In Progress

- Exposing priority editing in the admin UI.
- Wiring priority comparison into any future campaign conflict workflow.
- Surfacing clearer priority guidance to admins.

## Planned

- Conflict resolution using higher priority first.
- Sorting any priority-based lists in descending order.
- Additional campaign rules that rely on the higher-wins priority model.

## Future

- Campaign ranking previews.
- Conflict reports for overlapping campaigns.
- Priority-aware bulk actions.
