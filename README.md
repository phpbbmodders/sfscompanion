# SFS Companion

Companion tools for [rmcgirr83/stopforumspam](https://github.com/rmcgirr83/stopforumspam): a browsable, prunable log of what it's already blocked, plus a manual lookup/removal tool for suspected spammers. Every StopForumSpam API call this extension makes goes through `rmcgirr83/stopforumspam`'s own service — it never talks to the StopForumSpam API directly, and reuses that extension's own API key setting.

**Requires [rmcgirr83/stopforumspam](https://github.com/rmcgirr83/stopforumspam) to be installed and enabled.** This extension won't enable without it.

## What it adds

- **Spam blocks / SFS errors** — two ACP log pages, filtered from phpBB's own log table down to just what `rmcgirr83/stopforumspam` has logged, with sort, IP search, delete, and an auto-prune cron job
- **Scan users** — lists recently-registered users and checks each one against StopForumSpam inline, with a one-click report-and-remove action
- **Check SFS** — a manual lookup link on member profiles and in the ACP user overview (gated by a dedicated `m_chk_sfs` permission, so it can be delegated to moderators without full admin rights)

## Installation

1. Copy the extension to: `/ext/phpbbmodders/sfs_companion`
2. In the Administration Control Panel, navigate to: **Customise → Manage extensions**
3. Enable the **SFS Companion** extension (requires `rmcgirr83/stopforumspam` to already be enabled)
4. Configure the prune interval under **SFS Companion → Settings**

## Automated testing

We use automated unit tests to prevent regressions. Check out our build below:

[![Tests](https://github.com/phpbbmodders/sfs_companion/actions/workflows/tests.yml/badge.svg)](https://github.com/phpbbmodders/sfs_companion/actions/workflows/tests.yml)

## Acknowledgments

Built from two original extensions by Sheer: [phpbb3.1-3.2-Stop_Spam_Register](https://github.com/AlexSheer/phpbb3.1-3.2-Stop_Spam_Register) (the log-viewer piece) and [phpbb3.2-Stopforumspam](https://github.com/AlexSheer/phpbb3.2-Stopforumspam) (the scanner/finder piece), reworked to delegate all StopForumSpam API calls to [rmcgirr83/stopforumspam](https://github.com/rmcgirr83/stopforumspam)'s own service instead of duplicating that logic.

- Code review, bug fixes, and documentation assisted by [Claude](https://www.anthropic.com/claude).

## License

Licensed under the [GNU General Public License v2](license.txt)
