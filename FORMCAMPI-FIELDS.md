# Form fields reference — `src/_include/formcampi.class.php`

Reference for the form-field generator classes used in component `getDettaglio()` methods.
All field classes extend `pezzoDelForm` and expose `gettag()` (renders the HTML/JS) plus
common properties. They are wired into a `form` object and their rendered tags are
injected into the `dettaglio.html` template via `##placeholder##` replacement.

## Working convention

**When you meet a field type you don't know how to drive, ask the user for the path of
another framework project that already uses it, then study and replicate that pattern.**
Do not invent usage. Example: the `autocomplete` field is used in the *alertmanager*
project at `alcompanies/_include/companies.class.php` (field) + `alcompanies/ajax/listcomuni.php`
(endpoint) — that is where the timy `tsricavi`/`tscosti` autocomplete was modeled from.

## Common pattern

```php
$objform = new form();                       // name defaults to "dati"

$campo = new testo("de_nome", $dati["de_nome"], 50, 50);
$campo->obbligatorio = 1;                     // marks required (JS validation)
$campo->label = "'Nome'";                     // label for the JS alert; use "'{Key}'" to translate
$objform->addControllo($campo);               // register the field

// ... build $html from template ...
$html = str_replace("##STARTFORM##", $objform->startform(), $html);
$html = str_replace("##de_nome##",   $campo->gettag(),      $html);
$html = str_replace("##ENDFORM##",   $objform->endform(),   $html);
```

- Auto JS validation is added by `addControllo()` only for: `intero`, `testo`,
  `password`, `numerointero`, `numerodecimale`. Other types (`optionlist`,
  `autocomplete`, `data`, ...) have no built-in client check.
- Custom validation: `addControllo($obj, "!myJsCheck()", "{Error message}")`.
- The detail template's *save* link usually calls `checkConStato()` (defined in
  `src/template/comode.js`), which wraps the generated `checkForm()`.
- The submit JS targets the form by name (`document.dati...`); pass `$objform->name` to
  fields that compose values client-side (`data`, `dataOra`, `orario`).

## Field classes (constructor signatures)

| Class | Constructor | Notes |
|-------|-------------|-------|
| `hidden` | `($name='', $value=0)` | Hidden input. |
| `testo` | `($name='', $value="", $maxlength=null, $size=null)` | Text input. |
| `intero` | `($name='', $value=0, $maxlength=10, $size=10)` | Integer text input. |
| `numerointero` | *(extends `intero`)* | Positive integer (JS `testNumericoIntPos`). |
| `numerodecimale` | `($name='', $value="", $maxlength=10, $size=10, $decimali=2)` | Decimal; client-formats to `$decimali`. |
| `email` | `($name='', $value="", $maxlength=10, $size=10)` | *(extends `testo`)* |
| `urllink` | `($name='', $value="", $maxlength=10, $size=10)` | *(extends `testo`)* |
| `password` | `($name='', $value="", $maxlength=10, $size=10)` | Password input; optional strength check. |
| `areatesto` | `($name='', $value="", $rows=null, $columns=null)` | Textarea. |
| `richtext` | `($name='', $value="", $width="", $height="", $toolbar="")` | WYSIWYG editor. |
| `data` | `($name='', $value="", $formatoIN="gg-mm-aaaa", $formname="dati")` | Day/Month/Year inputs + hidden + jQuery UI datepicker. See note below. |
| `dataOra` | `($name='', $value="", $formatoIN="gg-mm-aaaa", $formname="")` | Date + time. |
| `orario` | `($name='', $value="", $formname="")` | Time (hh:mm). Pass `$objform->name`. |
| `optionlist` | `($name, $valore='', $arrayvalori=array())` | `<select>`. See `loadSqlOptions` below. |
| `checkboxlist` | `($name, $valore=[], $arrayvalori=[])` | Multiple checkboxes. |
| `radiolist` | `($name, $valore='', $arrayvalori=array())` | Radio group. |
| `colorlist` | `($name, $valore='', $arrayvalori=array())` | *(extends `radiolist`)* color swatches. |
| `checkbox` | `($name, $valore='', $checked=true)` | Single checkbox. |
| `autocomplete` | `($name='', $value="", $maxlength=10, $size=10, $url="")` | *(extends `testo`)* jQuery UI autocomplete. See below. |
| `fileupload` | `($name='', $size=30, $value="")` | File input. |
| `fileupload2` | `($name='', $val="", $params=array())` | File input (advanced/params). |
| `submit` | `($name='', $value="submit", $onclick="checkForm()")` | Submit button. |
| `form` | `($name="dati", $honeypot="")` | Form container; `startform()` / `endform()`. |

## Notes on specific fields

### `data` (and `dataOra`)
- `$formatoIN` describes the format of the **incoming** `$value` (how to parse it into
  day/month/year). For a MySQL `DATE` column read from the DB (`YYYY-MM-DD`) use
  `"aaaa-mm-gg"`. The on-screen display order is driven by the `DATEFORMAT` constant, not
  by `$formatoIN`.
- A **non-empty** value is required by the constructor: with an empty string the parse
  throws "Undefined array key". For new records default it first:
  ```php
  $valore = $dati["dt_payment"];
  if ($valore=="") $valore = date("Y-m-d");
  $dt = new data("dt_payment", $valore, "aaaa-mm-gg", $objform->name);
  ```
- The composed hidden field is posted as `YYYY-MM-DD`, ready for a MySQL `DATE` column.

### `optionlist`
- Static options: `new optionlist("en_status", $val, array("a"=>"{Label A}", ...))`.
- DB-driven options:
  ```php
  $sel = new optionlist("cd_x", $val);
  $sel->loadSqlOptions("SELECT id, nome FROM ...", "id", "nome", "{choose}");
  ```
  `loadSqlOptions($sql, $idField, $labelField, $emptyLabel)` — `$emptyLabel` adds a
  leading `--label--` empty option. Set `$sel->isMultiple = true` for multi-select.

### `autocomplete` (text search backed by AJAX)
Use when an `optionlist` would render too many rows.

- Field: `new autocomplete("cd_job", $dati["cd_job"], 100, 60, "../tsjob/ajax/jobsearch.php")`
  (the `$url` is resolved by the browser relative to the component's `index.php`; share a
  single endpoint instead of duplicating one per component).
- It renders a visible input `cd_job_ac` plus a hidden `cd_job` (holds the selected id);
  `updateAndInsert` reads the hidden as usual (`(int)$arDati["cd_job"]`).
- The `$url` endpoint is called two ways:
  - `?term=<text>` → must return a **JSON array** of `{"id":..., "value":"label"}`.
  - `?id=<value>` (on load, for edit) → must return the **JSON string** label to prefill.
- Requires jQuery UI (loaded via `##JQUERYINCLUDE##`, already in the detail templates).

Endpoint template (shared at `tsjob/ajax/jobsearch.php`, modeled on
*alertmanager* `alcompanies/ajax/listcomuni.php`):
```php
<?php
header('Content-Type: application/json');
$root="../../../../";
include($root."src/_include/config.php");

$term = postget("term","");
$id   = postget("id","0");

if($term!="") {
    $stmt = $conn->prepare("SELECT id_job, CONCAT(de_codice,' - ',de_nomejob) AS nome
        FROM ".DB_PREFIX."ts_job WHERE de_codice LIKE ? OR de_nomejob LIKE ?
        ORDER BY de_codice LIMIT 30");
    $search = "%".$term."%";
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $out = array();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) $out[] = array("id"=>$row['id_job'], "value"=>$row['nome']);
    echo json_encode($out);
} else {
    if($id!="0")
        echo json_encode(execute_scalar("SELECT CONCAT(de_codice,' - ',de_nomejob)
            FROM ".DB_PREFIX."ts_job WHERE id_job='".(int)$id."'",""));
}
```
