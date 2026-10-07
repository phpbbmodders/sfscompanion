<?php
/**
 *
 * SFS Companion extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\sfscompanion\notification\type;

/**
* Notifies staff when an established member (post count over the
* configured threshold) trips the honeypot and is silently moved into
* the restricted group - most likely a compromised/scripted account, but
* possibly a false positive worth a human look.
*/
class honeypot_restricted extends \phpbb\notification\type\base
{
	/** @var \phpbb\user_loader */
	protected $user_loader;

	public function set_user_loader(\phpbb\user_loader $user_loader)
	{
		$this->user_loader = $user_loader;
	}

	public function get_type()
	{
		return 'phpbbmodders.sfscompanion.notification.type.honeypot_restricted';
	}

	static public $notification_option = array(
		'lang'	=> 'SFSC_NOTIFICATION_TYPE_HP_RESTRICTED',
		'group'	=> 'NOTIFICATION_GROUP_ADMINISTRATION',
	);

	public function is_available()
	{
		return $this->auth->acl_get('a_user');
	}

	/**
	* One notification per restriction: keyed by the trip that caused it, so a
	* member restricted again later notifies staff again.
	*/
	public static function get_item_id($data)
	{
		return (int) $data['trip_id'];
	}

	public static function get_item_parent_id($data)
	{
		return 0;
	}

	public function find_users_for_notification($data, $options = array())
	{
		$options = array_merge(array(
			'ignore_users'	=> array(),
		), $options);

		$admin_ary = $this->auth->acl_get_list(false, 'a_user', false);
		$users = (!empty($admin_ary[0]['a_user'])) ? $admin_ary[0]['a_user'] : array();

		$sql = 'SELECT user_id
			FROM ' . USERS_TABLE . '
			WHERE user_type = ' . USER_FOUNDER;
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$users[] = (int) $row['user_id'];
		}
		$this->db->sql_freeresult($result);

		if (empty($users))
		{
			return array();
		}

		return $this->check_user_notification_options(array_unique($users), $options);
	}

	public function users_to_query()
	{
		return array($this->get_restricted_user_id());
	}

	public function get_avatar()
	{
		return $this->user_loader->get_avatar($this->get_restricted_user_id(), false, true);
	}

	public function get_title()
	{
		$username = $this->user_loader->get_username($this->get_restricted_user_id(), 'no_profile');

		return $this->language->lang('SFSC_NOTIFICATION_HP_RESTRICTED', $username, (int) $this->get_data('user_posts'));
	}

	public function get_url()
	{
		return $this->user_loader->get_username($this->get_restricted_user_id(), 'profile');
	}

	public function get_email_template()
	{
		return '@phpbbmodders_sfscompanion/honeypot_restricted';
	}

	public function get_email_template_variables()
	{
		// Note: {USERNAME} is reserved by phpBB's email system for the
		// recipient's own name - the flagged member's name is passed
		// separately to avoid colliding with it.
		$username = $this->user_loader->get_username($this->get_restricted_user_id(), 'username');

		return array(
			'RESTRICTED_USERNAME'	=> html_entity_decode($username, ENT_COMPAT),
			'USER_POSTS'			=> (int) $this->get_data('user_posts'),
			// Emails need an absolute link, independent of the sender's permissions
			'U_USER_DETAILS'		=> generate_board_url() . "/memberlist.{$this->php_ext}?mode=viewprofile&u=" . (int) $this->get_restricted_user_id(),
		);
	}

	/**
	* @return int The restricted member's user id
	*/
	protected function get_restricted_user_id()
	{
		return (int) $this->get_data('user_id');
	}

	public function create_insert_array($data, $pre_create_data = array())
	{
		$this->set_data('user_id', (int) $data['user_id']);
		$this->set_data('user_posts', $data['user_posts']);

		parent::create_insert_array($data, $pre_create_data);
	}
}
