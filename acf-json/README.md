# ACF field groups (plugin)

Field groups are versioned here and loaded via `EAB_ACF`:

| File | Post types |
|------|------------|
| `group_eab_bookable.json` | `eab_event`, `eab_training` |
| `group_eab_instructor.json` | `eab_instructor` |

Bio and photo for instructors use native **content** and **featured image**.

## Sync after install

1. Activate **ACF Pro**.
2. Open **Vlastní pole** — if groups show *Sync available*, run **Sync**.
3. Edits saved in admin are written back to this folder by default (`EAB_ACF_SAVE_JSON` defaults to on).

Disable saving into the plugin (theme-only exports):

```php
define('EAB_ACF_SAVE_JSON', false);
```
