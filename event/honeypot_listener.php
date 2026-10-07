<?php
/**
 *
 * SFS Companion extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\sfscompanion\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Honeypot checks on registration, posting and private messages.
*
* Two independent bot signals, either one trips detection: hidden fields
* real users never see or fill in, and a minimum time between showing the
* form and submitting it. The time check reuses phpBB's own signed
* creation_time field, already validated by check_form_key() before any of
* these events fire, so it can't be forged.
*
* Registrations that trip a check are rejected outright, since there's no
* account to restrict yet. Posts and private messages are allowed to appear
* to succeed (so a scripted bot doesn't detect and adapt), while the account
* is silently moved into a hidden group that can't post or reply. Staff are
* notified when the account already had enough posts to look established.
*
* Every trip is logged to the sfsc_honeypot_trips table, with the details
* needed to report the sender to Stop Forum Spam from the ACP.
*/
class honeypot_listener implements EventSubscriberInterface
{
	/** Field names deliberately look like normal optional fields to bait autofill/scripted bots */
	const FIELD_NAMES = array('hp_website', 'hp_phone');

	/** Group tripped accounts are moved into; created by the honeypot_1_1_0 migration */
	const RESTRICTED_GROUP_NAME = 'SFSC_HONEYPOT_RESTRICTED';

	/** Characters of the message kept in the trip log as evidence */
	const EXCERPT_LENGTH = 1000;

	/**
	* Admin-side reason on automatic IP bans. Deliberately not translated:
	* it must stay identical so the ACP can find and delete exactly these bans.
	*/
	const AUTO_BAN_REASON = 'SFS Companion honeypot auto-ban';

	/** sfsc_hp_ban_match values */
	const BAN_MATCH_IP = 0;
	const BAN_MATCH_IP_EMAIL = 1;
	const BAN_MATCH_ANY = 2;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\notification\manager */
	protected $notification_manager;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var string */
	protected $trips_table;

	/** @var string */
	protected $phpbb_root_path;

	/** @var string */
	protected $php_ext;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\request\request_interface $request,
		\phpbb\user $user,
		\phpbb\language\language $language,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\notification\manager $notification_manager,
		\phpbb\auth\auth $auth,
		\phpbb\template\template $template,
		$trips_table,
		$phpbb_root_path,
		$php_ext
	)
	{
		$this->config				= $config;
		$this->request				= $request;
		$this->user					= $user;
		$this->language				= $language;
		$this->db					= $db;
		$this->notification_manager	= $notification_manager;
		$this->auth					= $auth;
		$this->template				= $template;
		$this->trips_table			= $trips_table;
		$this->phpbb_root_path		= $phpbb_root_path;
		$this->php_ext				= $php_ext;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.ucp_register_data_after'				=> 'check_registration',
			'core.posting_modify_submission_errors'		=> 'check_posting',
			'core.ucp_pm_compose_modify_parse_after'	=> 'check_pm',
			'core.modify_posting_auth'					=> 'block_restricted_posting',
			'core.page_header_after'					=> 'assign_template_vars',
		);
	}

	/**
	* Tell the registration and posting templates whether to add the decoy fields.
	*/
	public function assign_template_vars()
	{
		$this->template->assign_var('S_SFSC_HONEYPOT', (bool) $this->config['sfsc_hp_enabled']);
	}

	/**
	* Registration has no account yet to restrict, so a trip is rejected.
	*
	* @param \phpbb\event\data $event
	*/
	public function check_registration($event)
	{
		if (!$this->config['sfsc_hp_enabled'] || !$this->config['sfsc_hp_check_register'] || !check_form_key('ucp_register') || !($reason = $this->detect()))
		{
			return;
		}

		$data = $event['data'];

		$this->log_trip('register', $reason, ANONYMOUS, $data['username'], $data['email'], '', '');

		$error = $event['error'];
		// Deliberately generic, so a bot can't tell which check it tripped
		$error[] = $this->language->lang('SFSC_HP_ERROR');
		$event['error'] = $error;
	}

	/**
	* @param \phpbb\event\data $event
	*/
	public function check_posting($event)
	{
		// Previews don't count, and a request without a valid form token may
		// be forged: neither may restrict anyone
		if (!$event['submit'] || !$this->config['sfsc_hp_enabled'] || !$this->config['sfsc_hp_check_posting'] || !check_form_key('posting') || !($reason = $this->detect()))
		{
			return;
		}

		// The person submitting, not the post's author: on an edit they can differ
		$poster_id = (int) $this->user->data['user_id'];

		if ($poster_id === ANONYMOUS)
		{
			// Guests have no account; Stop Forum Spam adds an email field to the guest posting form
			$username = $this->request->variable('username', '', true);
			$email = $this->request->variable('email', '');
		}
		else
		{
			$username = $this->user->data['username'];
			$email = $this->user->data['user_email'];
		}

		$trip_id = $this->log_trip('posting', $reason, $poster_id, $username, $email, $this->request->variable('subject', '', true), $this->request->variable('message', '', true));

		if ($poster_id !== ANONYMOUS && !$this->is_staff())
		{
			$this->restrict_user($poster_id, $trip_id);
		}
	}

	/**
	* @param \phpbb\event\data $event
	*/
	public function check_pm($event)
	{
		if (!$event['submit'] || !$this->config['sfsc_hp_enabled'] || !$this->config['sfsc_hp_check_pm'] || !check_form_key('ucp_pm_compose') || !($reason = $this->detect()))
		{
			return;
		}

		// PM composing always requires a logged-in sender
		$user_id = (int) $this->user->data['user_id'];

		$trip_id = $this->log_trip('pm', $reason, $user_id, $this->user->data['username'], $this->user->data['user_email'], $this->request->variable('subject', '', true), $this->request->variable('message', '', true));

		if (!$this->is_staff())
		{
			$this->restrict_user($user_id, $trip_id);
		}
	}

	/**
	* Staff are never restricted or banned by the honeypot, though their
	* trips are still logged (a password manager filling a decoy field, say).
	*
	* @return bool Whether the current user is an administrator or moderator
	*/
	protected function is_staff()
	{
		return !empty($this->user->data['is_registered']) && ($this->auth->acl_get('a_') || $this->auth->acl_getf_global('m_'));
	}

	/**
	* Members of the restricted group can't start topics or reply anywhere.
	*
	* Enforced here rather than with forum permissions, which are set per
	* forum and wouldn't cover forums created later.
	*
	* @param \phpbb\event\data $event
	*/
	public function block_restricted_posting($event)
	{
		if (!in_array($event['mode'], array('post', 'reply', 'quote')) || empty($this->user->data['is_registered']))
		{
			return;
		}

		$group_id = $this->get_restricted_group_id();

		if (!$group_id)
		{
			return;
		}

		$sql = 'SELECT group_id
			FROM ' . USER_GROUP_TABLE . '
			WHERE user_id = ' . (int) $this->user->data['user_id'] . '
				AND group_id = ' . (int) $group_id . '
				AND user_pending = 0';
		$result = $this->db->sql_query($sql);
		$restricted = (bool) $this->db->sql_fetchfield('group_id');
		$this->db->sql_freeresult($result);

		if ($restricted)
		{
			$event['is_authed'] = false;
		}
	}

	/**
	* @return string|false 'field' or 'time' if a signal tripped, false otherwise
	*/
	protected function detect()
	{
		foreach (self::FIELD_NAMES as $field_name)
		{
			if ($this->request->variable($field_name, '') !== '')
			{
				return 'field';
			}
		}

		$seconds = $this->submit_seconds();
		$min_seconds = (int) $this->config['sfsc_hp_min_seconds'];

		if ($seconds !== null && $min_seconds > 0 && $seconds < $min_seconds)
		{
			return 'time';
		}

		return false;
	}

	/**
	* @return int|null Seconds between showing the form and submitting it, null if unknown
	*/
	protected function submit_seconds()
	{
		// abs() as in check_form_key(), which accepts a negated timestamp with the
		// same signature; without it a negated value would look very old
		$creation_time = abs($this->request->variable('creation_time', 0));

		return $creation_time ? max(0, time() - $creation_time) : null;
	}

	/**
	* Record a trip with the details needed to report it to Stop Forum Spam.
	*
	* @param string	$form		'register', 'posting' or 'pm'
	* @param string	$reason		'field' or 'time'
	* @param int	$user_id	the account, or ANONYMOUS
	* @param string	$username	submitted or account username
	* @param string	$email		submitted or account email address
	* @param string	$subject	post or PM subject
	* @param string	$message	post or PM text; only an excerpt is kept
	* @return int	the new trip's id
	*/
	protected function log_trip($form, $reason, $user_id, $username, $email, $subject, $message)
	{
		$sql_ary = array(
			'trip_time'			=> time(),
			'trip_form'			=> $form,
			'trip_reason'		=> $reason,
			'submit_seconds'	=> (int) $this->submit_seconds(),
			'user_id'			=> (int) $user_id,
			'username'			=> utf8_substr((string) $username, 0, 255),
			'user_email'		=> utf8_substr((string) $email, 0, 100),
			'user_ip'			=> (string) $this->user->ip,
			'user_agent'		=> utf8_substr((string) $this->user->browser, 0, 255),
			'subject'			=> utf8_substr((string) $subject, 0, 255),
			'excerpt'			=> utf8_substr((string) $message, 0, self::EXCERPT_LENGTH),
			'sfs_reported'		=> 0,
		);

		$this->db->sql_query('INSERT INTO ' . $this->trips_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));

		$trip_id = (int) $this->db->sql_nextid();

		$this->maybe_ban_ip($trip_id, $sql_ary);

		return $trip_id;
	}

	/**
	* Ban the trip's IP once enough matching trips have come in within the
	* configured time window. The ban has a fixed admin-side reason (so the
	* ACP can delete only these bans) and no reason shown to the visitor.
	*
	* @param int	$trip_id	the trip just logged
	* @param array	$trip		its column values
	*/
	protected function maybe_ban_ip($trip_id, array $trip)
	{
		$threshold = (int) $this->config['sfsc_hp_ban_threshold'];

		if ($threshold <= 0 || $trip['user_ip'] === '')
		{
			return;
		}

		if (!$this->config['sfsc_hp_ban_all_reasons'] && $trip['trip_reason'] !== 'field')
		{
			return;
		}

		// Never lock out staff, whatever tripped
		if ($trip['user_id'] != ANONYMOUS && $this->is_staff())
		{
			return;
		}

		$ip = $this->db->sql_escape($trip['user_ip']);
		$email = $this->db->sql_escape($trip['user_email']);
		$username = $this->db->sql_escape($trip['username']);

		switch ((int) $this->config['sfsc_hp_ban_match'])
		{
			case self::BAN_MATCH_IP_EMAIL:
				if ($trip['user_email'] === '')
				{
					return;
				}
				$match = "user_ip = '$ip' AND user_email = '$email'";
			break;

			case self::BAN_MATCH_ANY:
				$match = "user_ip = '$ip'";
				$match .= ($trip['user_email'] !== '') ? " OR user_email = '$email'" : '';
				$match .= ($trip['username'] !== '') ? " OR username = '$username'" : '';
			break;

			case self::BAN_MATCH_IP:
			default:
				$match = "user_ip = '$ip'";
			break;
		}

		$sql = 'SELECT COUNT(trip_id) AS trips
			FROM ' . $this->trips_table . '
			WHERE trip_time >= ' . (time() - max(1, (int) $this->config['sfsc_hp_ban_window_hours']) * 3600) . '
				AND (' . $match . ')' .
				(($this->config['sfsc_hp_ban_all_reasons']) ? '' : " AND trip_reason = 'field'");
		$result = $this->db->sql_query($sql);
		$trips = (int) $this->db->sql_fetchfield('trips');
		$this->db->sql_freeresult($result);

		if ($trips < $threshold || $this->ip_is_banned($trip['user_ip']))
		{
			return;
		}

		if (!function_exists('user_ban'))
		{
			include($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
		}

		// Ban length is in minutes; 0 is permanent
		user_ban('ip', $trip['user_ip'], (int) $this->config['sfsc_hp_ban_days'] * 1440, '', false, self::AUTO_BAN_REASON, '');

		$this->db->sql_query('UPDATE ' . $this->trips_table . ' SET ip_banned = 1 WHERE trip_id = ' . (int) $trip_id);
	}

	/**
	* @param string $ip
	* @return bool Whether an active, non-exclusion ban already covers exactly this IP
	*/
	protected function ip_is_banned($ip)
	{
		$sql = 'SELECT ban_id
			FROM ' . BANLIST_TABLE . "
			WHERE ban_ip = '" . $this->db->sql_escape($ip) . "'
				AND ban_exclude = 0
				AND (ban_end = 0 OR ban_end > " . time() . ')';
		$result = $this->db->sql_query_limit($sql, 1);
		$banned = (bool) $this->db->sql_fetchfield('ban_id');
		$this->db->sql_freeresult($result);

		return $banned;
	}

	/**
	* Move a user into the restricted group, and notify staff if the account
	* already had enough posts to look established.
	*
	* @param int $user_id
	* @param int $trip_id the trip that caused it; identifies the staff notification
	*/
	protected function restrict_user($user_id, $trip_id)
	{
		$group_id = $this->get_restricted_group_id();

		if (!$group_id)
		{
			return;
		}

		$sql = 'SELECT user_posts
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$user_posts = $this->db->sql_fetchfield('user_posts');
		$this->db->sql_freeresult($result);

		if ($user_posts === false)
		{
			return;
		}

		$sql = 'SELECT group_id
			FROM ' . USER_GROUP_TABLE . '
			WHERE user_id = ' . (int) $user_id . '
				AND group_id = ' . (int) $group_id;
		$result = $this->db->sql_query($sql);
		$already_restricted = (bool) $this->db->sql_fetchfield('group_id');
		$this->db->sql_freeresult($result);

		if ($already_restricted)
		{
			return;
		}

		if (!function_exists('group_user_add'))
		{
			include($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
		}

		group_user_add($group_id, array($user_id));

		if ((int) $user_posts >= (int) $this->config['sfsc_hp_notify_posts'])
		{
			$this->notification_manager->add_notifications('phpbbmodders.sfscompanion.notification.type.honeypot_restricted', array(
				'trip_id'		=> (int) $trip_id,
				'user_id'		=> (int) $user_id,
				'user_posts'	=> (int) $user_posts,
			));
		}
	}

	/**
	* @return int The restricted group's id, or 0 if it doesn't exist
	*/
	protected function get_restricted_group_id()
	{
		$sql = 'SELECT group_id
			FROM ' . GROUPS_TABLE . '
			WHERE ' . $this->db->sql_build_array('SELECT', array('group_name' => self::RESTRICTED_GROUP_NAME));
		$result = $this->db->sql_query($sql);
		$group_id = (int) $this->db->sql_fetchfield('group_id');
		$this->db->sql_freeresult($result);

		return $group_id;
	}
}
