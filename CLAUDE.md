# CLAUDE.md - This is my SaaS application framework

## RULES

Fundamental rules for any agent working in this repository — always apply these:

1. **Always document in English.** Every `.md` file (this one, everything in `doc/`,
   `HANDOFF.md`, any future doc) is written in English, regardless of the language used in
   the conversation. Code, DB field/table names, and literal PHP values (e.g. `op=elimina`)
   stay as-is — only the surrounding prose is English.
2. **Commits are made by the human, not by the agent.** Never run `git commit` (or
   `git push`) in this repository unless the user explicitly asks for that specific commit
   in that turn. Editing/creating files is fine; committing them is the user's call.
3. **Split large `.md` files into `doc/`.** If `CLAUDE.md` or any file under `doc/` grows
   past roughly **35KB**, move the topic that's making it large into its own dedicated file
   in `doc/`, and leave a short pointer + link behind. Keeping individual files small is
   what makes context retrieval fast for agents — don't let a single file become a
   catch-all.

## Project Overview

This project is the **core** of a personal PHP framework, shared by several independent
application projects (see [doc/SIBLING-PROJECTS.md](doc/SIBLING-PROJECTS.md)). It isn't
installed as a package: it gets **copied** into every child project via
[`copiacore.php`](doc/DEPLOYMENT.md), run from the child project itself.

Built with PHP, MySQL, and JavaScript on a custom MVC-style backend built from scratch. Not
publicly distributed.

## Technology Stack

- **Backend:** PHP, MySQLi. **Composer is used** (autoload in `config.php` →
  `src/vendor/autoload.php`, dependencies: PHPMailer, altcha-org/altcha) — see
  [doc/CONFIGURATION.md](doc/CONFIGURATION.md).
- **Frontend:** Vanilla JavaScript, HTML templates with `##placeholder##` syntax
- **Database:** MySQL, no migration system
- **Auth:** Session-based with a role/profile system
- **i18n:** `.lang.txt` files in **quoted CSV** format (`"KEY","VALUE"`), `{label}` syntax
  in templates — see [doc/I18N.md](doc/I18N.md)

## Documentation

Detail documentation of the specific modules and components of the software in this installation is stored in [THIS-PROJECT.md](THIS-PROJECT.md), and detailed documentation of the whole framework lives in [`doc/`](doc/), organized by topic:

| File | Content |
|------|---------|
| [ARCHITECTURE.md](doc/ARCHITECTURE.md) | Repo layout, core files, component MVC pattern, request flow, standard CRUD commands |
| [COMPONENTS.md](doc/COMPONENTS.md) | Core components and conventions for creating new ones |
| [DATABASE.md](doc/DATABASE.md) | DB conventions, core tables, history/versioning pattern |
| [AUTH.md](doc/AUTH.md) | Authentication, profiles, session |
| [TEMPLATES.md](doc/TEMPLATES.md) | Template engine, standard placeholders, CSS, tooltips, AJAX |
| [FORMS.md](doc/FORMS.md) | Form field generators (`formcampi.class.php`), autocomplete pattern |
| [I18N.md](doc/I18N.md) | Language file format |
| [COMODE-HELPERS.md](doc/COMODE-HELPERS.md) | `comode.php` function reference by category |
| [CONFIGURATION.md](doc/CONFIGURATION.md) | `pons-settings.php`, Composer, DB-sourced constants |
| [DEPLOYMENT.md](doc/DEPLOYMENT.md) | `copiacore.php` (sync to child projects), `deploy.php` (publishing) |
| [SECURITY.md](doc/SECURITY.md) | Security notes |
| [SIBLING-PROJECTS.md](doc/SIBLING-PROJECTS.md) | Projects that use this core and how they relate to it |

See also [HANDOFF.md](HANDOFF.md) for the current work status on this documentation across
sessions.

## Development Guidelines

- **Composer is present but should be treated with care** — adding dependencies must be
  deliberate and surgical: this is a stable production system.
- **No framework upgrades** — changes must be surgical.
- **The component pattern is fixed** — new features go into a new component or extend an
  existing one, following the same `index.php` + class + templates structure (see
  [ARCHITECTURE.md](doc/ARCHITECTURE.md)).
- **DB changes are manual** — document any schema change clearly; there is no migration
  runner.
- **Primary application context is Italian** — labels go in both `it.lang.txt` and
  `en.lang.txt` (see [I18N.md](doc/I18N.md)). This is about the software's content, not
  about this documentation (see RULES above).
- **Core changes must be propagated** — after a change here, re-run `copiacore.php` in
  every affected child project (see [DEPLOYMENT.md](doc/DEPLOYMENT.md)).
- **Unknown form fields**: if you encounter a `formcampi.class.php` field you don't know
  how to drive, ask the user for the path of another framework project that already uses
  it and replicate that pattern instead of inventing one (see [FORMS.md](doc/FORMS.md)).
- **Gitignored files:** `pons-settings.php`, `deploy.env`, `data/logs/*`, `data/dbimg/*`,
  `src/vendor/*`, `src/composer.*`, `src/assets/*`, `src/lib/*`.

## Installations

There are different installation of this framework, we keep a list of all the applications here:

- Laboratory Alert Manager, managing alarms in medical laboratory for Beckman Coulter. Located here: "D:\codice\framework\alertmanager"
- AdAdmin, adv software, sold on CodeCamyon. Located here: "D:\codice\framework\amb"
- Timy, timesheet application (my version). Located here: "D:\codice\framework\timy"
- Timynce, timesheet and planning and CRM for NCE company: Located here: "D:\codice\framework\timynce"
- CSC, content management software for Caramella Rotar wensite. Located here: "D:\codice\framework\cms_csc"
- Lomac, content management software for Lomac website. Located here: "D:\codice\lomac.it\lomac.it\htdocs\ambiente"
- Rockit ambiente, some backend dashboard for rockit, AdAdmin modified and Newsletter tool. here: D:\codice\framework\rockit_ambiente"
- MI AMI Festival, newsletter tool. Here: "D:\codice\miamifestival.it\www.miamifestival.it\htdocs\ambiente"
- Octopus flow, PLC data ingestion tool, dashboard, pipeline. Located here: "D:\SC\project\ACR - Octopus Flow\p4"
- Splunkphp (old version of Octopus). Here: "D:\codice\framework\splunkphp"