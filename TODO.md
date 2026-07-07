# TODO.md

## High

### Priority Editing

Description:
Expose priority editing in the admin UI so campaign owners can assign higher values to campaigns that should win.

Expected files:
- `templates/admin/campaigns.php`
- `assets/js/admin.js`
- `includes/Admin/AjaxController.php`
- `includes/Campaign/CampaignService.php`

Acceptance criteria:
- Priority can be edited from the campaign form.
- Priority saves as an integer greater than or equal to `0`.
- Higher values are preserved exactly.

### Priority Comparison

Description:
Use descending priority ordering anywhere campaigns are compared.

Expected files:
- `includes/Pricing/ConflictResolver.php`
- Any future campaign comparison helpers

Acceptance criteria:
- Higher priority wins over lower priority.
- Ties are handled deterministically.
- Any sort used for priority comparison is descending.

## Medium

### Priority Guidance

Description:
Add clearer UI help text and documentation around priority values.

Expected files:
- `templates/admin/campaigns.php`
- `ARCHITECTURE.md`
- `FEATURES.md`

Acceptance criteria:
- Help text explains that higher values win.
- Documentation states that priority is a non-negative integer.
- No conflicting priority language remains in docs.

## Low

### Priority Tests

Description:
Add coverage for validation and descending priority selection.

Expected files:
- Future test files

Acceptance criteria:
- Priority `0` is accepted.
- Negative priority is rejected.
- Higher priority beats lower priority.
