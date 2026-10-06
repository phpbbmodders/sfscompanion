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

namespace phpbbmodders\sfscompanion\core;

use phpbbmodders\stopforumspam\core\sfsapi;

/**
* Shared helpers for looking up, reporting, and removing suspected spammers.
*
* Every call to StopForumSpam goes through phpbbmodders/stopforumspam's own
* sfsapi service - this extension never talks to the StopForumSpam API
* directly, and reuses that extension's own sfs_api_key config setting.
*/
class functions
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var sfsapi */
	protected $sfsapi;

	protected $phpbb_root_path;
	protected $php_ext;

	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\user $user,
		\phpbb\template\template $template,
		\phpbb\request\request_interface $request,
		\phpbb\config\config $config,
		\phpbb\auth\auth $auth,
		sfsapi $sfsapi,
		$phpbb_root_path,
		$php_ext
	)
	{
		$this->db				= $db;
		$this->user				= $user;
		$this->template			= $template;
		$this->request			= $request;
		$this->config			= $config;
		$this->auth				= $auth;
		$this->sfsapi			= $sfsapi;
		$this->phpbb_root_path	= $phpbb_root_path;
		$this->php_ext			= $php_ext;
	}

	/**
	* Query StopForumSpam for a username/IP/email.
	*
	* @param string $username
	* @param string $ip
	* @param string $email
	* @return array|false [0 => ['username' => 'yes'|'no', 'ip' => ..., 'email' => ...],
	*                      1 => ['username' => int, 'ip' => int, 'email' => int]]
	*                      or false if the lookup couldn't be completed.
	*/
	public function check_stopforumspam($username, $ip, $email)
	{
		if ($username === '' && $ip === '' && $email === '')
		{
			return array();
		}

		$response = $this->sfsapi->sfsapi('query', $username, $ip, $email);

		if (!$response)
		{
			return false;
		}

		$data = json_decode($response, true);

		if (empty($data['success']))
		{
			return false;
		}

		$result = $frequency = array();

		foreach (array('username', 'ip', 'email') as $key)
		{
			$result[$key] = (!empty($data[$key]['appears'])) ? 'yes' : 'no';
			$frequency[$key] = isset($data[$key]['frequency']) ? (int) $data[$key]['frequency'] : 0;
		}

		return array($result, $frequency);
	}

	/**
	* Report a confirmed spammer to StopForumSpam, using the API key already
	* configured for phpbbmodders/stopforumspam.
	*
	* sfsapi() returns true on a genuine success, false when SFS/cURL is
	* unavailable, or a JSON-encoded error string on a cURL failure - only
	* the strict true case counts as reported.
	*
	* @return bool
	*/
	public function report_to_sfs($username, $ip, $email)
	{
		$api_key = isset($this->config['sfs_api_key']) ? $this->config['sfs_api_key'] : '';

		return $this->sfsapi->sfsapi('add', $username, $ip, $email, $this->user->lang['SFSC_SPAM_REASON'], $api_key) === true;
	}

	/**
	* Ban a confirmed spammer by IP, username, and email, back up their row,
	* then delete the account. Backup/ban/delete are plain phpBB core
	* operations - only the "report to SFS" step depends on this extension.
	*
	* Deletion is skipped if the pre-deletion backup couldn't be written -
	* an unwritable store/ directory should never lead to an unrecoverable
	* account removal.
	*
	* @return bool True if the account was banned and deleted, false if the
	*              backup failed and the account was left untouched.
	*/
	public function ban_and_delete($user_id, $username, $ip, $email)
	{
		if (!function_exists('user_ban'))
		{
			include_once($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
		}

		if (!$this->backup($user_id))
		{
			return false;
		}

		user_ban('email', $email, 0, 0, 0, $this->user->lang['SFSC_SPAM_REASON'], $this->user->lang['SFSC_SPAM_REASON']);
		user_ban('user', $username, 0, 0, 0, $this->user->lang['SFSC_SPAM_REASON'], $this->user->lang['SFSC_SPAM_REASON']);

		if ($ip)
		{
			user_ban('ip', $ip, 0, 0, 0, $this->user->lang['SFSC_SPAM_REASON'], $this->user->lang['SFSC_SPAM_REASON']);
		}

		user_delete('remove', $user_id);
		add_log('admin', 'LOG_USER_DELETED', $username);

		return true;
	}

	/**
	* Export a user's row to store/ before deletion, in the SQL dialect for
	* whichever DB engine this board actually runs.
	*
	* @return bool True once the backup file has been written, false if the
	*              user row was missing or the file couldn't be written.
	*/
	public function backup($user_id)
	{
		$sql = 'SELECT *
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			return false;
		}

		$sql_layer = $this->db->get_sql_layer();

		$columns = array_keys($row);
		$values = array_map(array($this->db, 'sql_escape'), array_values($row));

		$insert_sql = 'INSERT INTO ' . USERS_TABLE . ' (' . implode(', ', $columns) . ") VALUES ('" . implode("', '", $values) . "');" . "\n";

		$backup_dir = $this->phpbb_root_path . 'store/sfscompanion/';

		if (!is_dir($backup_dir) && !@mkdir($backup_dir, 0755, true) && !is_dir($backup_dir))
		{
			return false;
		}

		$filename = $backup_dir . 'user_' . (int) $user_id . '_' . time() . '.sql';

		return (bool) @file_put_contents($filename, "-- Backup of user_id " . (int) $user_id . " (" . $sql_layer . ") before deletion by SFS Companion\n" . $insert_sql);
	}

	/**
	* Full single-user SFS check with a rendered report, and the confirm
	* screens for "report to SFS" and "report to SFS + ban + delete".
	* Shared by both the ACP bulk scanner drill-down and the standalone
	* memberlist/ACP-user-overview finder controller.
	*/
	public function full_check($user_id, $u_action)
	{
		$report_to_sfs	= $this->request->variable('report', false);
		$ban_and_delete	= $this->request->variable('report_and_delete', false);

		$sql = 'SELECT user_id, user_ip, user_email, username
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			trigger_error('NO_USER');
		}

		if ($ban_and_delete)
		{
			// Banning and deleting the account is a step up from "can check
			// users" - require real user-management rights, not just the
			// ability to run a lookup. confirm_box() below provides this
			// branch's own CSRF protection, matching the pattern phpBB core
			// uses for confirm-then-delete actions (e.g. acp_reasons.php) -
			// check_form_key() is not re-validated here because its token
			// isn't carried through the confirm_box() round trip.
			if (!($this->auth->acl_get('a_') || $this->auth->acl_get('a_user')))
			{
				trigger_error('NOT_AUTHORISED');
			}

			if (confirm_box(true))
			{
				$reported = $this->report_to_sfs($row['username'], $row['user_ip'], $row['user_email']);
				$deleted = $this->ban_and_delete($row['user_id'], $row['username'], $row['user_ip'], $row['user_email']);

				if (!$deleted)
				{
					$l_done = '<strong><span style="color: #a00;">' . $this->user->lang['FAIL_BACKUP'] . '</span></strong>';
				}
				else if (!$reported)
				{
					$l_done = '<strong>' . $this->user->lang['SUCSESS_DELETE'] . '<br /><span style="color: #a00;">' . $this->user->lang['FAIL_ADD_DATA'] . '</span></strong>';
				}
				else
				{
					$l_done = '<strong>' . $this->user->lang['SUCSESS_DELETE'] . '</strong>';
				}

				$this->template->assign_vars(array(
					'L_DONE'	=> $l_done,
				));

				$this->template->set_filenames(array('body' => 'is_spamer_full.html'));
				$this->template->assign_var('DONE', true);
				$this->finish_page();
			}
			else
			{
				confirm_box(false, $this->user->lang['CONFIRM_DELETE'], build_hidden_fields(array(
					'ch_user'				=> $user_id,
					'report_and_delete'	=> true,
				)));
			}
		}

		if ($report_to_sfs && check_form_key('sfscompanion_full_check'))
		{
			$reported = $this->report_to_sfs($row['username'], $row['user_ip'], $row['user_email']);

			$this->template->assign_vars(array(
				'S_ERROR'	=> !$reported,
			));

			if ($reported)
			{
				$this->template->set_filenames(array('body' => 'is_spamer_full.html'));
				$this->template->assign_var('DONE', true);
				$this->finish_page();
			}
		}

		$check = $this->check_stopforumspam($row['username'], $row['user_ip'], $row['user_email']);

		if ($check === false)
		{
			trigger_error('CONNECTION_ERROR');
		}

		list($insp_data, $freq) = $check + array(array(), array());

		$nick = $banned_ip = $em = false;

		foreach ($insp_data as $key => $value)
		{
			if ($value !== 'yes')
			{
				continue;
			}

			switch ($key)
			{
				case 'username':
					$nick = true;
				break;
				case 'ip':
					$banned_ip = true;
				break;
				case 'email':
					$em = true;
				break;
			}
		}

		if (!$nick && !$em && !$banned_ip)
		{
			$report = $this->user->lang['NOT_SPAMMER'];
			$report_img = ' find';
		}
		else if ($nick && $banned_ip && $em)
		{
			$report = $this->user->lang['SPAMMER'];
			$report_img = ' spam';
		}
		else
		{
			$report = $this->user->lang['POSSIBLE_YES'];
			$report_img = ' em_spam';
		}

		add_form_key('sfscompanion_full_check');

		$this->template->assign_vars(array(
			'IP_FIND'		=> ($banned_ip) ? sprintf($this->user->lang['IP_FIND'], $freq['ip']) : $this->user->lang['IP_NOT_FIND'],
			'FIND_MAIL'		=> ($em) ? sprintf($this->user->lang['EMAIL_FIND'], $freq['email']) : $this->user->lang['EMAIL_NOT_FIND'],
			'FIND_NICK'		=> ($nick) ? sprintf($this->user->lang['NICK_FIND'], $freq['username']) : $this->user->lang['NICK_NOT_FIND'],

			'USER'			=> htmlspecialchars($row['username']),
			'IP'			=> htmlspecialchars($row['user_ip']),
			'EMAIL'			=> htmlspecialchars($row['user_email']),

			'REPORT'		=> $report,
			'CLASS'			=> $report_img,

			'U_ACTION'		=> $u_action,
			'PAGE_TITLE'	=> $this->user->lang['SFS_INFO'],
		));

		$this->template->set_filenames(array('body' => 'is_spamer_full.html'));
		$this->finish_page();
	}

	/**
	* Closes out the report page rendered by full_check().
	*
	* In the ACP (bulk scanner drill-down), is_spamer_full.html is a
	* self-contained page - it includes its own overall_header.html/
	* overall_footer.html, the same way confirm_box() renders its own
	* full page when called from admin context - so we display it and
	* exit directly. On the front end (memberlist/ACP-user-overview
	* finder), the caller has already run page_header(), so page_footer()
	* both displays the body and closes the page.
	*/
	private function finish_page()
	{
		if (defined('IN_ADMIN'))
		{
			$this->template->display('body');

			garbage_collection();
			exit_handler();
		}
		else
		{
			page_footer();
		}
	}

	/**
	* Render a WHOIS lookup for the given IP.
	*/
	/**
	* Returns the WHOIS text for an IP. Rendering is left to the caller -
	* ACP modules assign this to phpBB's own built-in simple_body.html
	* template rather than a template of our own.
	*/
	public function whois_lookup($ip)
	{
		if (!function_exists('user_ipwhois'))
		{
			include_once($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
		}

		return user_ipwhois($ip);
	}

	public function getmicrotime()
	{
		list($usec, $sec) = explode(' ', microtime());

		return ((float) $usec + (float) $sec);
	}
}
