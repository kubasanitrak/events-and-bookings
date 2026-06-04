# events-and-bookings

WordPress plugin for events and bookings.

Repository: https://github.com/kubasanitrak/events-and-bookings

## Shortcodes (Phase 2)

| Shortcode | Usage |
|-----------|--------|
| `[eab_events_grid]` | Homepage grid — `type="event\|training"`, `ids="1,2,3"`, `limit="6"`, `title="…"` |
| `[eab_events_list]` | Full list + filters — `type="event"`, `filter_action="/akce/"`, preset `audience="deti"` |
| `[eab_event_detail]` | Detail (on singular omit `id`) — `id="123"` |
| `[eab_book_button]` | CTA only — `id="123"` |

**URL filters** (GET): `eab_type=event|training`, `eab_publikum`, `eab_rozvrzeni`, `eab_druh`, `eab_region` (term slugs).

Example list with preset filter:

```
[eab_events_list type="event" audience="deti" filter_action="https://example.test/akce/"]
```

Example homepage picks:

```
[eab_events_grid ids="12,15,18" limit="3" title="Vybrané akce"]
```

## Member auth (Phase 3)

| Shortcode | Page (auto-created) |
|-----------|---------------------|
| `[eab_register]` | `/registrace/` |
| `[eab_login]` | `/prihlaseni/` |
| `[eab_set_password]` | `/nastaveni-hesla/` |

Flow: register → verification e-mail → set password → login. Assign GDPR page ID in options (`eab_gdpr_page`) for consent link.

Verification link format: `?eab_verify=1&eab_uid=ID&eab_token=TOKEN` (home URL or any front page).

## Booking (Phase 4)

| Shortcode | Page |
|-----------|------|
| `[eab_checkout]` | `/pokladna/` |
| `[eab_dashboard]` | `/muj-ucet/` |
| `[eab_basket_count]` | header widget |

Flow: detail → **Rezervovat místo** → pokladna (účastníci, služby) → bankovní převod → potvrzení.

Configure under **Akce a rezervace → Nastavení** (bank account, payment deadline, admin e-mails).

Admin: **Objednávky** — confirm bank transfer manually (`Potvrdit platbu`). QR payment uses Paylibo API when account number + bank code are set.

## Composer

Runtime dependencies are installed into `vendor/` and bundled in release zips (sites do not run Composer).

```bash
composer install
```

The main plugin file loads `vendor/autoload.php` when present.

## Releasing an update

1. Bump `Version` and `EAB_VERSION` in `events-and-bookings.php`.
2. Add a `## [x.y.z]` section to `CHANGELOG.md`.
3. Commit and push to `main`.
4. Create and push a matching tag (header `0.1.1` → tag `v0.1.1`):

```bash
git tag v0.1.1
git push origin v0.1.1
```

GitHub Actions builds `events-and-bookings.zip` and publishes a GitHub Release. Installed sites check for updates via [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) (vendored under `lib/plugin-update-checker/`).

On a WordPress site: **Dashboard → Updates** or **Plugins → Check for updates** (when available).

## First release note

Sites without a GitHub Release newer than the installed version will not see an update until you publish a matching release (e.g. tag `v0.1.0` for version `0.1.0`).
