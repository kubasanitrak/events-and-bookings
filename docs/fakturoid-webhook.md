# Fakturoid — proforma + webhook

Flow: checkout (bank transfer) → Fakturoid creates **proforma** → customer pays by QR/transfer → Fakturoid pairs by VS → webhook `invoice_paid` → plugin marks order paid + confirmation e-mail.

## Plugin settings

**Akce a rezervace → Nastavení → Fakturoid**

1. Enable *Vytvořit proformu při rezervaci…*
2. Fill slug, API e-mail, API token, User-Agent, VAT rate
3. Copy **Webhook URL** into Fakturoid
4. Set **Webhook Authorization** to the same value as Fakturoid `auth_header` (e.g. `Bearer your-secret`)

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
