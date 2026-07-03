# CLAUDE.md - Framewotk | Timy

## Project Overview

This project include the core of a PHP framework that I use for several projects.
Plus the folders of my timesheet (Timy) software.

Built with PHP, MySQL, and JavaScript on a custom personal backend framework (MVC-style, built from scratch). Not publicly distributed;

## Technology Stack

- **Backend:** PHP, MySQLi. **Composer is used in the core** for the autoload:
  `config.php` requires `src/vendor/autoload.php` (e.g. PHPMailer ships via Composer).
  The `composer.json` lives in the framework core; `src/vendor/` is present locally.
- **Frontend:** Vanilla JavaScript, HTML templates with `##placeholder##` syntax
- **Database:** MySQL
- **Auth:** Session-based with role/profile system
- **i18n:** CSV `.lang.txt` files (`"KEY","VALUE"`), `{label}` syntax in templates

## Repository Layout

```
splunkphp/
├── index.php                  # Entry point → redirects to src/index.php
├── pons-settings.php          # Runtime DB credentials (gitignored)
├── data/
│   ├── lang/                  # Translation files (en.lang.txt, it.lang.txt, ...)
│   ├── logs/                  # App logs (gitignored)
│   ├── dbimg/                 # Uploaded media (gitignored)
│   └── basic-theme/           # Active theme assets
└── src/
    ├── index.php              # Main router: loads default component per user
    ├── _include/              # Core framework classes
    └── componenti/            # Application modules (many components in different projects, here only the ones that belongs to the core)
```

## Core Framework Files

| File | Purpose |
|------|---------|
| `src/_include/config.php` | Loads settings, defines constants, includes classes |
| `src/_include/comode.php` | ~1400-line utility library: DB helpers, template loading, validation |
| `src/_include/ambiente.class.php` | Template engine: variable replacement |
| `src/_include/session.class.php` | Session management (MD5-prefixed for multi-install isolation) |
| `src/_include/login.class.php` | Authentication against `frw_utenti` table |
| `src/_include/crudbase.class.php` | Base CRUD class for all components |
| `src/_include/grid.class.php` | Paginated/filterable data grid renderer |
| `src/_include/formcampi.class.php` | Form field generators |
| `src/_include/logger.class.php` | File-based logging with rotation |
| `src/_include/cryptor.class.php` | Encryption utilities |

## Component Architecture (MVC Pattern)

Each component lives under `src/componenti/{name}/` and follows this structure:

```
componenti/{name}/
├── index.php              # Controller: handles $command GET param, renders templates
├── _include/
│   └── {name}.class.php   # Model/Manager class
├── ajax/                  # AJAX endpoints (return JSON)
└── template/              # HTML files with ##placeholder## variables
```

**Request flow:**
1. `src/index.php` → reads user's default component from DB → redirects
2. `componenti/{name}/index.php` → reads `$command` → processes logic → renders template
3. Templates use `##variable##` replaced by `loadTemplateAndParse()` / `translateHtml()`

## Active Components (SplunkPHP-specific)

| Component | Purpose |
|-----------|---------|
| `debugger` | Debug tools |
| `frwcomponenti` | Framework: component management |
| `frwconstants` | Framework: system constants |
| `frwmoduli` | Framework: module management |
| `frwprofili` | Framework: user profiles/roles |
| `frwvars` | Framework: variable storage |
| `gestioneutenti` | Framework: User management |
| `gestioneutentitimy` | User management based on gestioneutenti |
| `installtimy` | Installation wizard |
| `tsclienti` | Clients component |
| `tsjob` | Jobs (projects) component |
| `tsreparti` | Departments component |
| `tsore2` | Timesheet component |
| `tsoremancanti` | Counts hours not inserted in timesheet component |
| `tschehofatto` | My reports component |
| `tsreport` | Admins report component |
| `tstipiore` | Types of hours component |
| `tsplanning` | Activitis planner component |
| `tstasks` | Todos component |
| `tslists` | Lists of todos component |
| `tsnotes` | Notes component |
| `tsfornitori` | Suppliers component |
| `tsricavi` | Job revenues component (tracks an edit history in `ts_ricavi_storico`) |
| `tscosti` | Job costs component |
| `tsreport-costi-ricavi` | Costs/revenues report component (personnel cost, supplier costs, revenues; selectable columns) — see [REPORT-COSTI-RICAVI.md](REPORT-COSTI-RICAVI.md) |



## Database Conventions

- Global MySQLi connection: `$conn` (defined in config.php)
- Table prefix: configurable via `DB_PREFIX` constant
- Core tables: `frw_vars`, `frw_utenti`, `frw_componenti`, `frw_moduli`, `frw_profili`, `frw_ute_fun`, `frw_extrauserdata`
- DB helper functions in `comode.php`:
  - `execute_scalar($sql)` — single value
  - `execute_row($sql)` — single row as array
  - `execute_query($sql)` — result set

**No migration system.** Schema changes are applied manually or via install wizard.

### Revenue edit history (`ts_ricavi_storico`)

`ts_ricavi` holds one row per revenue = its **current state** (the report and the list read
this table as-is). Every save of a revenue also writes a **snapshot** into `ts_ricavi_storico`
(`cd_ricavo`, `cd_utente`, `dt_modifica` + the tracked fields `dt_payment`, `nu_importo`,
`en_status`, `de_label`), so the evolution of a revenue (estimate → progress claim → invoice
emitted → invoice paid) can be shown in a read-only panel under the edit form. A snapshot is
written on insert (initial version) and on update only when a tracked field actually changed;
deleting a revenue also removes its history. Because `ts_ricavi` still stores only the latest
value, `tsreport-costi-ricavi` needs no change — it already sums the current amount.

## Authentication & Authorization

- Profile IDs: `20` = admin, `999999` = superadmin
- Permissions stored in `frw_ute_fun` (user ↔ feature mapping)
- Custom auth extension point: `login-custom.class-example.php`
- Session prefix is MD5-hashed to isolate multiple installations on the same server

## Template System

- Templates: `.html` files in `template/` folders
- Variable placeholders: `##variableName##`
- i18n placeholders: `{LABEL_KEY}` replaced by `translateHtml()`
- Global replacements: `$defaultReplace` array
- JS/CSS assets loaded per-component via template includes

### Standard template placeholders

| Placeholder | Valore |
|-------------|--------|
| `##root##` | Percorso root dell'applicazione (es. `/`) |
| `##JQUERYINCLUDE##` | Tag `<script>` per jQuery |
| `##VER##` | Versione per cache-busting degli asset |
| `##TITLE##` | Titolo del componente |

### Asset globali disponibili nei template

- **jQuery**: via `##JQUERYINCLUDE##`
- **Chart.js**: `##root##src/assets/chartjs/chart.min.js`
- **comode.js**: `##root##src/template/comode.js` (utility JS lato client)

### CSS per componente

I componenti con UI elaborata usano un foglio di stile dedicato `template/style.css`, incluso nel template con:
```html
<link rel="stylesheet" type="text/css" href="./template/style.css"/>
```
Le classi CSS vanno prefissate con il nome del componente (es. `fd-` per `fontidashboard`) per evitare conflitti.

### Pattern tooltip

```html
<span data-rel="Testo del tooltip" class="icon-help-circled"></span>
```
Il framework usa un icon font con classi `icon-*` (es. `icon-help-circled`, `icon-cancel`, ecc.).

### AJAX endpoints

Alcuni componenti usano `ajax.php` direttamente nella root del componente (non nella sottocartella `ajax/`). Entrambi i pattern sono validi; `ajax.php` diretto è più comune nei componenti recenti.

### Campi dei form

I generatori di campo (`testo`, `data`, `optionlist`, `autocomplete`, ecc.) sono in
`src/_include/formcampi.class.php`. Per firme dei costruttori, esempi d'uso e note pratiche
(incluso il pattern `autocomplete` + endpoint AJAX) vedi [FORMCAMPI-FIELDS.md](FORMCAMPI-FIELDS.md).
**Se incontri un campo di cui non conosci l'uso, chiedi all'utente il path di un altro
progetto del framework che lo usa e replica quel pattern, invece di inventare.**

## Language Files

Located in `data/lang/`. Format: **CSV with quoted values** — `"KEY","TRANSLATION"`
(an optional third column carries a version, e.g. `"...","...","4.2.8"`). The first line
is the header `LABEL,TRANSLATION`. Files use CRLF line endings.
Active files: `en.lang.txt` / `it.lang.txt` (core) and the module variants
`base.{en,it}.lang.txt` and `timy.{en,it}.lang.txt`. **Timy-specific labels live in
`timy.it.lang.txt` / `timy.en.lang.txt`** (e.g. component labels like `Supplier`,
`Revenues`, `Costs`). In templates the key is referenced as `{KEY}` and resolved by
`translateHtml()`.

## Configuration

Runtime config in `pons-settings.php` (gitignored — never commit this file):
```php
define('DB_HOST', '...');
define('DB_USER', '...');
define('DB_PASS', '...');
define('DB_NAME', '...');
```

Template for new installs: `pons-settings-install.php`.

## Security Notes

- SQL injection detection patterns in `config.php` (blocks suspicious input)
- Magic quotes simulation via `addslashes()` on input
- `.htaccess` blocks PHP execution in `data/` subdirectories
- Never put user input directly into SQL — use the existing escaping helpers in `comode.php`

## Development Guidelines

- **Composer is available** (autoload wired in `config.php` via `src/vendor/autoload.php`),
  but adding new dependencies must be deliberate and surgical — this is a stable
  production system, so don't pull in vendor packages casually.
- **No framework upgrades** — this is a stable production system; changes must be surgical
- **Component pattern is fixed** — new features go into new components or extend existing ones following the same `index.php` + class + templates structure
- **DB changes are manual** — document any schema changes clearly; there is no migration runner
- **Test in Italian context** — primary language is Italian; string labels go in both `it.lang.txt` and `en.lang.txt`
- **Gitignored files:** `pons-settings.php`, `data/logs/*`, `data/dbimg/*`, `src/vendor/*`, `src/assets/*`, `src/lib/*`
- This branch (`splunkphp`) excludes AlertManager, Timy, AdAdmin, and CMS components — do not add them back
