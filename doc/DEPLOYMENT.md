# Deployment and multi-project sync

This repo (`framework-core`) doesn't run on its own: it's the shared base for several
independent projects (see [SIBLING-PROJECTS.md](SIBLING-PROJECTS.md)). Two scripts manage
the lifecycle: `copiacore.php` (propagates the core into child projects) and `deploy.php`
(publishes a single project to its server).

## `copiacore.php` — core → child project sync

Run **from the child project's folder** (not from `framework-core`):

```
php copiacore.php
```

Copies from `../framework-core` (path relative to the child folder, so repositories must be
kept as sibling folders on the filesystem) into the current folder:

- `src/_include/`, `src/icons/`, `src/images/`, `src/template/`
- `src/index.php`, `src/login.php`, `src/logout.php`, `src/resetpassword.php`
- `src/componenti/frw*/`, `gestioneutenti/`, `installbase/`, `debugger/` (the core components)
- `data/.htaccess`, `data/index.php`, `data/dbimg/index.php`, `data/logs/.htaccess`,
  `data/logs/index.php`
- `data/lang/.htaccess`, `data/lang/base.en.lang.txt`, `data/lang/base.it.lang.txt` (**not**
  `en.lang.txt`/`it.lang.txt` — see [I18N.md](I18N.md))
- `copiacore.php` and `deploy.php` themselves (they propagate themselves)

Practical implications:
- This is a **one-way, destructive** copy (it overwrites the destination files): any local
  change to a core file made directly in the child project is lost on the next
  `copiacore.php` run. Changes to the core must be made in `framework-core` and then
  propagated, never the other way around.
- The `$items` list at the top of the script is the source of truth for **what counts as
  core** in the strict sense. If a new shared core file/folder is added, it must also be
  added here.
- It does not handle deletions: if a file is removed from the core, it still lingers
  (stale) in already-synced projects until manually removed.

## `deploy.php` — publishing to a server

Deploys via SFTP/FTP/FTPS all files tracked by git (respects `.gitignore`), run from the
folder of the project being published.

```
php deploy.php                        actual deploy (all files from git)
php deploy.php day=7                  only git files modified in the last 7 days
php deploy.php -test                  local → remote preview, no connection made
php deploy.php -test day=3            preview of files modified in the last 3 days
php deploy.php source=fs day=3        upload filesystem files (not git) modified in the last 3 days
php deploy.php -v                     verbose output (connection debugging)
```

Configuration in `deploy.env` (never commit — gitignored; template in
`deploy.env.example`):
```
DEPLOY_HOST=...
DEPLOY_PORT=21
DEPLOY_USER=...
DEPLOY_PASS=...
DEPLOY_PATH=/opt/...
DEPLOY_PROTOCOL=ftps        # ftp | ftps | sftp
```

Supported protocols: `ftp` (port 21), `ftps` (FTP with explicit TLS, port 21, recommended
for Aruba hosting), `sftp` (port 22). If `DEPLOY_KEY_PATH` is set and `sftp.exe` is
available, native OpenSSH is used (supports ed25519/ecdsa); otherwise it falls back to
curl/libssh2.

## No staging/CI environment

There is no CI pipeline or automated staging environment: `deploy.php -test` is the way to
validate what would be published before an actual deploy.
