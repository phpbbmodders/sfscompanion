# SFS Companion

[![Tests](https://github.com/phpbbmodders/sfscompanion/actions/workflows/tests.yml/badge.svg)](https://github.com/phpbbmodders/sfscompanion/actions/workflows/tests.yml) [![Lint](https://github.com/phpbbmodders/sfscompanion/actions/workflows/lint.yml/badge.svg)](https://github.com/phpbbmodders/sfscompanion/actions/workflows/lint.yml)

Companion tools for the Stop Forum Spam extension: browse what it blocked, and look up or remove suspected spammers.

## Features

- **Spam blocks / SFS errors** — two ACP log pages, filtered from phpBB's own log table down to just what `phpbbmodders/stopforumspam` has logged, with sort, IP search, delete, and an auto-prune cron job
- **Scan users** — lists recently-registered users and checks each one against StopForumSpam inline, with a one-click report-and-remove action
- **Check SFS** — a manual lookup link on member profiles and in the ACP user overview (gated by a dedicated `m_chk_sfs` permission, so it can be delegated to moderators without full admin rights)

## Requirements

- phpBB 3.3.19 or later
- PHP 8.0 or later
- [phpbbmodders/stopforumspam](https://github.com/phpbbmodders/stopforumspam) installed and enabled. This extension won't enable without it.

## Installation

1. Copy the extension to `/ext/phpbbmodders/sfscompanion`
2. In the Administration Control Panel, go to **Customise → Manage extensions**
3. Enable the **SFS Companion** extension (`phpbbmodders/stopforumspam` must already be enabled)
4. Set the log prune interval under **ACP → Extensions → SFS Companion → Settings**

## Upgrading from rmcgirr83/stopforumspam

Stop Forum Spam is now installed as `phpbbmodders/stopforumspam`. Upgrade in this order, or the board can fail to load while SFS Companion points at a disabled extension:

1. Disable **SFS Companion**
2. Disable the old **Stop Forum Spam** (do **not** delete its data)
3. Upload the new Stop Forum Spam to `/ext/phpbbmodders/stopforumspam` and this version of SFS Companion to `/ext/phpbbmodders/sfscompanion`
4. Enable **Stop Forum Spam**, then **SFS Companion**
5. Delete the `/ext/rmcgirr83/stopforumspam` folder and purge the board cache

## Contributing

Contributions are welcome!

- **Bug reports**: [Open an issue](https://github.com/phpbbmodders/sfscompanion/issues).
- **Everything else** (questions, feature requests, ideas, general discussion): [Use Discussions](https://github.com/orgs/phpbbmodders/discussions), or the [community forum](https://www.phpbbmodders.com/community/).
- Pull requests are welcome for bug fixes or discussed features.

## Acknowledgments

- Built from two original extensions by Sheer: [phpbb3.1-3.2-Stop_Spam_Register](https://github.com/AlexSheer/phpbb3.1-3.2-Stop_Spam_Register) (the log-viewer piece) and [phpbb3.2-Stopforumspam](https://github.com/AlexSheer/phpbb3.2-Stopforumspam) (the scanner/finder piece), reworked to delegate all StopForumSpam API calls to [phpbbmodders/stopforumspam](https://github.com/phpbbmodders/stopforumspam)'s own service instead of duplicating that logic.
- Code review, bug fixes, and documentation assisted by [Claude](https://www.anthropic.com/claude).

## License

This extension is licensed under the **GNU General Public License v2.0**.

See [license.txt](license.txt) for more information.
