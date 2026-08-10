# xTracy — a walkthrough

## What xTracy is, and why you would want it

xTracy replaces the XOOPS error page with [Tracy](https://tracy.nette.org/) while you are
working on a site.

The default XOOPS error page tells you that something failed. Tracy tells you **which line
failed, in which file, called from where, and what the request looked like at the time** —
on one screen, with the source around the failure and the full call stack. It also puts
Tracy's debug bar at the foot of every page.

What that buys you, concretely:

- **No guess-and-reload loop.** You stop adding `var_dump()` and refreshing, because the
  information you were about to print is already on the screen.
- **The stack, walkable.** Click any frame to see its code. The real mistake is often
  several frames above where the exception surfaced.
- **The request beside the error.** Parameters, session, constants, included files. Most
  "works on my machine" bugs are visible right there.
- **Your SQL too**, if XoopsLogger is enabled — usually where the answer actually is.
- **`bdump()` instead of `echo`.** Send a value to the debug bar rather than into the page,
  so inspecting a variable does not wreck the layout you are building.
- **It cannot leak.** Tracy shows source, paths and superglobals, so it appears only for an
  authenticated webmaster in a group you have permitted. Ordinary visitors get ordinary
  XOOPS error handling on the same site at the same time.
- **It costs nothing when off.** No front end, no tables, one function call during boot.

**Who it is for:** developers working on a XOOPS site or module. Not for a live site — and
the module is built so that switching it on takes a deliberate act you cannot perform by
accident.

---

The rest of this document is a working tutorial: install the library, switch Tracy on,
break something on purpose, and read what comes back. It assumes a XOOPS 2.7.3+ development
site you are willing to break. Do not follow it on a live one.

---

## 1. Install the library

Tracy is not shipped with the module. It goes where the site keeps its other libraries:

```
cd /path/to/your/site/xoops_lib
composer require tracy/tracy
```

You should end up with `xoops_lib/vendor/tracy/tracy`. The module finds it there without
being told where to look; it also checks `class/libraries/vendor` for older layouts.

## 2. Install the module

Extract the archive into `modules/`, rename the directory to `xtracy`, and install it from
Administration → Modules.

If the install refuses with a message about `xoops_activateErrorScreen()` being undefined,
your core predates the error-screen seam. The module would install and do nothing, so it
declines instead. Update the core.

On success it reports that it now owns the error screen — and that this takes effect once
`debug.php` is enabled, which is the next step.

## 3. Grant the permission

Administration → xTracy → **Permissions**, and select the groups allowed to see Tracy.
Webmasters are the usual answer. Nobody outside these groups will ever see a BlueScreen,
regardless of anything else on this page.

## 4. Enable the file-based debug configuration

If `xoops_data/data/debug.php` does not exist, copy the template beside it:

```
cp xoops_data/data/debug.dist.php xoops_data/data/debug.php
```

Open it and make sure of one line:

```php
'enabled' => true,
```

That is all that is required. `error_screen` ships as `'auto'`, meaning "the first
error-screen module installed", and installing xTracy recorded it as that owner.

**Why a file, and not the Debug Mode preference?** Admin → Preferences → Debug Mode does
not activate an error screen, deliberately. Writing a file on the server is a stronger
credential than holding an admin session, and an error screen shows source code, file
paths and superglobals to whoever is looking. The stronger exposure asks for the stronger
credential. Debug Mode keeps its twenty-year meaning: XoopsLogger renders its log into the
page.

## 5. See it work

Load any page as a webmaster. Tracy's debug bar should appear in the corner.

Now break something on purpose. Drop this into a scratch file under your site root and
request it:

```php
<?php
require __DIR__ . '/mainfile.php';
throw new RuntimeException('Hello from the tutorial');
```

Instead of the XOOPS error page you should get Tracy's BlueScreen.

## 6. Reading a BlueScreen

Four regions, and they answer different questions:

| Region | Question it answers |
|---|---|
| Header | What went wrong, and what kind of thing it was |
| Source panel | Which line threw, and what surrounds it |
| Call stack | How the request reached that line — click a frame to see its code |
| Environment | What the request looked like: parameters, session, constants, included files |

If XoopsLogger is enabled, the SQL it recorded appears in the environment section too,
which is often where the real answer is.

## 7. Dumping values

Tracy's `bdump()` sends a value to the debug bar instead of the page, so it does not
disturb the layout:

```php
bdump($someArray, 'what the handler received');
```

`dump()` writes inline where it is called. Both are Tracy functions and are available once
Tracy is active; if the code might run on a site where it is not, guard each call with its
own check — `function_exists('bdump')` for `bdump()`, `function_exists('dump')` for
`dump()` — since guarding one says nothing about the other.

---

## When Tracy does not appear

The site tells you why rather than leaving you to guess. XOOPS publishes four constants on
every request:

| Constant | Meaning |
|---|---|
| `XOOPS_ERROR_SCREEN_OWNER` | which module owns the screen, or `core` |
| `XOOPS_ERROR_SCREEN_SOURCE` | `config` (pinned in debug.php), `recorded` (claimed at install), or `default` |
| `XOOPS_ERROR_SCREEN_STATUS` | what actually happened |
| `XOOPS_ERROR_SCREEN_MESSAGE` | a sentence explaining that status |

Read them from any page, or from a scratch file after `mainfile.php`:

```php
echo XOOPS_ERROR_SCREEN_STATUS, ': ', XOOPS_ERROR_SCREEN_MESSAGE;
```

What the statuses mean:

- **`active`** — Tracy is running. If you still see no bar, check `'show_bar'`.
- **`dormant`** — the module is installed and recorded as owner, but `debug.php` is absent
  or `'enabled' => false`. Step 4.
- **`disabled`** — the module ran and chose not to register. The message says which reason:
  the request is not from an authenticated webmaster, the `use_xtracy` permission is not
  granted, or Tracy was switched off for this installation.
- **`missing`** — `'enabled' => true` is set but `tracy/tracy` is not installed where the
  module looks (or is present but could not be loaded). Step 1. In the default automatic
  mode an absent library reports `disabled` instead, since nothing was promised.
- **`incompatible`** — the installed Tracy build uses a `#[\Deprecated]` attribute on a
  property, which PHP 8.4 and later reject outright. Update Tracy.
- **`unclaimed`** — the configured owner is not an active module. Usually it was
  deactivated. Note that XOOPS does **not** promote another installed provider into a free
  seat: ownership only changes when somebody asks for it.
- **`core`** — nothing owns the screen; XoopsLogger has the handlers, which is the normal
  production state.
- **`error`** — Tracy threw while starting. The message carries the reason.

If the constants themselves are undefined, the core is older than the seam.

---

## Living with xWhoops as well

Both modules provide an error screen and PHP has only one pair of handlers, so exactly one
can own it. XOOPS resolves this in three steps, first answer wins:

1. `'error_screen'` in `debug.php`, when it names something other than `'auto'`
2. the owner recorded when a provider module was installed
3. `core`

So:

- **Installing the second module does not take the seat.** It says so, and leaves the first
  one alone.
- **Deactivating the owner does not pass the seat on.** The site falls back to core error
  handling and reports `unclaimed`. Deliberate: an error screen changing because you
  touched an unrelated module is the surprise this whole mechanism exists to prevent.
- **To hand the seat over**, do one of three things: deactivate the holder and *update* the
  module you want, which takes the seat from a holder that has stopped; uninstall the
  holder and reinstall the one you want; or name your choice in `debug.php` with
  `'error_screen' => 'xtracy'`, which beats anything recorded.

A pinned token always wins, so if you want certainty rather than convenience, pin it.

---

## Turning it off

Any of these, in increasing order of permanence:

- press "Turn Tracy toolbar OFF" in DebugBar, if that module is installed
- set `'xtracy' => ['enabled' => false]` in `debug.php`
- set `'error_screen' => 'core'`, which keeps the handlers with XoopsLogger no matter what
  is installed
- set `'enabled' => false` at the top of `debug.php`, which switches the whole file-based
  debug configuration off
- deactivate or uninstall the module

Deactivating does **not** release the recorded ownership — switch it back on and your setup
is as you left it. Uninstalling does release it.

One caveat: the uninstall can succeed while the release fails, when the runtime data
directory is not writable. The uninstaller says so in a warning. If that happens, make
`xoops_data/data` writable and remove the `error_screen_owner` entry from
`xoops_data/data/debug-runtime.json` by hand (or reinstall and uninstall again).

---

## Before you go live

Delete `xoops_data/data/debug.php`, or set `'enabled' => false`. Nothing in this module can
reach an anonymous visitor even if you forget, but a live site has no reason to be carrying
a debug configuration at all.
