<?php
/**
 *
 * SFS Companion extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\sfscompanion\migrations;

use phpbb\db\migration\exception;

/**
* Adds the honeypot checks (formerly the separate phpbbmodders/honeypot
* extension): their settings, the table each trip is logged to, the hidden
* group tripped accounts are moved into, and the ACP pages.
*/
class honeypot_1_1_0 extends \phpbb\db\migration\migration
{
	/** Group tripped accounts are moved into; the honeypot listener stops its members posting */
	const GROUP_NAME = 'SFSC_HONEYPOT_RESTRICTED';

	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'sfsc_honeypot_trips');
	}

	static public function depends_on()
	{
		return array('\phpbbmodders\sfscompanion\migrations\install_1_0_1');
	}

	public function update_schema()
	{
		return array(
			'add_tables'	=> array(
				$this->table_prefix . 'sfsc_honeypot_trips'	=> array(
					'COLUMNS'		=> array(
						'trip_id'			=> array('UINT', null, 'auto_increment'),
						'trip_time'			=> array('TIMESTAMP', 0),
						'trip_form'			=> array('VCHAR:16', ''),
						'trip_reason'		=> array('VCHAR:16', ''),
						'submit_seconds'	=> array('UINT', 0),
						'user_id'			=> array('UINT', 0),
						'username'			=> array('VCHAR_UNI', ''),
						'user_email'		=> array('VCHAR_UNI:100', ''),
						'user_ip'			=> array('VCHAR:40', ''),
						'user_agent'		=> array('VCHAR:255', ''),
						'subject'			=> array('VCHAR_UNI', ''),
						'excerpt'			=> array('TEXT_UNI', ''),
						'sfs_reported'		=> array('BOOL', 0),
						'ip_banned'			=> array('BOOL', 0),
					),
					'PRIMARY_KEY'	=> 'trip_id',
					'KEYS'			=> array(
						'trip_time'	=> array('INDEX', 'trip_time'),
						'user_ip'	=> array('INDEX', 'user_ip'),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables'	=> array(
				$this->table_prefix . 'sfsc_honeypot_trips',
			),
		);
	}

	public function update_data()
	{
		return array(
			// Master switch for every honeypot check
			array('config.add', array('sfsc_hp_enabled', 1)),
			array('config.add', array('sfsc_hp_check_register', 1)),
			array('config.add', array('sfsc_hp_check_posting', 1)),
			array('config.add', array('sfsc_hp_check_pm', 1)),
			// Minimum seconds between form render and submit - 0 disables the check
			array('config.add', array('sfsc_hp_min_seconds', 3)),
			// Notify staff when a tripped account has at least this many posts
			array('config.add', array('sfsc_hp_notify_posts', 10)),
			// Trip log retention - 0 disables pruning
			array('config.add', array('sfsc_hp_expire_days', 30)),
			array('config.add', array('sfsc_hp_last_prune', 0)),
			// Automatic IP bans: after this many matching trips (0 disables)...
			array('config.add', array('sfsc_hp_ban_threshold', 3)),
			// ...within this many hours...
			array('config.add', array('sfsc_hp_ban_window_hours', 24)),
			// ...ban the IP for this many days (0 is permanent)
			array('config.add', array('sfsc_hp_ban_days', 30)),
			// Which trips match: 0 same IP, 1 same IP and email, 2 same IP, email or username
			array('config.add', array('sfsc_hp_ban_match', 0)),
			// Which trips count: 0 hidden-field trips only, 1 all trips
			array('config.add', array('sfsc_hp_ban_all_reasons', 0)),

			// Posting by its members is blocked in honeypot_listener::block_restricted_posting()
			array('custom', array(array($this, 'create_restricted_group'))),

			array('module.add', array(
				'acp',
				'ACP_SFS_COMPANION',
				array(
					'module_basename'	=> '\phpbbmodders\sfscompanion\acp\main_module',
					'modes'				=> array('honeypot', 'honeypot_trips'),
				),
			)),

			array('config.update', array('sfsc_version', '1.1.0')),
		);
	}

	public function revert_data()
	{
		return array(
			array('custom', array(array($this, 'delete_restricted_group'))),
		);
	}

	/**
	* @throws exception
	*/
	public function create_restricted_group()
	{
		if ($this->get_group_id())
		{
			return;
		}

		if (!function_exists('group_create'))
		{
			include($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
		}

		$group_id = 0;
		$group_attributes = array(
			'group_colour'			=> 'AA0000',
			'group_legend'			=> 0,
			'group_founder_manage'	=> 0,
		);

		// GROUP_HIDDEN: other users and the member themselves see this group
		// as "Undisclosed"; only admins (or group managers) see it plainly
		$error = group_create($group_id, GROUP_HIDDEN, self::GROUP_NAME, '', $group_attributes);

		if ($error)
		{
			throw new exception('Failed to create ' . self::GROUP_NAME . ' group.');
		}
	}

	/**
	* Removes the group (and its memberships and permissions) when the
	* extension's data is deleted.
	*/
	public function delete_restricted_group()
	{
		$group_id = $this->get_group_id();

		if (!$group_id)
		{
			return;
		}

		if (!function_exists('group_delete'))
		{
			include($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
		}

		group_delete($group_id, self::GROUP_NAME);
	}

	/**
	* @return int The restricted group's id, or 0 if it doesn't exist
	*/
	protected function get_group_id()
	{
		$sql = 'SELECT group_id
			FROM ' . GROUPS_TABLE . '
			WHERE ' . $this->db->sql_build_array('SELECT', array('group_name' => self::GROUP_NAME));
		$result = $this->db->sql_query($sql);
		$group_id = (int) $this->db->sql_fetchfield('group_id');
		$this->db->sql_freeresult($result);

		return $group_id;
	}
}
