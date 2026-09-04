# Security notes

- SQL injection detection patterns in `config.php` (blocks suspicious input before it
  reaches queries).
- Magic-quotes simulation via `addslashes()` on input; dedicated helpers in `comode.php`:
  `addslashesonlyquote($s)`, `esc($s)` — always use these to build SQL with user input,
  never concatenate it directly.
- `.htaccess` blocks PHP execution in `data/` subdirectories (user uploads).
- `botfilter.php::dieIfBotOrSuspiciousTraffic()` blocks known bot/scraper user-agents on
  public pages (typically used on the ad server/banner side, where bot traffic would
  pollute the statistics).
- **Never put user input directly into SQL** — use the existing escaping helpers in
  `comode.php` ([COMODE-HELPERS.md](COMODE-HELPERS.md)).
- Credentials (`pons-settings.php`, `deploy.env`) are always gitignored: never commit them,
  not even in a child project's branch.
