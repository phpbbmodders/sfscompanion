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
	/** Staff notification sent when the honeypot restricts an established account */
	const NOTIFICATION_TYPE = 'phpbbmodders.sfscompanion.notification.type.honeypot_restricted';

	/**
	* Refuse to enable below the minimum phpBB and PHP versions.
	*
	* This extension only makes sense installed alongside phpbbmodders/stopforumspam
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

		return $phpbb_extension_manager->is_enabled('phpbbmodders/stopforumspam');
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

	/**
	* Required whenever an extension defines its own notification type -
	* omitting this throws uncaught exceptions on enable/disable/purge.
	*/
	public function enable_step($old_state)
	{
		if ($old_state === false)
		{
			$this->container->get('notification_manager')->enable_notifications(self::NOTIFICATION_TYPE);

			return 'notification';
		}

		return parent::enable_step($old_state);
	}

	public function disable_step($old_state)
	{
		if ($old_state === false)
		{
			$this->container->get('notification_manager')->disable_notifications(self::NOTIFICATION_TYPE);

			return 'notification';
		}

		return parent::disable_step($old_state);
	}

	public function purge_step($old_state)
	{
		if ($old_state === false)
		{
			$this->container->get('notification_manager')->purge_notifications(self::NOTIFICATION_TYPE);

			return 'notification';
		}

		return parent::purge_step($old_state);
	}
}
