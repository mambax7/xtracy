![alt XOOPS CMS](https://xoops.org/images/logo.png)

## xTracy module for [XOOPS CMS 2.7.3+](https://xoops.org)

[![XOOPS CMS Module](https://img.shields.io/badge/XOOPS%20CMS-Module-blue.svg)](https://xoops.org)
[![Software License](https://img.shields.io/badge/license-GPL-brightgreen.svg?style=flat)](http://www.gnu.org/licenses/gpl-2.0.html)

---

## What it is

xTracy replaces the XOOPS error page with **[Tracy](https://tracy.nette.org/)** while you
are developing a site.

When something throws, instead of a generic error you get the exception, the exact line
that produced it, the code around it, the call stack that led there, and the request that
triggered it — on one screen. It also adds Tracy's debug bar to the foot of every page.

Outside of the control panel there is no user interface. It simply takes over when
something goes wrong.

## What it does for you

**Stops the guess-and-reload loop.** The default XOOPS error page tells you something
failed. Tracy tells you which line, in which file, called from where, with which values in
scope — so the next thing you do is fix it, rather than add a `var_dump` and reload.

**Shows the request, not just the error.** Parameters, session contents, constants,
included files and server state sit alongside the stack trace. Most "works on my machine"
bugs are visible in that panel.

**Shows your SQL.** With XoopsLogger enabled, the queries that ran appear in the same
screen — which is where the real answer usually is.

**`bdump()` instead of `echo`.** Send values to the debug bar rather than into your page,
so inspecting a variable does not wreck the layout you are working on.

**Cannot leak to your visitors.** Tracy exposes source, paths and superglobals, so this
module registers only for an authenticated member of the webmaster group *and* only for
groups you have granted permission. Everyone else gets ordinary XOOPS error handling, on
the same site, at the same time.

**Costs nothing when it is off.** No provider installed means one function call during the
boot. There is no front end, no tables, and no runtime overhead on a live site.

## How it works

XOOPS core ships no error screen of its own beyond `XoopsLogger`, and knows no third-party
debugging library by name. Instead it publishes a seam: a site declares who owns PHP's
error and exception handlers, and the module owning that token registers itself at the end
of the boot — last, because whoever calls `set_error_handler()` last wins.

This module is that answer for Tracy. Its owner token is its own dirname, `xtracy`.

---

## Requirements

- **XOOPS 2.7.3 or later.** A hard requirement: the module is nothing but an answer to the
  core's `core.debug.errorscreen` event, so on an older core it would install and do
  nothing. Installation checks for the event itself and refuses with an explanation if the
  core does not provide it — a version number cannot express a capability.
- PHP 8.2 or later.
- `tracy/tracy`, which this module does not bundle.

## Installation

**1. Install the library.** Tracy lives with the site's other libraries, not inside this
module:

```bash
cd xoops_lib
composer require tracy/tracy
```

**2. Install the module.** Extract this repository into `htdocs/modules/`, rename the
directory to `xtracy`, and install it from Administration → Modules.

**3. Grant the permission.** Administration → xTracy → Permissions, and select the groups
allowed to see Tracy. Webmasters are the usual answer.

**4. Enable the debug configuration.** If `xoops_data/data/debug.php` does not exist, copy
`debug.dist.php` beside it and set:

```php
'enabled' => true,
```

That is the whole setup. `error_screen` ships as `'auto'`, meaning "the first error-screen
module installed", and installing this module recorded it as that owner.

> **Admin → Preferences → Debug Mode does not activate the error screen**, deliberately.
> Writing a file on the server is a stronger credential than holding an admin session, and
> an error screen shows source code and request data. With the module installed but no
> `debug.php`, XOOPS reports the status `dormant` and says why.

## Using it

Load any page as a member of a permitted group and Tracy's debug bar appears. When
something throws, you get the BlueScreen instead of the XOOPS error page.

Optional settings go in `debug.php` under an `'xtracy'` key — the module reads its own
block, and core passes it through without interpreting it:

```php
'xtracy' => [
    'enabled'       => null,   // null = auto: on whenever tracy/tracy is installed
    'show_bar'      => true,
    'strict_mode'   => false,
    'log_severity'  => E_NOTICE | E_WARNING,
    'log_directory' => '',     // defaults to xoops_data/logs
],
```

If the DebugBar module is installed, it offers a one-click "Turn Tracy toolbar ON/OFF"
button that flips the same switch without editing the file.

`docs/TUTORIAL.md` walks through all of this with a worked example, and
`docs/install.txt` is the short version.

## When Tracy does not appear

The site tells you why rather than leaving you guessing. Four constants are published on
every request — `XOOPS_ERROR_SCREEN_OWNER`, `_SOURCE`, `_STATUS` and `_MESSAGE`:

| Status | Meaning |
|---|---|
| `active` | Tracy is running |
| `dormant` | recorded as owner, but `debug.php` is absent or disabled |
| `disabled` | the module ran and chose not to register — the message says why |
| `missing` | `'enabled' => true` is set but `tracy/tracy` is not installed (or cannot be loaded); in the default automatic mode an absent library reports `disabled` |
| `incompatible` | the installed Tracy build cannot load on this PHP version |
| `unclaimed` | the configured owner is not an active module |
| `core` | nothing owns the screen; XoopsLogger has the handlers |

## Alongside xWhoops

Both provide an error screen, and PHP has one pair of handlers, so exactly one can own it.
Whichever was installed first keeps it; installing the second says so and changes nothing.
To hand it over: deactivate the holder and update the module you want, uninstall the holder
and reinstall the one you want, or pin your choice with `'error_screen' => 'xtracy'` in
`debug.php`. Nothing is ever taken silently.

---

### Please visit us on https://xoops.org

Current and upcoming "next generation" versions of XOOPS CMS are crafted on GitHub at: https://github.com/XOOPS
