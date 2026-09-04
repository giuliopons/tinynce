# Components

## Core components (in this repo)

These are the only components that live in `framework-core`. Every child project receives
them via `copiacore.php` and adds its own application components on top.

| Component | Purpose |
|-----------|---------|
| `debugger` | Debug tools |
| `frwcomponenti` | Manages installed components |
| `frwconstants` | Manages system constants |
| `frwmoduli` | Manages modules |
| `frwprofili` | Manages user profiles/roles |
| `frwvars` | Manages variables (`frw_vars`) — used as the reference example for the standard CRUD pattern in [ARCHITECTURE.md](ARCHITECTURE.md#component-mvc-pattern) |
| `gestioneutenti` | User management, personal profile, sign-in |
| `installbase` | Installation wizard |

## Application components in child projects (not in this repo)

Every project that consumes the core adds its own domain components. Indicative list (see
the specific project's CLAUDE.md for details):

- **rockit_ambiente** (`D:\codice\framework\rockit_ambiente`): Newsletter (`nlemails`, `nlnewsletter`, `nltags`, `nltempalte`, `nlfunneltree`, `nlimport`, `nlquery`), AdAdmin (`r7banner`, `r7campagne`, `r7posizioni`, `r7templates`), various dashboards (`analisiPro`, `ascolti`, `stats`).
- **timy** (`D:\codice\framework\timy`) and **timynce** (`D:\codice\framework\timynce`): Timesheet/todo (`tsclienti`, `tsjob`, `tsreparti`, `tsore2`, `tsoremancanti`, `tschehofatto`, `tsreport`, `tstipiore`, `tsplanning`, `tstasks`, `tslists`, `tsnotes`), plus a cost/revenue variant in `timynce` (`tsfornitori`, `tsricavi`, `tscosti`, `tsreport-costi-ricavi`).

See [SIBLING-PROJECTS.md](SIBLING-PROJECTS.md) for how these projects relate to the core.

## Conventions for a new component

- Follow the standard structure described in [ARCHITECTURE.md](ARCHITECTURE.md#component-mvc-pattern).
- The manager class extends `CrudBase` when the component is essentially a CRUD on a table.
- Build lists with the `Grid` class (see `grid.class.php`), and detail forms with the classes in `formcampi.class.php` (see [FORMS.md](FORMS.md)).
- AJAX endpoints: both `ajax/` (subfolder) and `ajax.php` / `ajax.nome.php` in the component root are valid patterns in the codebase; more recent components favor a direct `ajax.php` in root.
- New features go into a new component or by extending an existing one following the same pattern — don't introduce alternative patterns.
