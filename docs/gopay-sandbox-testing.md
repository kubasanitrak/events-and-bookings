# GoPay sandbox testing

Checklist for **Events and Bookings** card payments (GoPay REST API v3).  
Plugin version **0.7.0+**. No GP WebPay.

Official references:

- [Testing payments in the sandbox](https://help.gopay.com/en/knowledge-base/integration-of-payment-gateway/integration-of-payment-gateway-1/testing-payments-in-the-sandbox)
- [Essential Guide to Integration](https://help.gopay.com/en/knowledge-base/integration-of-payment-gateway/essential-guide-to-integration)
- [GoPay API docs](https://doc.gopay.com/)

---

## 0. Prerequisites

| # | Item | Notes |
|---|------|--------|
| ☐ | GoPay merchant account | Register at [gopay.com](https://www.gopay.com) if needed |
| ☐ | Sandbox credentials | GoPay business account → **Nastavení → E-shopy → Sandbox** → GoID, Client ID, Client Secret |
| ☐ | Plugin active | Load any front page once (DB migration + auto pages) |
| ☐ | HTTPS site URL | **WP Nastavení → Obecné** → `https://…` |
| ☐ | Public notification URL | GoPay calls your server asynchronously — local MAMP needs **ngrok** or staging |
| ☐ | Test member | Registered, verified, logged in |
| ☐ | Bookable event | Free capacity, price set |
| ☐ | Mail | Mailhog or real inbox for order / payment e-mails |

**Tip:** Disable Fakturoid for first card tests (**Akce a rezervace → Nastavení → Fakturoid** off). Enable after GoPay flow works.

**Admin shortcut:** **Akce a rezervace → Nastavení → GoPay** → **Otestovat připojení** (OAuth ping + resolved URLs).

---

## 1. Plugin settings

**WP Admin → Akce a rezervace → Nastavení → Platby**

| Setting | Sandbox value |
|---------|----------------|
| Povolit platbu kartou | ☑ |
| GoID | From GoPay sandbox e-shop |
| Client ID | Sandbox |
| Client Secret | Sandbox |
| Sandbox (testovací brána) | ☑ **must stay on** |

With all four credential fields filled, checkout shows **Karta (GoPay)**.

Optional for isolated card testing: disable **Bankovní převod → Povolit**.

---

## 2. URLs the plugin sends to GoPay

Replace `https://your-site.test` with **Nastavení → Obecné → Adresa webu**.

### Notification URL (server callback)

Built by the plugin:

```text
https://your-site.test/?eab_gopay_notify=1
```

GoPay appends the payment id:

```text
https://your-site.test/?eab_gopay_notify=1&id=GOPAY_PAYMENT_ID
```

- Must be **publicly reachable** (not only in your browser).
- Plugin responds `200` with body `OK`.
- Shown in admin after **Otestovat připojení**.

**Pre-flight check:**

```bash
curl -I "https://your-site.test/?eab_gopay_notify=1&id=test"
```

Expect HTTP 200.

### Return URL (customer browser)

Plugin sends per order:

```text
https://your-site.test/platba-uspesna/?order=ORDER_ID
```

GoPay adds `id` on return:

```text
https://your-site.test/platba-uspesna/?order=42&id=GOPAY_PAYMENT_ID
```

| Page | Slug | Shortcode |
|------|------|-----------|
| Success | `/platba-uspesna/` | `[eab_payment_success]` |
| Failed | `/platba-neuspesna/` | `[eab_payment_failed]` |

Auto-created on plugin load if missing.

### API endpoint (internal)

| Mode | URL |
|------|-----|
| Sandbox ☑ | `https://gw.sandbox.gopay.com/api` |
| Production ☐ | `https://gate.gopay.com/api` |

---

## 3. GoPay portal

In sandbox e-shop settings, confirm:

| ☐ | Notification URL matches `/?eab_gopay_notify=1` on your public domain |
| ☐ | Return URL allowed (`/platba-uspesna/`) |
| ☐ | Currency **CZK** enabled |
| ☐ | Card payment method enabled |

---

## 4. Test cards and amounts

| Card | PAN | Issuer |
|------|-----|--------|
| MasterCard | `5447380000000006` | CZE |
| VISA | `4444444444444448` | POL |

| Field | Value |
|-------|--------|
| CVV/CVC | Any 3 digits (e.g. `123`) |
| Expiry | Any future date (e.g. `12/28`) |

**Outcome is driven by amount (last two digits):**

| Amount ends with | Example | Result |
|------------------|---------|--------|
| `*00` | **500,00 Kč** | Success → `PAID` |
| `*04` | **504,00 Kč** | Declined → `REFUSED` |

Set event price or optional services to hit exact totals.

---

## 5. Test scenarios

### A — Happy path

| Step | Action | Expected |
|------|--------|----------|
| 1 | Basket → `/pokladna/` | Checkout loads |
| 2 | Attendees, **Karta (GoPay)** | |
| 3 | Submit | Redirect to `gw.sandbox.gopay.com` |
| 4 | MC `544738…0006`, amount ending **00** | |
| 5 | Return to `/platba-uspesna/` | “Platba byla úspěšná” |
| 6 | **Objednávky** | Status **paid**, method GoPay |
| 7 | Order row | `transaction_id` = GoPay payment id |
| 8 | Capacity | Spots **confirmed** |
| 9 | E-mail | Payment confirmed |

### B — Declined card

| Step | Action | Expected |
|------|--------|----------|
| 1 | Order total ending **04** (e.g. 504 Kč) | |
| 2 | Pay on GoPay | Declined |
| 3 | Redirect | `/platba-neuspesna/` |
| 4 | **Objednávky** | Status **failed**, holds released |

### C — Cancelled payment

| Step | Action | Expected |
|------|--------|----------|
| 1 | Start GoPay payment | |
| 2 | Cancel on gateway | |
| 3 | Order | **failed** or **processing** until expiry |

### D — Notification without browser return

| Step | Action | Expected |
|------|--------|----------|
| 1 | Complete payment on GoPay | |
| 2 | Close tab before return page | |
| 3 | Wait ~30 s, refresh **Objednávky** | Order **paid** via notification |

If D fails but A works → notification URL not reachable.

### E — Idempotency

Reload success URL after paid order → stays paid, no duplicate e-mails.

### F — Fakturoid (after A–E)

Enable Fakturoid → repeat happy path → invoice PDF in confirmation e-mail.

### G — Bank transfer regression

Bank transfer + admin **Potvrdit platbu** still works alongside GoPay.

---

## 6. Troubleshooting

| Symptom | Check |
|---------|--------|
| No “Karta (GoPay)” | All GoPay fields + checkbox; run **Otestovat připojení** |
| OAuth failed | Client ID/Secret, sandbox toggle, outbound HTTPS to `gw.sandbox.gopay.com` |
| Create payment failed | GoID, amount ≥ 1 Kč; `eab_logs` type `gopay_create_failed` |
| Stuck **processing** | Notification URL not public; test scenario D |
| Paid in GoPay, not in WP | `transaction_id` matches GoPay `id`; curl notification URL |
| Wrong return page | `eab_page_ids.payment_success`; flush permalinks |

```sql
SELECT id, order_number, status, payment_method, transaction_id, paid_at
FROM wp_eab_orders ORDER BY id DESC LIMIT 5;
```

---

## 7. GoPay sign-off (before production)

| ☐ | Successful sandbox payment (amount `*00`) |
| ☐ | Declined payment (`*04`) |
| ☐ | Cancel flow |
| ☐ | Notification completes order without browser |
| ☐ | Order statuses match GoPay in admin |
| ☐ | Payment confirmation e-mail |
| ☐ | E-mail **integrace@gopay.cz** for production credentials |

---

## 8. Production cutover

| Step | Action |
|------|--------|
| 1 | Receive production GoID, Client ID, Client Secret |
| 2 | Replace credentials in **Nastavení → Platby** |
| 3 | Uncheck **Sandbox (testovací brána)** |
| 4 | Register production notification URL in GoPay |
| 5 | One small real test payment |
| 6 | Enable Fakturoid on production if used |

No code changes — settings only.

---

## 9. Suggested test order

```text
1. Configure settings + **Otestovat připojení**
2. curl notification URL → 200
3. Scenario A (500 Kč)
4. Scenario D (close tab)
5. Scenario B (504 Kč)
6. Scenario F (Fakturoid)
7. Contact integrace@gopay.cz for production keys
```
