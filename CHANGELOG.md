# Changelog

**xTracy replaces the XOOPS error page with [Tracy](https://tracy.nette.org/) while you are
working on a site.** Instead of a generic failure page you get the line that threw, the
source around it, the call stack that led there and the state of the request — plus Tracy's
debug bar on every page. It shows only to an authenticated webmaster in a group you have
permitted, costs one function call when it is off, and has no front end. For developers;
not for a live site. See `README.md` for what it does and `docs/TUTORIAL.md` for a
walkthrough.

All notable changes to the xTracy module are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses semantic versioning.

## [1.0.0-Beta1] - 2026-08-08

First release. xTracy hands PHP's error and exception handlers to
[nette/tracy](https://tracy.nette.org/) through the error-screen provider seam introduced
in XOOPS 2.7.3.

Before that seam existed, Tracy was activated by the core itself — roughly 190 lines of
`include/debugconfig.php` that named a third-party library, on every XOOPS install whether
or not the site owner had ever heard of it. This module is where that code belongs: a site
that wants Tracy installs it, and a site that does not carries none of it.

### Added

- Answers `core.debug.errorscreen` for the owner token `xtracy`, the module's own dirname,
  so `'error_screen' => 'xtracy'` in `xoops_data/data/debug.php` names the directory to go
  and look in. The older spelling `tracy`, used by 2.7.3 development builds, is still
  answered — see `LEGACY_OWNERS` in `preloads/core.php`.
- Claims the error screen at install and releases it at uninstall, so installing one
  provider module is the whole setup: with `'error_screen' => 'auto'` — the shipped default
  — the first provider installed becomes the owner and no config edit is needed.
  Deactivating does **not** release the seat, so switching the module off for an afternoon
  leaves the setup intact for when it comes back.
- Takes the screen from a holder that is gone or inactive, on update. Core refuses an
  ordinary claim while another provider holds the seat — an update must not quietly take
  it from something still running — so the transfer establishes that the holder has stopped
  and then claims deliberately.
- Reports its outcome to core in every branch, including the branches where it decides
  **not** to register: `active`, `disabled` with the reason, `missing` when `tracy/tracy`
  is not installed, `incompatible` when the installed build cannot load on this PHP, and
  `error` if Tracy throws while starting. Core publishes it as
  `XOOPS_ERROR_SCREEN_STATUS` / `_MESSAGE`. A provider that registers silently is worse
  than one that does nothing: the site's own diagnostics would then describe a screen
  nobody is showing.
- Enforces the `use_xtracy` permission the Permissions page manages, in addition to core's
  developer-request gate. The two answer different questions — "may diagnostics be exposed
  to whoever is making this request" and "has this site granted this group xTracy" — so a
  site can withhold Tracy from an administrator who would otherwise qualify.
- Admin pages: Home, Permissions and About, plus multi-page help (Overview, Disclaimer,
  License, Support).
- Defines `XOOPS_TRACY_STATUS` and `XOOPS_TRACY_MESSAGE` for DebugBar 1.4.0 and earlier,
  which read them directly and predate the generic constants. Deprecated; they come out
  when DebugBar's floor reaches the release that reads `XOOPS_ERROR_SCREEN_STATUS`.

### Security

- Registers only when core reports the request comes from an authenticated member of the
  webmaster group. Tracy's toolbar and BlueScreen expose source excerpts, file paths,
  superglobals and environment; the previous in-core implementation checked only that
  debugging was switched on, which made it the loosest test in the stack and therefore the
  one that set the site's real exposure.
- Refuses to hand Tracy a log directory that does not exist or is not writable.
  `Debugger::enable()` registers its own handlers **before** it validates the path and then
  throws, so its own handler catches the exception and ends the request — a `try/catch`
  around `enable()` cannot save it. A site whose `xoops_data/logs` was missing lost every
  page. File logging is switched off instead, and the status message says so.
- Registration is now the LAST thing activation does. `Debugger::enable()` is where Tracy
  takes PHP's error, exception and shutdown handlers, so the three `$strictMode` /
  `$showBar` / `$logSeverity` assignments moved in front of it: anything that throws after
  `enable()` leaves Tracy holding the handlers while this module reports `error`, and
  because the failure is caught here rather than propagating, core's own catch never runs.
  XOOPS 2.7.3 hands the error and exception handlers back on an `error` report, but nothing
  in PHP can unregister a shutdown function — so having nothing fallible left to do by the
  time we register is the only part of that residue a module can close itself.
- Screens the Tracy source for a `#[\Deprecated]` attribute on a property before
  autoloading it. Some Tracy dev builds carry one, PHP 8.4 and later reject it outright,
  and a compile-time attribute error cannot be caught — by the time the class body is
  parsed the fatal has already happened. Reported as `incompatible`.

### Requirements

- XOOPS **2.7.3** or later. This is a hard floor: the module is nothing but an answer to
  `core.debug.errorscreen`, so on a core that never fires that event it would install
  cleanly and do nothing at all. The version alone is not sufficient — the seam landed
  during 2.7.3's development — so installation also tests for
  `xoops_activateErrorScreen()` and aborts with an explanation if it is absent. A version
  number cannot express a capability.
- PHP 8.2 or later.
- `tracy/tracy`, installed into `xoops_lib/vendor` with `composer require tracy/tracy`.
  The module vendors nothing itself.
