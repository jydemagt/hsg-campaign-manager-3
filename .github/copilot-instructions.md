# HSG Campaign Manager

## Project Goal

Develop a production-ready WooCommerce Campaign Manager.

The architecture is frozen.

Always extend the existing code.

Never redesign the architecture.

## Architecture

Admin

↓

Ajax

↓

Service

↓

Repository

↓

WooCommerce

Business logic belongs ONLY in Services.

Repositories only persist data.

Templates never contain business logic.

Ajax controllers never contain business logic.

## Coding Standards

- PHP 8.2+
- WordPress Coding Standards
- PSR-4
- WooCommerce HPOS compatible
- Translation ready
- No duplicated code
- PHPDoc required
- Strict typing where possible

## JavaScript

Use vanilla JavaScript where possible.

Use jQuery only inside wp-admin.

## WooCommerce

Never modify WooCommerce core.

Use hooks.

Never query products inside loops unless necessary.

## Before changing code

Read:

- AGENTS.md
- ARCHITECTURE.md
- FEATURES.md
- TODO.md

Read the complete repository before making changes.

## Never

Do not redesign architecture.

Do not rename namespaces.

Do not replace working code.

Do not introduce frameworks.

Do not introduce Composer dependencies without approval.

## Features

Implement one feature at a time.

Update all affected files.

Return a summary of changed files.

Suggest a commit message.