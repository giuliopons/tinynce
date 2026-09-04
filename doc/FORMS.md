# Form fields — `src/_include/formcampi.class.php`

Reference for the form-field generator classes, typically used in components' manager
`getDettaglio()` methods. Every field class extends `pezzoDelForm` and exposes `gettag()`
(which renders the HTML/JS) plus common properties. They are registered on a `form` object
and their rendered tag is injected into the `dettaglio.html` template via `##placeholder##`
replacement.

## Basic usage convention

```php
$objform = new form();                       // name defaults to "dati"

$campo = new testo("de_nome", $dati["de_nome"], 50, 50);
$campo->obbligatorio = 1;                     // marks the field as required (JS validation)
$campo->label = "'Nome'";                     // label for the JS alert; use "'{Key}'" to translate it
$objform->addControllo($campo);               // registers the field

// ... building $html from the template ...
$html = str_replace("##STARTFORM##", $objform->startform(), $html);
$html = str_replace("##de_nome##",   $campo->gettag(),      $html);
$html = str_replace("##ENDFORM##",   $objform->endform(),   $html);
```

- Automatic JS validation is added by `addControllo()` only for: `intero`, `testo`,
  `password`, `numerointero`, `numerodecimale`. Other types (`optionlist`, `autocomplete`,
  `data`, ...) have no built-in client-side check.
- Custom validation: `addControllo($obj, "!myJsCheck()", "{Error message}")`.
- The detail template's save link typically calls `checkConStato()` (defined in
  `src/template/comode.js`), which wraps the generated `checkForm()`.
- Submit JS targets the form by name (`document.dati...`): pass `$objform->name` to fields
  that compose their value client-side (`data`, `dataOra`, `orario`).

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
| `richtext` | `($name='', $value="", $width="", $height="", $toolbar="")` | WYSIWYG editor (TinyMCE). |
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
| `fileupload2` | `($name='', $val="", $params=array())` | File input (advanced/parametric). |
| `submit` | `($name='', $value="submit", $onclick="checkForm()")` | Submit button. |
| `form` | `($name="dati", $honeypot="")` | Form container; `startform()` / `endform()`. |

## Notes on specific fields

### `data` (and `dataOra`)
- `$formatoIN` describes the format of the **incoming** `$value` (how to parse it into
  day/month/year). For a MySQL `DATE` column read from the DB (`YYYY-MM-DD`), use
  `"aaaa-mm-gg"`. The on-screen display order is driven by the `DATEFORMAT` constant, not
  by `$formatoIN`.
- A **non-empty** value is required by the constructor: with an empty string the parsing
  throws "Undefined array key". For new records, default it first:
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
  `loadSqlOptions($sql, $idField, $labelField, $emptyLabel)` — `$emptyLabel` adds a leading
  empty `--label--` option. Set `$sel->isMultiple = true` for a multi-select.

### `autocomplete` (text search backed by AJAX)
Use when an `optionlist` would render too many rows.

- Field: `new autocomplete("cd_job", $dati["cd_job"], 100, 60, "../componentname/ajax/endpoint.php")`
  (`$url` is resolved by the browser relative to the component's `index.php`; share a single
  endpoint instead of duplicating one per component when multiple forms in the same domain
  search for the same entity).
- It renders a visible input `{name}_ac` plus a hidden `{name}` (holds the selected id); the
  save method reads the hidden as usual (e.g. `(int)$arDati["cd_job"]`).
- The `$url` endpoint is called two ways:
  - `?term=<text>` → must return a **JSON array** of `{"id":..., "value":"label"}`.
  - `?id=<value>` (on load, for editing) → must return the **JSON string** label to prefill.
- Requires jQuery UI (loaded via `##JQUERYINCLUDE##`, already present in detail templates).

Example endpoint (shared pattern, e.g. `component/ajax/entitysearch.php`):
```php
<?php
header('Content-Type: application/json');
$root="../../../../";
include($root."src/_include/config.php");

$term = postget("term","");
$id   = postget("id","0");

if($term!="") {
    $stmt = $conn->prepare("SELECT id_x, CONCAT(de_codice,' - ',de_nome) AS nome
        FROM ".DB_PREFIX."tabella WHERE de_codice LIKE ? OR de_nome LIKE ?
        ORDER BY de_codice LIMIT 30");
    $search = "%".$term."%";
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $out = array();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) $out[] = array("id"=>$row['id_x'], "value"=>$row['nome']);
    echo json_encode($out);
} else {
    if($id!="0")
        echo json_encode(execute_scalar("SELECT CONCAT(de_codice,' - ',de_nome)
            FROM ".DB_PREFIX."tabella WHERE id_x='".(int)$id."'",""));
}
```

Note: field/table/parameter names above (`de_nome`, `cd_job`, `dt_payment`, ...) are the
actual naming convention used in the codebase (Italian abbreviated prefixes: `de_`
description, `cd_` code/id, `dt_` date, `nu_` number, `en_` enum/status) — kept as-is
rather than translated, since they must match real column/field names.

## Working convention: unknown fields

**If you come across a field type you don't know how to drive, ask the user for the path of
another framework project that already uses it, then study and replicate that pattern
instead of inventing one.** Each child project (rockit_ambiente, timy, timynce, ...) may
have real usage examples of the same field with different nuances — see
[SIBLING-PROJECTS.md](SIBLING-PROJECTS.md).
