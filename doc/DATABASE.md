# Database conventions

- Global MySQLi connection: `$conn` (set up in `config.php` / `comode.php::Connessione()`).
- Table prefix configurable via the `DB_PREFIX` constant.
- Core tables: `frw_vars`, `frw_utenti`, `frw_componenti`, `frw_moduli`, `frw_profili`, `frw_ute_fun`, `frw_extrauserdata`.
- DB helpers in `comode.php` (see [COMODE-HELPERS.md](COMODE-HELPERS.md)):
  - `execute_scalar($sql, $def="")` — single value
  - `execute_row($sql, $def=[])` — single row as an array
  - `execute_query($sql)` — result set
  - `mysql_scalar($sql)` — legacy scalar variant
  - `table_exists($t)`

**No migration system.** Schema changes are applied manually or via the installation wizard
(`installbase`). Every schema change should be documented explicitly (in the project's
CLAUDE.md or a dedicated file), since there is no automatic history to consult otherwise.

## Patterns observed in child projects

Not part of the core, but reusable patterns worth knowing when extending the framework:

### History/versioning of a "current state" table

Pattern used in `timynce` (`tsricavi`/`tscosti`): the main table (e.g. `ts_ricavi`) holds
only the **current state** of each row (read as-is by lists and reports). On every save, if
at least one tracked field changed, a snapshot row is written into a dedicated history
table (e.g. `ts_ricavi_storico`, keyed by `cd_ricavo` + `cd_utente` + `dt_modifica` + the
tracked fields). A snapshot is also written on the initial insert. Deleting the main row
also deletes its history. This enables a read-only panel showing the record's evolution
(e.g. estimate → progress claim → invoice issued → invoice paid) without normalizing the
main table or touching existing reports (which keep summing only the current value).

If a new component needs to track a field's history, this is the reference pattern to
replicate (a twin `_storico` table, same key + timestamp, writing only when tracked fields
actually changed).
