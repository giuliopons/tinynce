# Authentication and authorization

- Authentication against the `frw_utenti` table (`Login` class in `login.class.php`).
- Profile IDs: `20` = admin, `999999` = superadmin.
- User ↔ feature permissions in `frw_ute_fun`; checked in PHP via `checkAbilitazione($componente, $settasempre)` in `comode.php`.
- Custom auth extension point: `login-custom.class-example.php` (copy/adapt per project, e.g. SSO or special rules — see `CUSTOM_LOGIN_CLASS` in `constant_intelliphense.php`).
- Session handled by the `Session` class (`session.class.php`), a wrapper around `$_SESSION`. The session prefix is the MD5 of `WEBURL`, isolating multiple installations of the framework on the same domain/server (shared cookie, separate sessions).
- Login/logout/reset endpoints: `src/login.php`, `src/logout.php`, `src/resetpassword.php`.
