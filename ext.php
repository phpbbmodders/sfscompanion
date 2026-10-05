<?php
/**
 *
 * SFS Companion extension for the phpBB Forum Software package
 *
 * @copyright (c) 2015-2019, Sheer, https://www.phpbbguru.net/community/
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\sfscompanion;

class ext extends \phpbb\extension\base
{
	/**
	* Refuse to enable below the minimum phpBB and PHP versions.
	*
	* This extension only makes sense installed alongside rmcgirr83/stopforumspam
	* (every StopForumSpam API call it makes goes through that extension's own
	* sfsapi service) - most boards install extensions by copying files rather
	* than via Composer, so composer.json's "require" alone won't be enforced.
	*/
	public function is_enableable()
	{
		if (!$this->check_phpbb_version() || !$this->check_php_version())
		{
			$language = $this->container->get('language');
			$language->add_lang('install_sfscompanion', 'phpbbmodders/sfscompanion');

			return $language->lang('SFSCOMPANION_NOT_ENABLEABLE');
		}

		global $phpbb_extension_manager;

		return $phpbb_extension_manager->is_enabled('rmcgirr83/stopforumspam');
	}

	/**
	 * Require phpBB 3.3.19
	 *
	 * @return bool
	 */
	public function check_phpbb_version()
	{
		return phpbb_version_compare(PHPBB_VERSION, '3.3.19', '>=');
	}

	/**
	 * Require PHP 8.0
	 *
	 * @return bool
	 */
	public function check_php_version()
	{
		return PHP_VERSION_ID >= 80000;
	}
}
