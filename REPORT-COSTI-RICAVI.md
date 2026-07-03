# REPORT-COSTI-RICAVI.md — Componente `tsreport-costi-ricavi`

Report di analisi economica delle commesse: mette a confronto **costo del personale**,
**costi fornitori** e **ricavi**, con colonne selezionabili e vari raggruppamenti.

- **Controller:** `src/componenti/tsreport-costi-ricavi/index.php`
- **Classe/Model:** `src/componenti/tsreport-costi-ricavi/_include/tsreport-costi-ricavi.class.php` (classe `ReportCostiRicavi`)
- **Template:** `template/elenco.html` — **JS:** `template/elenco.js` — **AJAX job per cliente:** `elencojob.php`
- **Permesso:** `checkAbilitazione("TSREPORTCR","TSREPORTCR")`
- **Output:** doppio, HTML (tabella `.griglia`) + CSV (vedi sotto)

---

## Le tre metriche

| Metrica | Origine | Note |
|---------|---------|------|
| **Costo personale** | calcolato: `ore × costo orario` da `ts_ore` | costo orario preferito da `ts_users_annual_cost` per anno, fallback su `frw_extrauserdata.nu_costo`. Molte join (utenti, extrauserdata, job, clienti). Filtrato per `dt_giorno`. |
| **Costi fornitori** | importo memorizzato in `ts_costi.nu_importo` | legato al job (`cd_job`), al fornitore (`cd_fornitore`). Filtrato per `dt_payment`. |
| **Ricavi** | importo memorizzato in `ts_ricavi.nu_importo` | legato solo al job (`cd_job`). Filtrato per `dt_payment`. |

**Scelta architetturale:** la query pesante del costo personale **non** viene toccata;
costi fornitori e ricavi si estraggono con **query aggregate separate** e si uniscono
in memoria tramite mappe di lookup (`mappaImporti()`), prima di generare l'output.

Il legame con il cliente per costi/ricavi passa **solo** dal job
(`ts_costi/ts_ricavi.cd_job → ts_job.id_job → ts_job.cd_cliente`).

---

## I tre tipi di report (parametro `gruppo`)

| `gruppo` | Vista | Colonne base |
|----------|-------|--------------|
| `cd_cliente` | **By client** — aggregato per cliente | `Client` + metriche attive + riga totali |
| `cd_job` | **By job** — aggregato per commessa | `Code · Client · Job` + metriche attive + riga totali |
| `std` | **Standard (x mesi)** — una riga per job, una colonna per mese | `Code · Client · Job` + colonne mensili |

> Le colonne **Ore** e **Giorni**, presenti in origine su *By client* / *By job*, sono
> state rimosse: questi report mostrano ora solo gli importi economici.

### Report `std` — sotto-colonne per mese
Ogni mese si espande in **N sotto-colonne**, una per metrica selezionata:
- con **una sola** metrica → intestazione a riga singola (mese);
- con **più** metriche → intestazione a **due righe** (mese in `colspan`, metriche sotto:
  `Pers` / `Forn` / `Ric`).

Il costo personale mensile usa la sub-query per-job esistente; fornitori/ricavi arrivano
dalla mappa `mese` (`id_job → YYYY-MM → totale`). Vincolo lato JS: intervallo ≤ 12 mesi.

---

## Filtri del pannello

| Campo | Tipo | Note |
|-------|------|------|
| **From / To** (`dal`/`al`) | date | intervallo; default `dal` = 1° gennaio anno corrente |
| **Client** (`cliente`) | optionlist | `onchange` ricarica i job via `elencojob.php` |
| **Job** (`job`) | optionlist | valori speciali: `""`=tutti, `-1`=tutti OFF, `-2`=tutti ON |
| **Tipo di report** (`gruppo`) | optionlist | `std` / `cd_cliente` / `cd_job` |
| **Status** (`stato`) | optionlist | filtro stato importi (vedi sotto) |
| **Columns** (`col_pers`/`col_forn`/`col_ric`) | checkbox | selezione colonne (vedi sotto) |

### Selezione colonne
Tre checkbox indipendenti: **Personnel cost**, **Supplier costs**, **Revenues**.
- Default (prima apertura): tutte e tre attive.
- In ricerca valgono solo le checkbox inviate; se nessuna è selezionata → fallback su
  costo personale. Guard lato JS: blocca il submit se nessuna colonna è scelta.
- Logica centralizzata in `colonneSelezionate($dati)` → `array('pers'=>bool,'forn'=>bool,'ric'=>bool)`.

### Filtro Status (`stato`) — soglie cumulative
Agisce **solo su costi fornitori e ricavi** (hanno la enum `en_status`); il **costo
personale non è influenzato** (le ore non hanno stato). Mappatura in `statiInclusi()`:

| Scelta | `en_status` inclusi |
|--------|---------------------|
| **All** (`all`, default) | estimate, progress claim, invoice emitted, invoice payed |
| **Progress claim** (`progressclaim`) | progress claim, invoice emitted, invoice payed |
| **Invoice emitted** (`invoice`) | invoice emitted, invoice payed |
| **Invoice paid** (`payed`) | invoice payed |

Il filtro è applicato nel punto unico `filtroImporti()`, quindi vale per tutti e tre i
report.

---

## Formattazione e output

- **Importi HTML:** metodo `money($val,$dec)`. Se il valore arrotondato è **zero**,
  mostra un **`-` grigio** invece di `0€` (tabella più leggibile); il simbolo `MONEY` è
  reso in grigio.
- **CSV:** generato in parallelo all'HTML negli stessi cicli (valori sempre numerici,
  nessun `-`), separatore `;`, campi tra virgolette. È incorporato in un pulsante
  *Download CSV* come **data-URI base64** (`base64_encode(mb_convert_encoding(...,'ISO-8859-1','UTF-8'))`),
  senza endpoint separato. Nome file: `report-<gruppo>-<data>.csv`.

---

## Metodi chiave della classe

| Metodo | Ruolo |
|--------|-------|
| `getPannello($dati)` | costruisce il form dei filtri e, se `op==cerca`, inserisce l'output di `eseguiRicerca` |
| `eseguiRicerca($dati,$params)` | esegue il report per il `gruppo` scelto e ritorna HTML+CSV |
| `colonneSelezionate($dati)` | stato delle 3 checkbox colonne |
| `statiInclusi($stato)` | soglia → elenco `en_status` |
| `filtroImporti($dati,$ax,$aj)` | WHERE condiviso per gli importi (cliente/job/date/stato) |
| `mappaImporti($tabella,$dati,$mode)` | mappe aggregate (`cliente`/`job`/`mese`) da `ts_costi`/`ts_ricavi` |
| `money($val,$dec)` | formattazione importo con `-` grigio per lo zero |

---

## Tabelle coinvolte

`ts_ore`, `frw_utenti`, `frw_extrauserdata`, `ts_users_annual_cost` (costo personale) ·
`ts_job`, `ts_clienti` (dimensioni) · `ts_costi`, `ts_fornitori` (costi fornitori) ·
`ts_ricavi` (ricavi).

## i18n

Label in `data/lang/timy.it.lang.txt` / `timy.en.lang.txt` (formato CSV quotato, CRLF):
`Personnel cost`, `Supplier costs`, `Revenues`, `Columns`, `Pers`, `Forn`/`Suppl`,
`Ric`/`Rev`, `Select at least one column`. Riusate dal core/altri componenti: `Status`,
`All`, `Progress claim`, `Invoice emitted`, `Invoice paid`, `Cost`, `Client`, `Job`, `Code`.
