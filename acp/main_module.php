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

namespace phpbbmodders\sfscompanion\acp;

class main_module
{
	/** Users checked per scan page; each is a live Stop Forum Spam lookup */
	const SCAN_PER_PAGE = 6;

	public $u_action;
	public $tpl_name;
	public $page_title;

	public function main($id, $mode)
	{
		global $phpbb_container;

		$sfs = $phpbb_container->get('phpbbmodders.sfscompanion.core.functions');

		switch ($mode)
		{
			case 'settings':
				$this->settings();
			break;

			case 'blocks':
				$this->log_view('blocks', $sfs);
			break;

			case 'errors':
				$this->log_view('errors', $sfs);
			break;

			case 'scan':
				$this->scan($sfs);
			break;

			case 'honeypot':
				$this->honeypot_settings();
			break;

			case 'honeypot_trips':
				$this->honeypot_trips($sfs);
			break;
		}
	}

	private function settings()
	{
		global $config, $request, $template, $user;

		add_form_key('sfscompanion_settings');

		$this->tpl_name = 'acp_sfs_settings';
		$this->page_title = $user->lang('ACP_SFS_SETTINGS');

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('sfscompanion_settings'))
			{
				trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$config->set('sfsc_expire_days', max(0, $request->variable('sfsc_expire_days', 0)));

			trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
		}

		$template->assign_vars(array(
			'SFSC_EXPIRE_DAYS'	=> (int) $config['sfsc_expire_days'],
			'U_ACTION'			=> $this->u_action,
		));
	}

	/**
	* Shared viewer for both the "Spam blocks" and "SFS errors" ACP pages -
	* thin wrapper around phpBB's own view_log(), scoped to this
	* extension's log_operation values via the core.get_logs_modify_type
	* listener.
	*/
	private function log_view($view, $sfs)
	{
		global $db, $template, $request, $user, $config, $phpbb_container;

		$whois = $request->variable('whois', false);

		if ($whois)
		{
			$this->tpl_name = 'simple_body';
			$this->page_title = 'WHOIS';

			$template->assign_vars(array(
				'MESSAGE_TITLE'	=> $user->lang['WHOIS'],
				'MESSAGE_TEXT'	=> nl2br($sfs->whois_lookup($request->variable('ip', ''))),
			));

			return;
		}

		add_form_key('sfscompanion_' . $view);

		$this->tpl_name = 'acp_sfs_logs';
		$this->page_title = $user->lang(($view === 'blocks') ? 'ACP_SFS_BLOCKS' : 'ACP_SFS_ERRORS');

		$start		= $request->variable('start', 0);
		$deletemark	= $request->variable('delmarked', false, false, \phpbb\request\request_interface::POST);
		$deleteall	= $request->variable('delall', false, false, \phpbb\request\request_interface::POST);
		$marked		= $request->variable('mark', array(0));
		$sort_days	= $request->variable('st', 0);
		$sort_key	= $request->variable('sk', 't');
		$sort_dir	= $request->variable('sd', 'd');
		$isearch	= $request->variable('isearch', '');

		// LOG_SFS_CURL_ERROR/LOG_SFS_NEED_CURL are logged by
		// phpbbmodders/stopforumspam's own sfsapi service - the same service
		// this extension's own lookups/reports go through - so they belong
		// in "errors" alongside the registration/posting-time LOG_SFS_DOWN* entries.
		$ops = ($view === 'blocks') ? array('LOG_SFS_MESSAGE') : array('LOG_SFS_DOWN', 'LOG_SFS_DOWN_USER_ALLOWED', 'LOG_SFS_CURL_ERROR', 'LOG_SFS_NEED_CURL');

		if ($deletemark || $deleteall)
		{
			// confirm_box() below provides this action's CSRF protection -
			// check_form_key() is not used here because its token isn't
			// carried through the confirm_box() round trip, matching how
			// phpBB core's own confirm-then-delete actions are guarded
			// (e.g. acp_reasons.php).
			if (confirm_box(true))
			{
				$sql_where = ' AND ' . $db->sql_in_set('log_operation', $ops);
				// Same IP match as the listing, so "Delete all" removes what's shown
				$sql_where .= ($isearch === '') ? '' : ' AND ' . $phpbb_container->get('phpbbmodders.sfscompanion.listener')->ip_search_sql($isearch, 'log_ip');

				if ($deletemark && count($marked))
				{
					$sql = 'DELETE FROM ' . LOG_TABLE . '
						WHERE ' . $db->sql_in_set('log_id', $marked) . $sql_where;
					$db->sql_query($sql);
				}
				else if ($deleteall)
				{
					$sql = 'DELETE FROM ' . LOG_TABLE . '
						WHERE log_id <> 0' . $sql_where;
					$db->sql_query($sql);
				}

				add_log('admin', ($view === 'blocks') ? 'LOG_CLEAR_SFS_BLOCKS' : 'LOG_CLEAR_SFS_ERRORS');
				redirect($this->u_action);
			}
			else
			{
				confirm_box(false, $user->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
					'start'		=> $start,
					'delmarked'	=> $deletemark,
					'delall'	=> $deleteall,
					'mark'		=> $marked,
					'st'		=> $sort_days,
					'sk'		=> $sort_key,
					'sd'		=> $sort_dir,
					'isearch'	=> $isearch,
				)));
			}
		}

		$limit_days = array(0 => $user->lang['ALL_ENTRIES'], 1 => $user->lang['1_DAY'], 7 => $user->lang['7_DAYS'], 14 => $user->lang['2_WEEKS'], 30 => $user->lang['1_MONTH'], 90 => $user->lang['3_MONTHS'], 180 => $user->lang['6_MONTHS'], 365 => $user->lang['1_YEAR']);
		$sort_by_text = array('u' => $user->lang['SORT_USERNAME'], 't' => $user->lang['SORT_DATE'], 'i' => $user->lang['SORT_IP']);
		$sort_by_sql = array('u' => 'u.username_clean', 't' => 'l.log_time', 'i' => 'l.log_ip');

		$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
		gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);

		$log_time = ($sort_days) ? (time() - ($sort_days * 86400)) : 0;
		$sql_sort = $sort_by_sql[$sort_key] . ' ' . (($sort_dir === 'd') ? 'DESC' : 'ASC');

		// mode 'users' (plural) = board-wide LOG_USERS entries, not scoped to one reportee;
		// the actual LOG_SFS_* restriction is applied by the core.get_logs_modify_type listener
		$log_mode = ($view === 'blocks') ? 'users' : 'admin';

		$log_data = array();
		$log_count = 0;
		$listener = $phpbb_container->get('phpbbmodders.sfscompanion.listener');
		$listener->set_log_filter($ops, $isearch);
		$start = view_log($log_mode, $log_data, $log_count, $config['topics_per_page'], $start, 0, 0, 0, $log_time, $sql_sort);
		$listener->set_log_filter(null);

		$base_url = $this->u_action . "&amp;$u_sort_param";

		/** @var \phpbb\pagination $pagination */
		$pagination = $GLOBALS['phpbb_container']->get('pagination');
		$pagination->generate_template_pagination($base_url, 'pagination', 'start', $log_count, $config['topics_per_page'], $start);

		foreach ($log_data as $row)
		{
			$template->assign_block_vars('log', array(
				'USERNAME'	=> $row['username_full'],
				'IP'		=> $row['ip'],
				'DATE'		=> $user->format_date($row['time']),
				'ACTION'	=> $row['action'],
				'ID'		=> $row['id'],
				'U_IP'		=> (!empty($row['ip'])) ? $this->u_action . '&amp;whois=true&amp;ip=' . $row['ip'] : '',
			));
		}

		$template->assign_vars(array(
			'U_ACTION'		=> $this->u_action . "&amp;start=$start",
			'S_LIMIT_DAYS'	=> $s_limit_days,
			'S_SORT_KEY'	=> $s_sort_key,
			'S_SORT_DIR'	=> $s_sort_dir,
			'ISEARCH'		=> $isearch,
		));
	}

	/**
	* Bulk scanner: lists recently-registered users and checks each one
	* against SFS inline. Same per-page check cost as the original
	* (6 users/page), just delegated to phpbbmodders/stopforumspam's own
	* sfsapi service instead of a direct API call.
	*/
	private function scan($sfs)
	{
		global $db, $user, $template, $request, $phpbb_root_path, $phpEx, $phpbb_container;

		include_once($phpbb_root_path . 'includes/functions_user.' . $phpEx);

		$default_key = 'a';

		$start		= $request->variable('start', 0);
		$delmarked	= $request->variable('delmarked', false);
		$filter		= $request->variable('filter', '', true);
		$filter_key	= $request->variable('f_opt', 1);
		$no_post	= $request->variable('no_posts', '');
		$sort_key	= $request->variable('sk', $default_key);
		$sort_dir	= $request->variable('sd', 'a');
		$full_check	= $request->variable('full_check', false);
		$ip			= $request->variable('ip', '');
		$ch_user	= $request->variable('ch_user', '');
		$whois		= $request->variable('whois', false);
		$action		= $request->variable('f', 0);
		$s_inactive	= $request->variable('s_inactive', false);
		$users		= $request->variable('id_list', array(0));

		$filter_options = array(1 => 'ip', 2 => 'email');
		$per_page = self::SCAN_PER_PAGE;

		$this->tpl_name = 'acp_sfs_scan';
		$this->page_title = $user->lang('ACP_SFS_SCAN');

		add_form_key('sfscompanion_scan');

		if ($full_check)
		{
			// full_check() renders its own page and terminates via exit_handler()
			$sfs->full_check($ch_user, $this->u_action . '&amp;full_check=true&amp;ch_user=' . $ch_user);
		}

		if ($whois)
		{
			$this->tpl_name = 'simple_body';
			$this->page_title = 'WHOIS';

			$template->assign_vars(array(
				'MESSAGE_TITLE'	=> $user->lang['WHOIS'],
				'MESSAGE_TEXT'	=> nl2br($sfs->whois_lookup($ip)),
			));

			return;
		}

		$sort_key_text = array('a' => $user->lang['SORT_JOINED'], 'b' => $user->lang['SORT_USERNAME'], 'c' => $user->lang['SORT_IP'], 'd' => $user->lang['SFSC_SORT_POST'], 'e' => $user->lang['SFSC_SORT_EMAIL'], 'l' => $user->lang['LAST_VISIT']);
		$sort_key_sql = array('a' => 'user_regdate', 'b' => 'username_clean', 'c' => 'user_ip', 'd' => 'user_posts', 'e' => 'user_email', 'l' => 'user_lastvisit');
		$sort_dir_text = array('a' => $user->lang['ASCENDING'], 'd' => $user->lang['DESCENDING']);

		if (!isset($sort_key_sql[$sort_key]))
		{
			$sort_key = $default_key;
		}

		$this->assign_options('sort_keys', $sort_key_text, $sort_key);
		$this->assign_options('sort_dirs', $sort_dir_text, $sort_dir);
		$this->assign_options('filter_keys', array(1 => $user->lang['IP'], 2 => $user->lang['EMAIL_ADDRESS']), $filter_key);

		$pagination = $phpbb_container->get('pagination');
		$pagination_url = $this->u_action . '&amp;filter=' . urlencode($filter) . '&amp;f=' . (int) $action . '&amp;no_posts=' . $no_post . '&amp;s_inactive=' . $s_inactive . '&amp;sd=' . $sort_dir . '&amp;sk=' . $sort_key . '&amp;f_opt=' . (int) $filter_key;

		$sql_where = '';
		if ($filter && isset($filter_options[$filter_key]))
		{
			$sql_where .= ' AND user_' . $filter_options[$filter_key] . ' ' . $db->sql_like_expression(str_replace('*', $db->get_any_char(), $filter));
		}
		$sql_where .= ($no_post) ? ' AND user_posts = 0' : '';
		$sql_where .= ($s_inactive) ? ' AND user_inactive_reason <> 0' : '';
		$order_by = ' ORDER BY ' . $sort_key_sql[$sort_key] . ' ' . (($sort_dir === 'a') ? 'ASC' : 'DESC');

		if ($delmarked)
		{
			// confirm_box() below provides this action's CSRF protection -
			// check_form_key() is not used here because its token isn't
			// carried through the confirm_box() round trip, matching how
			// phpBB core's own confirm-then-delete actions are guarded
			// (e.g. acp_reasons.php).
			if (confirm_box(true))
			{
				if (count($users))
				{
					// $users is already the exact set of checked ids - no
					// pagination offset applies on top of that, or a page
					// beyond the first would skip straight past all of them.
					$sql = 'SELECT user_id, user_email, username, user_ip
						FROM ' . USERS_TABLE . '
						WHERE ' . $db->sql_in_set('user_id', array_map('intval', $users)) . $sql_where . $order_by;
					$result = $db->sql_query($sql);

					$deleted_count = $report_failed_count = $backup_failed_count = 0;

					while ($row = $db->sql_fetchrow($result))
					{
						$reported = $sfs->report_to_sfs($row['username'], $row['user_ip'], $row['user_email']);
						$deleted = $sfs->ban_and_delete($row['user_id'], $row['username'], $row['user_ip'], $row['user_email']);

						if (!$deleted)
						{
							$backup_failed_count++;
							continue;
						}

						$deleted_count++;

						if (!$reported)
						{
							$report_failed_count++;
						}
					}
					$db->sql_freeresult($result);

					$msg = sprintf($user->lang['SFSC_SUCCESS_DELETE_COUNT'], $deleted_count);

					if ($report_failed_count)
					{
						$msg .= '<br />' . sprintf($user->lang['SFSC_FAIL_ADD_DATA_COUNT'], $report_failed_count);
					}

					if ($backup_failed_count)
					{
						$msg .= '<br />' . sprintf($user->lang['SFSC_FAIL_BACKUP_COUNT'], $backup_failed_count);
					}
				}
				else
				{
					$msg = $user->lang['SFSC_NONE_SELECTED'];
				}

				meta_refresh(3, $pagination_url);
				trigger_error($msg . '<br /><br />' . sprintf($user->lang['RETURN_PAGE'], '<a href="' . $pagination_url . '">', '</a>'));
			}
			else
			{
				if (empty($users))
				{
					$msg = $user->lang['SFSC_NONE_SELECTED'];
					meta_refresh(3, $pagination_url);
					trigger_error($msg . '<br /><br />' . sprintf($user->lang['RETURN_PAGE'], '<a href="' . $pagination_url . '">', '</a>'), E_USER_WARNING);
				}

				confirm_box(false, $user->lang['SFSC_CONFIRM_DELETE'], build_hidden_fields(array(
					'id_list'	=> $users,
					'delmarked'	=> $delmarked,
				)));
			}

			return;
		}

		$current_time = time();
		$day = 86400;
		$periods = array(0 => $day, 1 => $day * 7, 2 => $day * 30, 3 => $day * 365, 4 => 0);
		$labels = array(0 => 'SFSC_PER_DAY', 1 => 'SFSC_PER_WEEK', 2 => 'SFSC_PER_MONTH', 3 => 'SFSC_PER_YEAR', 4 => 'SFSC_PER_ALL_TIME');

		$action = isset($periods[$action]) ? $action : 0;
		$period = $periods[$action] ? ($current_time - $periods[$action]) : 0;

		$this->assign_options('periods', array_map(array($user, 'lang'), $labels), $action);

		$time_start = $sfs->getmicrotime();

		$sql = 'SELECT COUNT(user_id) AS total
			FROM ' . USERS_TABLE . '
			WHERE user_type <> ' . USER_IGNORE . '
				AND user_type <> ' . USER_FOUNDER . '
				AND user_regdate > ' . (int) $period . $sql_where;
		$result = $db->sql_query($sql);
		$total_users = (int) $db->sql_fetchfield('total');
		$db->sql_freeresult($result);

		$sql = 'SELECT user_id, username, user_ip, user_email, user_regdate, user_posts, user_lastvisit, user_inactive_reason
			FROM ' . USERS_TABLE . '
			WHERE user_type <> ' . USER_IGNORE . ' AND user_type <> ' . USER_FOUNDER . '
				AND user_regdate > ' . (int) $period . $sql_where . $order_by;
		$result = $db->sql_query_limit($sql, $per_page, $start);

		while ($row = $db->sql_fetchrow($result))
		{
			$check = $sfs->check_stopforumspam($row['username'], $row['user_ip'], $row['user_email']);

			$em = $nick = $banned_ip = false;
			$fail_chk = false;

			if ($check === false)
			{
				$fail_chk = true;
			}
			else if (count($check))
			{
				list($insp_data,) = $check;
				$nick = ($insp_data['username'] === 'yes');
				$banned_ip = ($insp_data['ip'] === 'yes');
				$em = ($insp_data['email'] === 'yes');
			}

			$class = ' find';
			if ($em && $nick)
			{
				$class = ' spam';
			}
			else if ($em || $nick)
			{
				$class = ' em_spam';
			}
			else if (empty($row['user_ip']) || $banned_ip)
			{
				$class = ' ip';
			}

			$template->assign_block_vars('row', array(
				'USER_ID'			=> $row['user_id'],
				'CLASS'				=> $class,

				'USER_REG_DATE'		=> $user->format_date($row['user_regdate']),
				'LAST_VISIT'		=> ($row['user_lastvisit']) ? $user->format_date($row['user_lastvisit']) : $user->lang['NEVER'],
				'USER_NAME'			=> '<a href="' . append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=viewprofile&amp;u=' . (int) $row['user_id']) . '">' . htmlspecialchars($row['username']) . '</a>',
				'USER_EMAIL'		=> htmlspecialchars($row['user_email']),
				'USER_POSTS'		=> $row['user_posts'],
				'USER_IP'			=> (!empty($row['user_ip'])) ? htmlspecialchars($row['user_ip']) : $user->lang['SFSC_READ_COMMENT'],
				'U_POSTS'			=> append_sid("{$phpbb_root_path}search.$phpEx", 'author_id=' . (int) $row['user_id'] . '&sr=posts'),
				'S_USER_IP'			=> (!empty($row['user_ip'])) ? $this->u_action . '&amp;whois=true&amp;ip=' . $row['user_ip'] : '',
				'U_FULL_CHECK'		=> $this->u_action . '&amp;full_check=true&amp;ch_user=' . (int) $row['user_id'],
				'S_FAIL_CHK'		=> $fail_chk,
			));
		}
		$db->sql_freeresult($result);

		$pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $total_users, $per_page, $start);

		$template->assign_vars(array(
			'FILTER'			=> $filter,
			'NOPOSTS'			=> (bool) $no_post,
			'UNACTIVE'			=> (bool) $s_inactive,
			'TOTAL_USERS'		=> ($total_users) ? $user->lang('SFSC_LIST_USERS', $total_users) : '',
			'EXEC_TIME'			=> sprintf($user->lang['SFSC_EXEC_TIME'], round($sfs->getmicrotime() - $time_start, 4)),
			'U_ACTION'			=> $pagination_url,
		));
	}

	/**
	* Honeypot settings: which forms are checked, the minimum time to
	* submit, when staff are notified, and how long trips are kept.
	*/
	private function honeypot_settings()
	{
		global $config, $request, $template, $user, $db;

		add_form_key('sfscompanion_honeypot');

		$this->tpl_name = 'acp_sfs_honeypot';
		$this->page_title = $user->lang('ACP_SFSC_HONEYPOT');

		$auto_ban_where = 'ban_reason = \'' . $db->sql_escape(\phpbbmodders\sfscompanion\event\honeypot_listener::AUTO_BAN_REASON) . '\'';

		if ($request->is_set_post('delete_auto_bans'))
		{
			// Nothing to delete: say so straight away rather than asking to confirm
			if (!$this->count_auto_bans($auto_ban_where) && !$request->is_set_post('confirm'))
			{
				trigger_error($user->lang('SFSC_HP_AUTO_BANS_DELETED', 0) . adm_back_link($this->u_action));
			}

			// confirm_box() provides this action's CSRF protection
			if (confirm_box(true))
			{
				$sql = 'SELECT ban_id
					FROM ' . BANLIST_TABLE . '
					WHERE ' . $auto_ban_where;
				$result = $db->sql_query($sql);
				$ban_ids = array_map('intval', array_column($db->sql_fetchrowset($result), 'ban_id'));
				$db->sql_freeresult($result);

				if (count($ban_ids))
				{
					if (!function_exists('user_unban'))
					{
						global $phpbb_root_path, $phpEx;
						include($phpbb_root_path . 'includes/functions_user.' . $phpEx);
					}

					// user_unban() only takes ids, so other bans are never touched
					user_unban('ip', $ban_ids);
				}

				trigger_error($user->lang('SFSC_HP_AUTO_BANS_DELETED', count($ban_ids)) . adm_back_link($this->u_action));
			}
			else
			{
				confirm_box(false, $user->lang['SFSC_HP_DELETE_AUTO_BANS_CONFIRM'], build_hidden_fields(array('delete_auto_bans' => 1)));
			}
		}

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('sfscompanion_honeypot'))
			{
				trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$config->set('sfsc_hp_enabled', $request->variable('sfsc_hp_enabled', 0) ? 1 : 0);
			$config->set('sfsc_hp_check_register', $request->variable('sfsc_hp_check_register', 0) ? 1 : 0);
			$config->set('sfsc_hp_check_posting', $request->variable('sfsc_hp_check_posting', 0) ? 1 : 0);
			$config->set('sfsc_hp_check_pm', $request->variable('sfsc_hp_check_pm', 0) ? 1 : 0);
			$config->set('sfsc_hp_min_seconds', max(0, $request->variable('sfsc_hp_min_seconds', 3)));
			$config->set('sfsc_hp_notify_posts', max(0, $request->variable('sfsc_hp_notify_posts', 10)));
			$config->set('sfsc_hp_expire_days', max(0, $request->variable('sfsc_hp_expire_days', 30)));
			$config->set('sfsc_hp_ban_threshold', max(0, $request->variable('sfsc_hp_ban_threshold', 3)));
			$config->set('sfsc_hp_ban_window_hours', max(1, $request->variable('sfsc_hp_ban_window_hours', 24)));
			$config->set('sfsc_hp_ban_days', max(0, $request->variable('sfsc_hp_ban_days', 30)));
			$config->set('sfsc_hp_ban_match', min(2, max(0, $request->variable('sfsc_hp_ban_match', 0))));
			$config->set('sfsc_hp_ban_all_reasons', $request->variable('sfsc_hp_ban_all_reasons', 0) ? 1 : 0);

			trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
		}

		$template->assign_vars(array(
			'SFSC_HP_ENABLED'			=> (bool) $config['sfsc_hp_enabled'],
			'SFSC_HP_CHECK_REGISTER'	=> (bool) $config['sfsc_hp_check_register'],
			'SFSC_HP_CHECK_POSTING'		=> (bool) $config['sfsc_hp_check_posting'],
			'SFSC_HP_CHECK_PM'			=> (bool) $config['sfsc_hp_check_pm'],
			'SFSC_HP_MIN_SECONDS'		=> (int) $config['sfsc_hp_min_seconds'],
			'SFSC_HP_NOTIFY_POSTS'		=> (int) $config['sfsc_hp_notify_posts'],
			'SFSC_HP_EXPIRE_DAYS'		=> (int) $config['sfsc_hp_expire_days'],
			'SFSC_HP_BAN_THRESHOLD'		=> (int) $config['sfsc_hp_ban_threshold'],
			'SFSC_HP_BAN_WINDOW_HOURS'	=> (int) $config['sfsc_hp_ban_window_hours'],
			'SFSC_HP_BAN_DAYS'			=> (int) $config['sfsc_hp_ban_days'],
			'SFSC_HP_BAN_MATCH'			=> (int) $config['sfsc_hp_ban_match'],
			'SFSC_HP_BAN_ALL_REASONS'	=> (bool) $config['sfsc_hp_ban_all_reasons'],
			'SFSC_HP_AUTO_BAN_COUNT'	=> $this->count_auto_bans($auto_ban_where),
			'U_ACTION'					=> $this->u_action,
		));
	}

	/**
	* @param string $auto_ban_where SQL condition matching the automatic bans
	* @return int Number of active automatic honeypot bans
	*/
	private function count_auto_bans($auto_ban_where)
	{
		global $db;

		$sql = 'SELECT COUNT(ban_id) AS bans
			FROM ' . BANLIST_TABLE . '
			WHERE ' . $auto_ban_where . '
				AND (ban_end = 0 OR ban_end > ' . time() . ')';
		$result = $db->sql_query($sql);
		$bans = (int) $db->sql_fetchfield('bans');
		$db->sql_freeresult($result);

		return $bans;
	}

	/**
	* Honeypot trip log: browse, delete, and report individual trips to
	* Stop Forum Spam. Reporting is always a manual, confirmed action per
	* entry, so people who only tripped the time check by being quick are
	* never sent to a public database without a human look.
	*/
	private function honeypot_trips($sfs)
	{
		global $db, $template, $request, $user, $config, $phpbb_root_path, $phpEx, $phpbb_container, $table_prefix;

		$trips_table = $table_prefix . 'sfsc_honeypot_trips';

		$this->tpl_name = 'acp_sfs_honeypot_trips';
		$this->page_title = $user->lang('ACP_SFSC_HONEYPOT_TRIPS');

		$start		= $request->variable('start', 0);
		$action		= $request->variable('action', '');
		$trip_id	= $request->variable('trip_id', 0);
		$deletemark	= $request->variable('delmarked', false, false, \phpbb\request\request_interface::POST);
		$deleteall	= $request->variable('delall', false, false, \phpbb\request\request_interface::POST);
		$marked		= $request->variable('mark', array(0));
		$sort_days	= $request->variable('st', 0);
		$sort_key	= $request->variable('sk', 't');
		$sort_dir	= $request->variable('sd', 'd');
		$isearch	= $request->variable('isearch', '');

		$u_list = $this->u_action . '&amp;st=' . $sort_days . '&amp;sk=' . $sort_key . '&amp;sd=' . $sort_dir . '&amp;isearch=' . urlencode($isearch);

		if ($action === 'report' && $trip_id)
		{
			$this->report_trip($sfs, $trips_table, $trip_id, $u_list);
		}

		if ($deletemark || $deleteall)
		{
			// confirm_box() provides this action's CSRF protection, as on the other log pages
			if (confirm_box(true))
			{
				if ($deletemark && count($marked))
				{
					$db->sql_query('DELETE FROM ' . $trips_table . ' WHERE ' . $db->sql_in_set('trip_id', array_map('intval', $marked)));
				}
				else if ($deleteall)
				{
					$db->sql_query('DELETE FROM ' . $trips_table);
				}

				add_log('admin', 'LOG_SFSC_HP_TRIPS_CLEARED');
				redirect($this->u_action);
			}
			else
			{
				confirm_box(false, $user->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
					'delmarked'	=> $deletemark,
					'delall'	=> $deleteall,
					'mark'		=> $marked,
				)));
			}
		}

		$limit_days = array(0 => $user->lang['ALL_ENTRIES'], 1 => $user->lang['1_DAY'], 7 => $user->lang['7_DAYS'], 14 => $user->lang['2_WEEKS'], 30 => $user->lang['1_MONTH'], 90 => $user->lang['3_MONTHS'], 180 => $user->lang['6_MONTHS'], 365 => $user->lang['1_YEAR']);
		$sort_by_text = array('t' => $user->lang['SORT_DATE'], 'u' => $user->lang['SORT_USERNAME'], 'i' => $user->lang['SORT_IP']);
		$sort_by_sql = array('t' => 'trip_time', 'u' => 'username', 'i' => 'user_ip');

		if (!isset($sort_by_sql[$sort_key]))
		{
			$sort_key = 't';
		}

		$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
		gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);

		$sql_where = 'trip_time >= ' . (($sort_days) ? (time() - ($sort_days * 86400)) : 0);
		$sql_where .= ($isearch !== '') ? " AND user_ip = '" . $db->sql_escape($isearch) . "'" : '';

		$sql = 'SELECT COUNT(trip_id) AS total
			FROM ' . $trips_table . '
			WHERE ' . $sql_where;
		$result = $db->sql_query($sql);
		$total = (int) $db->sql_fetchfield('total');
		$db->sql_freeresult($result);

		$per_page = (int) $config['topics_per_page'];
		$pagination = $phpbb_container->get('pagination');
		$start = $pagination->validate_start($start, $per_page, $total);

		$sql = 'SELECT *
			FROM ' . $trips_table . '
			WHERE ' . $sql_where . '
			ORDER BY ' . $sort_by_sql[$sort_key] . ' ' . (($sort_dir === 'd') ? 'DESC' : 'ASC');
		$result = $db->sql_query_limit($sql, $per_page, $start);

		$can_report = !empty($config['sfs_api_key']);

		while ($row = $db->sql_fetchrow($result))
		{
			$has_details = ($row['username'] !== '' && $row['user_email'] !== '' && $row['user_ip'] !== '');

			$template->assign_block_vars('trip', array(
				'ID'			=> (int) $row['trip_id'],
				'DATE'			=> $user->format_date($row['trip_time']),
				'FORM'			=> $user->lang('SFSC_HP_FORM_' . strtoupper($row['trip_form'])),
				'REASON'		=> $user->lang('SFSC_HP_REASON_' . strtoupper($row['trip_reason'])),
				'SECONDS'		=> (int) $row['submit_seconds'],
				'USERNAME'		=> $row['username'],
				'U_USER'		=> ((int) $row['user_id'] !== ANONYMOUS) ? append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=viewprofile&amp;u=' . (int) $row['user_id']) : '',
				'EMAIL'			=> $row['user_email'],
				'IP'			=> $row['user_ip'],
				'U_IP'			=> ($row['user_ip'] !== '') ? $u_list . '&amp;isearch=' . urlencode($row['user_ip']) : '',
				'USER_AGENT'	=> $row['user_agent'],
				'SUBJECT'		=> $row['subject'],
				'EXCERPT'		=> $row['excerpt'],

				'S_REPORTED'	=> (bool) $row['sfs_reported'],
				'S_IP_BANNED'	=> (bool) $row['ip_banned'],
				'S_CAN_REPORT'	=> $can_report && $has_details && !$row['sfs_reported'],
				'S_MISSING'		=> !$has_details,
				'U_REPORT'		=> $u_list . '&amp;start=' . $start . '&amp;action=report&amp;trip_id=' . (int) $row['trip_id'],
			));
		}
		$db->sql_freeresult($result);

		$pagination->generate_template_pagination($this->u_action . "&amp;$u_sort_param&amp;isearch=" . urlencode($isearch), 'pagination', 'start', $total, $per_page, $start);

		$template->assign_vars(array(
			'U_ACTION'		=> $u_list . '&amp;start=' . $start,
			'S_LIMIT_DAYS'	=> $s_limit_days,
			'S_SORT_KEY'	=> $s_sort_key,
			'S_SORT_DIR'	=> $s_sort_dir,
			'ISEARCH'		=> $isearch,
			'S_NO_API_KEY'	=> !$can_report,
		));
	}

	/**
	* Report one honeypot trip to Stop Forum Spam after a confirmation, then
	* mark it so it can't be reported twice. Ends the request with a message.
	*/
	private function report_trip($sfs, $trips_table, $trip_id, $u_list)
	{
		global $db, $user, $config;

		$sql = 'SELECT *
			FROM ' . $trips_table . '
			WHERE trip_id = ' . (int) $trip_id;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		$back = '<br /><br />' . sprintf($user->lang['RETURN_PAGE'], '<a href="' . $u_list . '">', '</a>');

		if (!$row)
		{
			trigger_error($user->lang['SFSC_HP_TRIP_NOT_FOUND'] . $back, E_USER_WARNING);
		}

		if ($row['sfs_reported'])
		{
			trigger_error($user->lang['SFSC_HP_ALREADY_REPORTED'] . $back, E_USER_WARNING);
		}

		if ($row['username'] === '' || $row['user_email'] === '' || $row['user_ip'] === '' || empty($config['sfs_api_key']))
		{
			trigger_error($user->lang['SFSC_HP_CANNOT_REPORT'] . $back, E_USER_WARNING);
		}

		if (!confirm_box(true))
		{
			confirm_box(false, $user->lang('SFSC_HP_REPORT_CONFIRM', $row['username'], $row['user_email'], $row['user_ip']), build_hidden_fields(array(
				'action'	=> 'report',
				'trip_id'	=> (int) $trip_id,
			)));

			// Cancelled
			redirect(str_replace('&amp;', '&', $u_list));
		}

		if (!$this->send_trip_report($sfs, $trips_table, $row))
		{
			trigger_error($user->lang['SFSC_FAIL_ADD_DATA'] . $back, E_USER_WARNING);
		}

		trigger_error($user->lang('SFSC_HP_REPORTED_SUCCESS', $row['username']) . $back);
	}

	/**
	* Send a confirmed honeypot trip to Stop Forum Spam and mark it as reported.
	*
	* @param \phpbbmodders\sfscompanion\core\functions	$sfs
	* @param string	$trips_table
	* @param array	$row			the trip's table row
	* @return bool	true if Stop Forum Spam accepted the report
	*/
	private function send_trip_report($sfs, $trips_table, array $row)
	{
		global $db, $user;

		$evidence = $user->lang('SFSC_HP_EVIDENCE', $user->lang('SFSC_HP_REASON_' . strtoupper($row['trip_reason'])), $user->lang('SFSC_HP_FORM_' . strtoupper($row['trip_form'])));

		// Values are stored HTML-escaped by phpBB's request class
		if ($row['subject'] !== '' || $row['excerpt'] !== '')
		{
			$evidence .= "\n\n" . htmlspecialchars_decode(trim($row['subject'] . "\n" . $row['excerpt']), ENT_COMPAT);
		}

		if (!$sfs->report_to_sfs($row['username'], $row['user_ip'], $row['user_email'], $evidence))
		{
			return false;
		}

		$db->sql_query('UPDATE ' . $trips_table . ' SET sfs_reported = 1 WHERE trip_id = ' . (int) $row['trip_id']);

		add_log('admin', 'LOG_SFSC_HP_REPORTED', $row['username'], $row['user_ip']);

		return true;
	}

	/**
	* Assign a drop down's options to a template loop.
	*
	* @param string	$block		template loop name
	* @param array	$options	value => display text
	* @param mixed	$selected	the selected value
	*/
	private function assign_options($block, array $options, $selected)
	{
		global $template;

		foreach ($options as $value => $text)
		{
			$template->assign_block_vars($block, array(
				'VALUE'			=> $value,
				'TEXT'			=> $text,
				'S_SELECTED'	=> ((string) $value === (string) $selected),
			));
		}
	}
}
