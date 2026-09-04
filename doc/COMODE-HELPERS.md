# Function reference — `src/_include/comode.php`

Global utility library (~1700 lines, no namespace/class: all global functions). Categorized
list of the functions most relevant when writing a component. This is not an exhaustive
list of every parameter: for full signature details, read the file directly — the goal here
is to know *what to look for and where*.

## Connection and configuration

- `Connessione($WEBDOMAIN, $DEFUSERNAME, $DEFDBPWD, $DEFDBNAME)` — opens `$conn` (the global MySQLi connection).
- `CollateConnessione()` — sets the connection's charset/collation.
- `getVarSetting($var, $def="")` — reads a config variable/constant with a default.
- `hasModule($mod)` — checks whether a module/component is active for the current install.
- `table_exists($t)`

## Request input

- `postget($nome, $default="", $allowedValues=[])` — POST with a GET fallback.
- `getpost($nome, $default="", $allowedValues=[])` — GET with a POST fallback.
- `get($nome, $default="", $allowedValues=[])` — GET only.
- `getVar($var, $params=[])`, `gridFilterParams($params=[])`, `gridResetStartPage($params=[])`
  — helpers for `Grid` filter/pagination parameters.

All of these accept an optional array of allowed values: if passed, a value outside the
allowlist is discarded in favor of the default — an allowlist pattern to prefer over a
manual check repeated in every controller.

## DB

- `execute_scalar($sql, $def="")`, `execute_row($sql, $def=[])`, `mysql_scalar($sql)`.
- `concatenaId($sql, $sep=",")` — concatenates an id column into a `$sep`-separated list.
- `set_and_enum_values($table, $field)`, `getEmptyNomiCelleAr($table)`.

## Templates and i18n

- `loadTemplate($sFilename, $sCharset='UTF-8')` — reads a raw template file.
- `loadTemplateAndParse($filename, $ar=[])` — reads the template and performs the base
  substitutions (also supports including a URL/executed PHP).
- `translateHtml($html)` — resolves `{LABEL}` placeholders (see [I18N.md](I18N.md)); always
  call this as the last step before printing the response HTML.
- `loadLanguageLabels()`, `getDefaultLanguage()` — load the array of current language
  labels (also used by `ajax.lang.php`).

## AJAX responses / messages

- `returnmsg($msg, $op="", $class="err")` — error/generic message sent back to the client
  JS (the frontend interprets `$op`, e.g. `"jsback"`, `"reload"`).
- `returnmsgok($msg, $op="")` — success message sent back to the client JS (e.g.
  `"reload"`, `"load index.php"`).

See the full CRUD command pattern in
[ARCHITECTURE.md](ARCHITECTURE.md#standard-crud-commands-the-op-convention).

## Dates

- `numberf($n, $d=2)`, `datef($d, $hour=false)`, `phpFormat($fwk_format)`.
- `todayadd($g)`, `dayadd($g, $dayYmd)`, `date_diff2($d1, $d2)`, `DateAdd($v, $d=null, $f="d/m/Y")`.
- `TOymd($d='')`, `TOdmy($d='', $sep="-")`, `TOdmyhis($d='', $sep="-")`, `GetTimeStamp($MySqlDate)`.

Note: the `DATEFORMAT` constant drives the display format used by the `data` field in
`formcampi.class.php` (see [FORMS.md](FORMS.md)), not the parameters of these functions.

## Validation and escaping

- `is_email($Address)` — email syntax + common spam-domain check.
- `is_mobile()`, `is_OS($allowedOSAr)` — user-agent detection.
- `addslashesonlyquote($s)`, `esc($s)`, `myHtmlspecialchars($s)` — SQL/HTML escaping (see
  [SECURITY.md](SECURITY.md): always use these, never concatenate raw input).

## Uploads and image galleries

- `uploadFile($files, $campo, $uploadfile, $allowedArrayExt, $x=0, $y=0, $kb=0, $max=1)`,
  `checkForUploadErrors(...)`, `saveUploadedFile(...)`.
- `loadgallery($dirOrArrayParams, $prenome="", $div="div", $return="html", $SPOSTA=false, $TRIGGER_ERROR=true, $callback='null')`
  — generates an image gallery's markup from a folder or an array of parameters; used by
  `frwvars` (see `ajax.moveimg.php`/`ajax.deleteimg.php` as a companion endpoint example for
  reordering/deleting).
- `deldbimg($f)`, `spostafilegallery(...)`, `deletefilegallery(...)`, `unlinkbetter($s)`,
  `renamebetter($from, $to)`, `rrmdir($dir)`, `is_emptydir($which)`.

## Email and SMS

- `mail_utf8($to, $subject, $message)` — sends via PHPMailer (see
  [CONFIGURATION.md](CONFIGURATION.md#composer)), uses the `SMTP_*` constants.
- `isMobileNumber($number)`, `sms_text($telnumber, $message, $logTable="")`.

## Other

- `checkAbilitazione($componente, $settasempre="SETTA_SEMPRE")` — checks a user's
  permission on a component (see [AUTH.md](AUTH.md)).
- `array_qsort2(&$array, ...)`, `array_key_multi_sort($arr)`, `unatcmp($a, $b, $l=null)` —
  custom array sorting.
- `smartsub($text, $maxTextLenght, $modo)` — "smart" text truncation.
- `fg_from_bg($color)` — computes a readable text color for a given background (used by
  `colorlist` in `formcampi.class.php`).
- `NomeImmagine($s)` — recognizes common image/media extensions (gif/jpg/png/webp/mp4/zip...).
- `getIP()`, `getIP2LocationRow($ip)` — IP geolocation (used by AdAdmin for geo-targeting in
  child projects).
- `getDefaultComponentAddress()` — the logged-in user's default component (used by
  `src/index.php` for the initial redirect).
