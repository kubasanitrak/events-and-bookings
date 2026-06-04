# events-and-bookings

WordPress plugin for events and bookings.

Repository: https://github.com/kubasanitrak/events-and-bookings

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
