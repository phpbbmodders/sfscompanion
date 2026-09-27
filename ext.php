<?php
/**
 *
 * SFS Companion extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\sfscompanion;

class ext extends \phpbb\extension\base
{
	/**
	* This extension only makes sense installed alongside rmcgirr83/stopforumspam
	* (every StopForumSpam API call it makes goes through that extension's own
	* sfsapi service) - most boards install extensions by copying files rather
	* than via Composer, so composer.json's "require" alone won't be enforced.
	*/
	public function is_enableable()
	{
		global $phpbb_extension_manager;

		return $phpbb_extension_manager->is_enabled('rmcgirr83/stopforumspam');
	}
}
