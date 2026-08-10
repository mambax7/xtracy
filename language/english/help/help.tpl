<div id="help-template" class="outer">
    <{include file=$smarty.const._MI_XTRACY_HELP_HEADER}>

    <h4 class="odd">DESCRIPTION</h4> <br>

    <p>
        xTracy replaces the XOOPS error page with <a href="https://tracy.nette.org/" class="external" target="_blank">Tracy</a>,
        which shows the exception, the call that produced it, the source around it, and the
        request that triggered it. It also adds Tracy's debug bar to the foot of the page.
        It is intended for developers working on a site, not for a live one.<br> <br>
    </p>
    <p>
        Tracy is shown only to an authenticated member of the webmaster group, and only to
        groups the administrator has granted permission. Because it can never reach an
        ordinary visitor, it is free to be as detailed as it likes.<br> <br>
    </p>

    <h4 class="odd">REQUIREMENTS</h4> <br>
    <div class="even">
    <ul>
        <li>XOOPS <strong>2.7.3</strong> or later. This is a hard requirement, not a
            recommendation: the whole module is an answer to the core's
            <code>core.debug.errorscreen</code> event, so on an older core it would install
            and then do nothing. Installation checks for the event itself and refuses with
            an explanation if the core does not provide it.</li>
        <li>PHP 8.2 or later.</li>
        <li>The Tracy library, which the module does not ship.</li>
    </ul>
    </div>

    <h4 class="odd">INSTALL/UNINSTALL</h4>

    <div class="even">
        <br> <br> To add <strong>xTracy</strong> to your XOOPS 2.7.3+ site, follow these steps<br> <br>

    <ul>
        <li>install the Tracy library into your XOOPS library directory:
            <code>composer require tracy/tracy</code> run inside <code>xoops_lib</code>.
            Tracy lives with the site's other libraries, not inside this module.</li>
        <li>download the distribution archive of your choice</li>
        <li>extract the archive into your system's modules directory</li>
        <li>rename the new directory to <code>xtracy</code></li>
        <li>install the xtracy module in the system administration module page</li>
        <li>grant access by selecting groups in the Permissions section</li>
        <li>create <code>xoops_data/data/debug.php</code> if you have not already — copy
            <code>debug.dist.php</code> beside it — and make sure it has
            <code>'enabled' =&gt; true</code></li>
    </ul>

    <br> <br>
        Detailed instructions on installing modules are available in the
        <a href="https://xoops.gitbook.io/xoops-operations-guide/" target="_blank">Chapter 2.12 of our XOOPS Operations Manual</a>
    </div>
    <br> <br>

    <h4 class="odd">SWITCHING IT ON</h4> <br>
    <div class="even">
    <p>
        Installing the module is normally the whole configuration. XOOPS ships
        <code>'error_screen' =&gt; 'auto'</code>, which means "the first error-screen module
        installed", and installing xTracy records it as that owner.
    </p>
    <p>
        Two things are worth knowing.
    </p>
    <ul>
        <li><strong>Only <code>debug.php</code> activates the error screen.</strong>
            Admin &rarr; Preferences &rarr; Debug Mode does not, and this is deliberate:
            writing a file on the server is a stronger credential than holding an admin
            session, and handing PHP's error handlers to a third-party library asks for the
            stronger one. With the module installed but no <code>debug.php</code>, XOOPS
            reports the status <code>dormant</code> and tells you why.</li>
        <li><strong>Only one module can own the error screen.</strong> If you also have
            xWhoops installed, whichever was installed first keeps it. To hand it over,
            deactivate the holder and update the module you want, or name your choice
            explicitly with <code>'error_screen' =&gt; 'xtracy'</code> in
            <code>debug.php</code>. Nothing is ever taken silently.</li>
    </ul>
    <p>
        Optional settings go in <code>debug.php</code> under an <code>'xtracy'</code> key —
        the module reads its own block and the core passes it through without interpreting
        it:
    </p>
    <p><code>
        'xtracy' =&gt; [<br>
        &nbsp;&nbsp;'enabled' =&gt; null, &nbsp;// null = auto: on whenever tracy/tracy is installed<br>
        &nbsp;&nbsp;'show_bar' =&gt; true,<br>
        &nbsp;&nbsp;'strict_mode' =&gt; false,<br>
        &nbsp;&nbsp;'log_severity' =&gt; E_NOTICE | E_WARNING,<br>
        &nbsp;&nbsp;'log_directory' =&gt; '', &nbsp;// defaults to xoops_data/logs<br>
        ],
    </code></p>
    <p>
        If the DebugBar module is installed it offers a one-click "Turn Tracy toolbar
        ON/OFF" button, which flips the same switch without editing the file.
    </p>
    </div>

    <h4 class="odd">OPERATING INSTRUCTIONS</h4><br>
    <div class="even">
    <p>
        When an uncaught error occurs, Tracy replaces the page with its BlueScreen, which
        has four regions:
    <ul>
        <li><em>the header</em> names the exception and the message it carried</li>
        <li><em>the source panel</em> shows the file and line that threw, with the
            surrounding code</li>
        <li><em>the call stack</em> traces how the request got there; selecting a frame
            shows the code for that frame</li>
        <li><em>environment and details</em> holds request parameters, session contents,
            constants, included files and the rest of the request's state</li>
    </ul>
    Note: if the XoopsLogger is enabled, MySQL queries appear in the environment section.
    </p>
    <p>
        Tracy's debug bar sits in the corner of every page it is active on, showing request
        time, memory and any dumps you have made with <code>bdump()</code>.
    </p>
    <p>
        If something is not appearing, the site's own diagnostics will say why rather than
        leaving you guessing: XOOPS publishes <code>XOOPS_ERROR_SCREEN_STATUS</code> and
        <code>XOOPS_ERROR_SCREEN_MESSAGE</code> on every request, with values such as
        <code>active</code>, <code>dormant</code>, <code>disabled</code>,
        <code>missing</code> (the library is explicitly enabled but not installed, or could
        not be loaded — with the default automatic mode an absent library reports
        <code>disabled</code>), <code>incompatible</code> (the
        installed Tracy build cannot load on this PHP version) or <code>unclaimed</code>
        (the configured owner is not an active module).
    </p>
    <p>
        Detailed instructions on configuring the access rights for user groups are available in the
        <a href="https://xoops.gitbook.io/xoops-operations-guide/" target="_blank">Chapter 2.8 of our XOOPS Operations Manual</a><br> <br></p>
    </div>

    <h4 class="odd">TUTORIAL</h4> <br>

    <p class="even">
        A tutorial ships with the module, in <code>docs/TUTORIAL.md</code>. It walks through
        installing the library, switching Tracy on, reading a BlueScreen, using
        <code>bdump()</code>, and what to do when the screen does not appear.
        <br><br>
        There are more XOOPS Tutorials, so check them out in our <a href="https://www.gitbook.com/@xoops/" target="_blank">XOOPS Tutorial Repository on GitBook</a>.
    </p>

    <h4 class="odd">TRANSLATIONS</h4> <br>
    <p class="even">
        Translations are on <a href="https://www.transifex.com/xoops/" target="_blank">Transifex</a> and in our <a href="https://github.com/XoopsLanguages/" target="_blank">XOOPS Languages Repository on GitHub</a>.</p>

    <h4 class="odd">SUPPORT</h4> <br>
    <p class="even">
        If you have questions about this module and need help, you can visit our <a href="https://xoops.org/modules/newbb/viewforum.php?forum=28" target="_blank">Support Forums on XOOPS Website</a></p>

    <h4 class="odd">DEVELOPMENT</h4> <br>
    <p class="even">
        This module is Open Source and we would love your help in making it better! You can fork this module on <a href="https://github.com/XoopsModules27x/xtracy" target="_blank">GitHub</a><br><br>
        But there is more happening on GitHub:<br><br>
        - <a href="https://github.com/xoops" target="_blank">XOOPS Core</a> <br>
        - <a href="https://github.com/XoopsModules27x" target="_blank">XOOPS Modules</a><br>
        - <a href="https://github.com/XoopsThemes" target="_blank">XOOPS Themes</a><br><br>
        Go check it out, and <strong>GET INVOLVED</strong>

    </p>

</div>
