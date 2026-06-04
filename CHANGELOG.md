# Changelog

All notable changes to Events and Bookings are documented here.

## [0.4.1] - 2026-06-04

### Changed

- Version bump.

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
