# AGENTS.md

## Purpose

HSG Campaign Manager is a WordPress plugin for managing WooCommerce sales campaigns for HSG Whisky. The plugin currently provides an admin-only campaign manager where WooCommerce managers can create, edit, list, delete, and assign products to campaigns.

The long-term goal is a production-ready campaign system covering pricing, coupons, scheduling, conflict resolution, analytics, import/export, REST endpoints, and bulk operations. The architecture is already established and must be extended, not redesigned.

## Architecture

The plugin follows a fixed layered architecture:

Plugin -> Admin -> AJAX -> Service -> Repository -> WordPress/WooCommerce storage

Templates and assets support the admin interface but must not own business behavior.

- Plugin layer: boots the plugin, registers dependencies, checks WooCommerce availability, and wires controllers.
- Admin layer: registers wp-admin menu pages, enqueues assets, localizes admin data, and renders templates.
- AJAX layer: handles authenticated admin requests, verifies nonces and capabilities, sanitizes request input, and delegates decisions.
- Service layer: owns business rules, validation, normalization, and feature-level workflow.
- Repository layer: owns persistence and retrieval through WordPress APIs.
- Template layer: renders markup using prepared data only.

Business logic belongs in services. Repositories persist data. AJAX controllers must remain thin. Templates must not contain business logic.

## Coding Standards

- Target PHP 8.1 or newer as declared by the plugin header; follow the project instruction of PHP 8.2+ where the runtime allows it.
- Follow WordPress Coding Standards for PHP formatting, escaping, sanitization, nonce checks, and capability checks.
- Use the existing `HSGCM` namespace and autoloading convention: classes live under `includes/` with paths matching namespaces.
- Keep code translation-ready with the `hsg-campaign-manager` text domain.
- Use WooCommerce public APIs and hooks. Do not modify WooCommerce core.
- Keep WooCommerce HPOS compatibility in mind by avoiding direct order table assumptions.
- Add PHPDoc to classes and methods consistently with the existing code.
- Keep JavaScript scoped to the admin page. jQuery is acceptable inside wp-admin because the current admin asset uses it.
- Avoid new frameworks or Composer dependencies unless the project owner approves them first.
- Do not duplicate business rules between AJAX, templates, JavaScript, and services.

## Contributor Workflow

1. Read `AGENTS.md`, `ARCHITECTURE.md`, `FEATURES.md`, and `TODO.md`.
2. Read the relevant implementation files before changing behavior.
3. Implement one feature or fix at a time.
4. Extend existing classes and layer boundaries instead of introducing a parallel architecture.
5. Keep templates focused on markup and data display.
6. Put validation and business decisions in service classes.
7. Put WordPress persistence details in repository classes.
8. Update documentation when behavior, storage, workflows, or roadmap status changes.

## Git Workflow

- Work from a clean understanding of the current branch before editing.
- Keep commits focused on one feature, fix, or documentation change.
- Do not mix formatting-only churn with behavior changes.
- Do not revert unrelated user or teammate changes.
- Use clear commit messages, for example:
  - `docs: document campaign manager architecture`
  - `feat: add campaign scheduling validation`
  - `fix: validate campaign product assignments`
- Before opening a pull request, include the affected files, testing performed, and any known gaps.

## Definition of Done

A change is done when:

- It follows the established Plugin -> Admin -> AJAX -> Service -> Repository -> Template architecture.
- Business logic is in the service layer.
- Persistence is handled through repositories and WordPress/WooCommerce APIs.
- Admin requests verify nonce and `manage_woocommerce` capability where required.
- Input is sanitized and output is escaped.
- User-facing strings are translation-ready.
- The admin UI still loads only on the Campaign Manager page.
- Documentation and backlog entries are updated if scope or behavior changed.
- Manual testing has covered the affected admin workflow.
- No unrelated PHP, JavaScript, CSS, or documentation changes are included.