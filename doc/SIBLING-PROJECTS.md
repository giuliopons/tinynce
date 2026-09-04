# Projects that use this core

`framework-core` is not a package installed via Composer: it's a sibling folder that gets
**copied** into every child project with [`copiacore.php`](DEPLOYMENT.md), run from the
child project's folder (`../framework-core` relative path). The repositories must therefore
be kept as sibling folders on the filesystem, typically under `D:\codice\framework\`.

Practical consequence: **a change to the core does not propagate on its own** to child
projects — `copiacore.php` must be re-run in each of them after a shared change, and any
change accidentally made to a "core" file inside a child project must be brought back here,
not left to silently diverge.

## Known installations

The canonical, up-to-date list of every installation of this framework lives in the
**Installations** section of [`CLAUDE.md`](../CLAUDE.md#installations) — kept there (not
duplicated here) so there is a single list to update. As of the last check it includes 10
installations: alertmanager, AdAdmin (`amb`), Timy, Timynce, CSC, Lomac, Rockit ambiente,
MI AMI Festival, Octopus Flow, and Splunkphp (the old version of Octopus).

Of these, the three explored in depth so far by this `doc/` set are **rockit_ambiente**,
**timy**, and **timynce** (see "Known discrepancies" below) — their component lists (e.g.
newsletter/AdAdmin for rockit_ambiente, timesheet/todo for timy and timynce) were read
directly from their own CLAUDE.md files. The rest are known to exist (path + one-line
purpose in the Installations list) but haven't been opened by this documentation effort —
don't assume anything about their internals beyond that one line without checking.

## `copiacore.php` now propagates `CLAUDE.md` and `doc/` too

Since the `copiacore.php` update that added `CLAUDE.md` and `doc/` to its `$items` list,
running it from a child project **overwrites that project's `CLAUDE.md` with this core's
generic one** (and copies the whole `doc/` folder alongside it). This is the mechanism that
keeps the "known discrepancies" below from recurring once a project re-syncs — but it also
means:

- Any project-specific content that used to live in a child project's own `CLAUDE.md`
  (tech-stack notes, its own component table, project-specific guidelines) must be moved to
  that project's own `THIS-PROJECT.md` (see this core's [`THIS-PROJECT.md`](../THIS-PROJECT.md)
  for the pattern — a short, project-only file, not propagated by `copiacore.php`)
  **before** the next `copiacore.php` run there, or it will be silently overwritten.
- As of this writing it is not confirmed whether `rockit_ambiente`, `timy`, and `timynce`
  have already made this move. Check for a `THIS-PROJECT.md` in each before running
  `copiacore.php` in them, and migrate their custom `CLAUDE.md` content first if it's
  missing.

## Known discrepancies between projects (verified by reading their respective CLAUDE.md files)

The three below predate the `CLAUDE.md`-propagation change above and describe the state
found by reading each project's own (pre-sync) `CLAUDE.md`. They should self-resolve for
any project once it runs `copiacore.php` again (after migrating its own content per the
section above) — until then, treat them as still-open discrepancies for these projects.

- **i18n**: `rockit_ambiente` and `timy` describe lang files as tab-separated; the actual
  content (both in this core repo and in `timynce`) is instead quoted CSV — see
  [I18N.md](I18N.md). Likely stale documentation in those two projects after a format
  change: worth checking/aligning when working on either of them.
- **Composer**: `timynce`'s CLAUDE.md explicitly states that the core uses
  Composer/autoload (PHPMailer); `rockit_ambiente` and `timy` deny it ("no Composer
  dependencies"). The actual code in this core repo confirms `timynce`'s version (see
  [CONFIGURATION.md](CONFIGURATION.md#composer)) — the other two projects likely need
  updating too.
- **Excluded components**: the old CLAUDE.md of this core repo mentioned a `splunkphp`
  branch that excludes AlertManager/Timy/AdAdmin/CMS — legacy branch terminology, no longer
  relevant now that core/newsletter/timesheet live in separate repositories.

When opening one of these three child projects, check first whether it already has a
`THIS-PROJECT.md` and a freshly re-synced `CLAUDE.md` (see above) — if so, its `CLAUDE.md`
is no longer the place to look for project-specific info, `THIS-PROJECT.md` is. If it still
has its old, pre-sync `CLAUDE.md`, treat the discrepancies above as live and this repo
(`framework-core`) as the source of truth for the shared part.
