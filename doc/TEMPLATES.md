# Template system

- Templates are `.html` files inside each component's `template/` folder.
- Variable placeholder: `##variableName##`, replaced by `loadTemplateAndParse()` / the various `str_replace()` calls in controllers (see [ARCHITECTURE.md](ARCHITECTURE.md)).
- i18n placeholder: `{LABEL_KEY}`, resolved by `translateHtml()` (see [I18N.md](I18N.md)) — always call it on the final HTML before printing.
- Global replacements: the `$defaultReplace` array (defined on the `Ambiente`/config side), applied to every template.
- JS/CSS assets are loaded per-component through the templates themselves (there is no bundler).

## Standard placeholders

| Placeholder | Value |
|-------------|-------|
| `##root##` | Application root path (e.g. `/`) |
| `##JQUERYINCLUDE##` | `<script>` tag for jQuery |
| `##VER##` | Version string, used for asset cache-busting |
| `##TITLE##` | Component title |

## Global assets available in templates

- **jQuery**: via `##JQUERYINCLUDE##`
- **Chart.js**: `##root##src/assets/chartjs/chart.min.js`
- **comode.js**: `##root##src/template/comode.js` — client-side utility JS (e.g. form handling, `checkConStato()`)
- **controlloform.js**: client-side validation generated from form fields (see [FORMS.md](FORMS.md))
- jQuery UI: used by some form fields (`data`, `autocomplete`) — included in detail templates

## Per-component CSS

Components with a more elaborate UI use a dedicated stylesheet `template/style.css`, included with:
```html
<link rel="stylesheet" type="text/css" href="./template/style.css"/>
```
CSS classes should be prefixed with the component name (e.g. `fd-` for `fontidashboard`) to avoid conflicts between different components loaded on the same page.

## Tooltip pattern

```html
<span data-rel="Tooltip text" class="icon-help-circled"></span>
```
The framework uses an icon font with `icon-*` classes (e.g. `icon-help-circled`, `icon-cancel`, etc.), defined in `src/icons/fontello`.

## AJAX endpoints

Some components use `ajax.php` (or `ajax.nome.php`) directly in the component root instead of the `ajax/` subfolder. Both patterns are valid in the existing codebase; a direct root `ajax.php` is the more common pattern in recent components. Framework-wide shared AJAX endpoints: `src/_include/ajax.lang.php` (current language labels as JSON) and `ajax.menu.php` (logged-in user's menu markup).
