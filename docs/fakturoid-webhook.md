# Fakturoid — proforma + webhook

Flow: checkout (bank transfer) → Fakturoid creates **proforma** → customer pays by QR/transfer → Fakturoid pairs by VS → webhook `invoice_paid` → plugin marks order paid + confirmation e-mail.

## Plugin settings

**Akce a rezervace → Nastavení → Fakturoid**

1. Enable *Vytvořit proformu při rezervaci…*
2. **Slug účtu** — from the Fakturoid URL: `https://app.fakturoid.cz/{slug}/…`
3. **Client ID** + **Client Secret** — *Nastavení → Uživatelský účet → API přístupy* (OAuth Client Credentials; prefer the access that has webhook API management if you manage webhooks via API)
4. **User-Agent** — e.g. `Events and Bookings (vas@email.cz)`
5. Copy **Webhook URL** into Fakturoid
6. Set **Webhook Authorization** to the same value as Fakturoid `auth_header` (e.g. `Bearer your-secret`)
7. Use **Otestovat připojení** after saving

Without Authorization configured, the endpoint returns `503` and ignores payloads.

## Fakturoid account

1. Create webhook: event **`invoice_paid`**, URL from settings, `auth_header` matching the plugin field  
   Docs: https://www.fakturoid.cz/api/v3/webhooks
2. Enable bank payment pairing for your account  
   Docs: https://www.fakturoid.cz/podpora/parovani
3. Use **separate** Fakturoid firms / webhook URLs for staging vs production

Proformas are created with `proforma_followup_document: none` so Fakturoid does **not** auto-issue a second final invoice. The site only refreshes PDF/meta on the same document after payment.

## Failure behaviour

- If proforma API fails at checkout, the order still stays `awaiting_payment` and the QR page uses local bank details + fallback VS (digits from order number). Admin **Potvrdit platbu** remains available.
- Duplicate webhooks are ignored (order already `paid` + `Idempotency-Key` cache).

## GoPay

Card payments are temporarily disabled (`eab_gopay_available` filter defaults to false). Credentials remain under a collapsed settings block.
