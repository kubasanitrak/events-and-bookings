# Propojení webu s Fakturoidem

Tento návod je pro provozovatele webu. Technické znalosti nejsou potřeba — stačí umět se přihlásit do Fakturoidu a do WordPressu.

Po dokončení se stane toto:

1. Zákazník si na webu rezervuje místo a zvolí platbu převodem.
2. Web ve Fakturoidu automaticky vytvoří **proformu** (zálohovou fakturu) s variabilním symbolem.
3. Zákazník zaplatí podle QR kódu nebo převodem.
4. Fakturoid platbu spáruje a dá vědět webu.
5. Web rezervaci **potvrdí** a zákazníkovi odešle e-mail.

Druhá (ostrá) faktura se z webu **nevytváří**. Doklad zůstává ten, který vznikl při rezervaci.

---

## Co budete potřebovat

- Účet ve [Fakturoidu](https://app.fakturoid.cz/)
- Přístup do WordPressu s právy administrátora
- **Placený tarif Fakturoidu** — webhooky (oznámení o zaplacení) v tarifu Zdarma nejsou
- Zapnuté **párování plateb s bankou** — bez něj Fakturoid nepozná, že zákazník zaplatil, a web rezervaci nepotvrdí sám

Doporučené tarify:

| Potřeba | Tarif Fakturoidu |
| --- | --- |
| Webhooky (oznámení webu o platbě) | Placený tarif |
| Párování s jedním bankovním účtem | Na každý den |
| Párování s více účty | Na maximum |

Oficiální nápověda Fakturoidu: [API a přístupové údaje](https://www.fakturoid.cz/podpora/automatizace/api), [webhooky](https://www.fakturoid.cz/podpora/automatizace/webhooky), [bankovní účty](https://www.fakturoid.cz/podpora/nastaveni/bankovni-ucty), [párování plateb](https://www.fakturoid.cz/podpora/automatizace/parovani-plateb-s-bankou).

---

## Část A — Fakturoid

### 1. Přihlaste se do správného účtu

Otevřete [app.fakturoid.cz](https://app.fakturoid.cz/) a přihlaste se.

Pokud pod jedním e-mailem vedete více firem (OSVČ i s.r.o.), **přepněte se do účtu, ze kterého chcete fakturovat rezervace**. Přístupové klíče patří vždy k jednomu konkrétnímu účtu.

### 2. Zapište si slug účtu

Po přihlášení se podívejte do adresního řádku prohlížeče. Adresa vypadá takto:

`https://app.fakturoid.cz/vas-slug/dashboard`

Část **`vas-slug`** (mezi `fakturoid.cz/` a dalším lomítkem) je slug účtu. Ten později vložíte do WordPressu.

Příklad: u adresy `https://app.fakturoid.cz/moje-firma/invoices` je slug **`moje-firma`**.

### 3. Vygenerujte Client ID a Client Secret

Tyto dva kódy jsou „klíče od účtu“ pro web. Bez nich plugin nemůže vytvářet proformy.

1. Vpravo nahoře klikněte na **název účtu / svůj profil**.
2. Otevřete **Nastavení**.
3. Zvolte **Uživatelský účet**.
4. Najděte sekci **API přístupy** (někdy **API v3 přístupové údaje**).
5. Do pole názvu napište například `Rezervace na webu`.
6. Klikněte na **Vygenerovat API přístupové údaje** (nebo **Vygenerovat**).
7. Zobrazí se:
   - **Client ID** — delší veřejný identifikátor
   - **Client Secret** — tajný klíč

**Client Secret si ihned zkopírujte** do správce hesel nebo do souboru, který nikam neposíláte. Fakturoid ho později nemusí znovu ukázat v plném znění.

Poznámky:

- **Nepoužívejte** sekci *Napojení na jiné aplikace → OAuth 2 pro tvůrce aplikací*. Ta je pro vývojáře veřejných doplňků, ne pro váš vlastní web.
- Starý „API token“ z dřívějších let **nefunguje**. Potřebujete právě Client ID a Client Secret.

#### Volitelně: povolit správu webhooků u tohoto přístupu

Webhook (krok 6) se běžně zakládá ručně v Nastavení. Pokud byste později chtěli, aby přístup uměl webhooky spravovat i přes API:

1. V **API přístupy** klikněte na **modrý název** právě vytvořeného přístupu.
2. Klikněte na **Upravit**.
3. Zapněte **správu webhooků**.
4. Uložte.

Pro tento návod to není nutné — webhook založíte v další části ručně.

### 4. Zkontrolujte bankovní účet ve Fakturoidu

Bez uloženého účtu Fakturoid nedokáže vystavit doklad s platebními údaji.

1. Jděte do **Nastavení → Banka a párování plateb**.
2. Ověřte, že je tam účet, na který mají zákazníci platit (číslo účtu, ideálně i IBAN).
3. Pokud chybí, klikněte na **Přidat bankovní účet** a vyplňte ho.
4. Stejné číslo účtu (a IBAN) později zadejte i ve WordPressu — z něj se skládá QR kód na platební stránce webu.

### 5. Zapněte párování plateb s bankou

Bez párování Fakturoid neuvidí příchozí platbu a web rezervaci neoznačí jako zaplacenou.

1. Zůstaňte v **Nastavení → Banka a párování plateb**.
2. Zapněte přepínač **Párování plateb**.
3. Klikněte na **Návod k nastavení** u daného účtu.
4. Zkopírujte **párovací e-mail** (vypadá podobně jako `bank.X.Y@fakturoidmail.cz`).
5. V internetovém bankovnictví nastavte zasílání oznámení o příchozích platbách na tento e-mail.

Přesný postup závisí na bance. Fakturoid má návody podle bank: [párování s bankami](https://www.fakturoid.cz/podpora/parovani).

Důležité:

- Párování funguje **dopředu**, ne zpětně. Platby, které přišly před zapnutím, Fakturoid sám nespáruje.
- Párování hledá doklad podle **variabilního symbolu a částky**. Zákazník musí zaplatit přesně podle údajů z webu / proformy.

### 6. Webhook zatím nepřidávejte

Adresu webhooku zkopírujete až z WordPressu (část B, krok 4). Pak se sem vrátíte.

---

## Část B — WordPress (plugin Akce a rezervace)

### 1. Otevřete nastavení pluginu

V administraci webu jděte na:

**Akce a rezervace → Nastavení**

Nejdřív vyplňte sekci **Platby**, potom **Fakturoid**.

### 2. Bankovní převod na webu

V sekci **Platby**:

1. Zaškrtněte **Bankovní převod → Povolit**.
2. Vyplňte **Název účtu**, **Číslo účtu**, **Kód banky** a **IBAN** — stejně jako ve Fakturoidu.
3. Nastavte **Lhůtu platby** (kolik hodin má zákazník na zaplacení).

Tyto údaje se ukazují na platební stránce a v QR kódu. Variabilní symbol vezme web z proformy ve Fakturoidu.

### 3. Údaje Fakturoidu

V sekci **Fakturoid** vyplňte:

| Pole ve WordPressu | Co sem patří |
| --- | --- |
| **Fakturace** | Zaškrtněte *Vytvořit proformu při rezervaci a potvrdit platbu webhookem* |
| **Slug účtu** | Hodnota z adresy, např. `moje-firma` (bez `https://` a bez lomítek) |
| **Client ID** | Z kroku A3 |
| **Client Secret** | Z kroku A3 |
| **User-Agent** | Např. `Events and Bookings (vas@email.cz)` — Fakturoid vyžaduje název a e-mail v závorce |
| **Sazba DPH (%)** | Neplátce DPH: **0**. Plátce: obvykle **21**. Pokud jste ve Fakturoidu neplátce a zadáte 21, vytvoření proformy selže. |

Na konci stránky klikněte na **Uložit změny**.

### 4. Otestujte připojení

Až po uložení:

1. Klikněte na **Otestovat připojení**.
2. Má se objevit zpráva, že připojení funguje (název účtu ve Fakturoidu).

Pokud test selže:

- Zkontrolujte slug (překlepy, žádné mezery).
- Znovu zkopírujte Client ID a Client Secret (bez mezery na začátku nebo na konci).
- Ověřte, že jste údaje vygenerovali v **tom samém** Fakturoid účtu, jehož slug zadáváte.
- Uložte nastavení a zkuste test znovu.

### 5. Zkopírujte Webhook URL a vymyslete tajný kód

Na stejné stránce v sekci Fakturoid uvidíte **Webhook URL**. Vypadá podobně takto:

`https://vas-web.cz/?eab_fakturoid_webhook=1`

Tuto adresu budete vkládat do Fakturoidu.

Do pole **Webhook Authorization** zadejte tajný kód ve tvaru:

`Bearer nejaky-dlouhy-nahodny-retezec`

Příklad (vymyslete si vlastní, nepoužívejte tento):

`Bearer 7kQ9mP2xW4nR8sL1`

Pravidla:

- Musí začínat slovem **`Bearer`**, mezera, a pak náhodný text.
- Stejný text později vložíte i do Fakturoidu.
- Bez vyplněného pole web webhook **odmítne** a rezervace se po platbě nepotvrdí.

Uložte nastavení ve WordPressu.

---

## Část C — Webhook ve Fakturoidu

Webhook je zpráva z Fakturoidu na web ve chvíli, kdy je doklad označený jako zaplacený.

1. Ve Fakturoidu jděte na **Nastavení → Napojení na jiné aplikace → Webhooky**.
2. Klikněte na **Nový webhook**.
3. Do adresy vložte **Webhook URL** z WordPressu (`https://vas-web.cz/?eab_fakturoid_webhook=1`).
4. Do pole autorizace / **auth_header** vložte **přesně stejný** text jako ve WordPressu u *Webhook Authorization* (včetně slova `Bearer` a mezery).
5. Jako událost vyberte **Zaplacena** / **`invoice_paid`**. Ostatní události nechte vypnuté.
6. Webhook uložte a ověřte, že je **aktivní**.

Webhooky jsou jen v placených tarifech. Pokud položku Webhooky nevidíte, tarif webhooky neumožňuje.

---

## Část D — Ověření, že vše funguje

1. Na webu vytvořte **zkušební rezervaci** (ideálně na malou částku).
2. Dokončete pokladnu platbou převodem.
3. Ve Fakturoidu se má objevit **proforma** na zákazníka s variabilním symbolem.
4. Ve WordPressu v **Akce a rezervace → Objednávky** má být u objednávky číslo proformy a VS.
5. Zaplaťte podle QR kódu / údajů na webu **přesně** (částka i variabilní symbol).
6. Ve Fakturoidu se proforma má označit jako zaplacená (po doručení bankovního e-mailu, často během minut).
7. Web má objednávku přepnout na zaplacenou a odeslat potvrzovací e-mail.

Pokud platba nedorazí včas, lze v Objednávkách použít **Potvrdit platbu** ručně. To je záloha, ne běžný provoz.

Když proforma při rezervaci nevznikne, v Objednávkách může být tlačítko **Vytvořit proformu**.

---

## Bezpečnost

- Client Secret a Webhook Authorization **neposílejte e-mailem** v běžné schránce, pokud to není nutné. Lepší je správce hesel nebo osobní předání.
- Klíče nikomu dalšímu nedávejte (účetní je k běžné práci nepotřebuje).
- Pokud máte testovací a ostrý web, použijte **oddělené** Fakturoid účty, nebo alespoň **oddělené webhooky** (jiná URL). Ostrý webhook nesmí mířit na testovací web a naopak.

---

## Časté problémy

**Test připojení ve WordPressu neprojde**  
Špatný slug, Client ID nebo Secret. Údaje musí být z *Uživatelský účet → API přístupy*, ne z OAuth pro tvůrce aplikací.

**Rezervace projde, ale ve Fakturoidu nic není**  
Plugin není zapnutý, chybí některý údaj, nebo API vrátilo chybu. V Objednávkách zkontrolujte sloupec Fakturoid a případnou chybovou hlášku nahoře. U neplátce DPH musí být sazba **0**.

**Zákazník zaplatil, rezervace na webu pořád čeká**  
Nejčastěji: párování banky není zapnuté, webhook chybí, nebo se neshoduje Authorization (`Bearer …` musí být **byte po bytu stejný** ve WordPressu i ve Fakturoidu). Zkontrolujte také, že zákazník poslal správný VS a částku.

**Ve Fakturoidu nevidím Webhooky**  
Tarif Zdarma webhooky nemá. Je potřeba placený tarif.

**Účetní se ptá na druhou fakturu po zaplacení**  
Web druhou fakturu úmyslně nevystavuje. Po zaplacení se jen aktualizuje stejný doklad (PDF v e-mailu).

---

## Rychlý checklist

- [ ] Správný Fakturoid účet (firma, ze které se fakturuje)
- [ ] Zapsaný slug z URL
- [ ] Vygenerované Client ID + Client Secret
- [ ] Bankovní účet ve Fakturoidu
- [ ] Zapnuté párování plateb + e-mail v bance
- [ ] Ve WordPressu vyplněný bankovní účet
- [ ] Ve WordPressu slug, Client ID, Client Secret, User-Agent, DPH
- [ ] Nastavení uložené a **Otestovat připojení** OK
- [ ] Webhook URL + stejný `Bearer …` ve WordPressu i ve Fakturoidu
- [ ] Webhook jen na událost **invoice_paid** / Zaplacena
- [ ] Zkušební rezervace → proforma → platba → potvrzení na webu
