# Changelog

All notable changes to Events and Bookings are documented here.

## [0.7.10] - 2026-07-06

### Changed

- Version bump.

## [0.7.9] - 2026-07-04

### Changed

- Version bump.

## [0.7.8] - 2026-07-04

### Changed

- Version bump.

## [0.7.7] - 2026-07-04

### Changed

- Version bump.

## [0.7.6] - 2026-07-04

### Changed

- Version bump.

## [0.7.5] - 2026-07-03

### Changed

- Version bump.

## [0.7.4] - 2026-06-04

### Changed

- Version bump.

## [0.7.3] - 2026-06-04

### Changed

- Version bump.

## [0.7.2] - 2026-06-04

### Changed

- Version bump.

## [0.7.1] - 2026-06-04

### Added

- GoPay sandbox testing guide: `docs/gopay-sandbox-testing.md`.
- Admin **Otestovat připojení** on settings page (OAuth2 probe + resolved callback URLs).

## [0.7.0] - 2026-06-04

### Added

- Phase 6: GoPay card payments (OAuth2, payment create, notification + return URL handlers).
- Fakturoid API v3 — invoice on paid order, PDF stored in uploads, e-mail attachment.
- Payment result pages `/platba-uspesna/`, `/platba-neuspesna/` (`[eab_payment_success]`, `[eab_payment_failed]`).
- Admin settings for GoPay credentials and Fakturoid account.
- DB columns `fakturoid_invoice_id`, `fakturoid_invoice_number`, `fakturoid_pdf` on orders.

## [0.6.1] - 2026-06-04

### Changed

- Version bump.

## [0.4.1] - 2026-06-04

### Changed

- Version bump.

## [0.6.0] - 2026-06-04

### Added

- Phase 5: Paylibo QR platby na stránce bankovního převodu.
- Cron: expirace objednávek, připomínka platby, úklid košíku a logů.
- Admin **Objednávky** — potvrzení platby / zrušení; přehled se statistikami.
- Nastavení bankovního účtu, lhůty platby, e-mailů a OP v administraci.

## [0.5.0] - 2026-06-04

### Added

- Phase 4: basket, checkout (`[eab_checkout]`), capacity holds, pricing rules, optional services.
- Orders tables, booking spots (regular/alternate), bank transfer instructions.
- `[eab_dashboard]` / `[eab_basket_count]`; book button adds to basket → `/pokladna/`.
- Order confirmation e-mails; attendee list from confirmed bookings.

## [0.4.0] - 2026-06-04

### Added

- Phase 3 member auth: register, e-mail verification, set password, login.
- Shortcodes `[eab_register]`, `[eab_login]`, `[eab_set_password]`.
- Auto pages: `/registrace/`, `/prihlaseni/`, `/nastaveni-hesla/`, `/muj-ucet/` (placeholder).
- Role `eab_member`, verification e-mail, unverified login blocked.
- Newsletter hook `eab_newsletter_subscribed`.

## [0.3.0] - 2026-06-04

### Added

- Phase 2 shortcodes: `[eab_events_grid]`, `[eab_events_list]`, `[eab_event_detail]`, `[eab_book_button]`.
- URL filters: `eab_type`, `eab_publikum`, `eab_rozvrzeni`, `eab_druh`, `eab_region`.
- Listing/detail templates, schedule/price/tags helpers, public CSS/JS.
- Attendee block on detail (logged-in only); data via filter `eab_event_attendees`.
- Book CTA (login/register or placeholder until basket phase).

## [0.2.1] - 2026-06-04

### Added

- Product decisions: attendee list for logged-in users only (`EAB_Access`).
- Basket rules: min 1 spot per line; single-event basket default with opt-in multi-event (`eab_basket_allow_multiple_events`).
- Optional company invoice at checkout (DL-style fields, `EAB_Invoice`, profile + checkout partial).
- ACF JSON field groups in `acf-json/` for bookable posts and instructors.
- Settings screen under **Akce a rezervace → Nastavení**.

## [0.2.0] - 2026-06-04

### Added

- Plugin loader, activation/deactivation, and `eab_member` role.
- CPTs with Czech permalinks: `akce`, `treninky`, `instruktori`.
- Filter taxonomies with Czech slugs: `publikum`, `rozvrzeni`, `druh`, `region` (default terms on first run).
- Admin menu **Akce a rezervace** grouping CPTs and taxonomy screens.
- ACF JSON load path (`acf-json/`) and minimal public asset stubs.

## [0.1.0] - 2026-06-04

### Added

- Plugin bootstrap with version constants.
- GitHub release updates via Plugin Update Checker (public repo, release zip asset).
- Composer setup and GitHub Actions release workflow.
