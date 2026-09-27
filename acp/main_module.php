<?php
/**
 *
 * SFS Companion extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\sfscompanion\acp;

class main_module
{
	public $u_action;
	public $tpl_name;
	public $page_title;

	function main($id, $mode)
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
		global $db, $template, $request, $user, $config;

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
		// rmcgirr83/stopforumspam's own sfsapi service - the same service
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
				$sql_where .= (!$isearch) ? '' : ' AND log_ip = \'' . $db->sql_escape($isearch) . '\'';

				if ($deletemark && sizeof($marked))
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
		$start = view_log($log_mode, $log_data, $log_count, $config['topics_per_page'], $start, 0, 0, 0, $log_time, $sql_sort);

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
	* (6 users/page), just delegated to rmcgirr83/stopforumspam's own
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
		$per_page = 6;

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

		$sort_key_text = array('a' => $user->lang['SORT_JOINED'], 'b' => $user->lang['SORT_USERNAME'], 'c' => $user->lang['SORT_IP'], 'd' => $user->lang['SORT_POST'], 'e' => $user->lang['SORT_EMAIL'], 'l' => $user->lang['LAST_VISIT']);
		$sort_key_sql = array('a' => 'user_regdate', 'b' => 'username_clean', 'c' => 'user_ip', 'd' => 'user_posts', 'e' => 'user_email', 'l' => 'user_lastvisit');
		$sort_dir_text = array('a' => $user->lang['ASCENDING'], 'd' => $user->lang['DESCENDING']);

		if (!isset($sort_key_sql[$sort_key]))
		{
			$sort_key = $default_key;
		}

		$s_sort_key = '';
		foreach ($sort_key_text as $key => $value)
		{
			$s_sort_key .= '<option value="' . $key . '"' . (($sort_key === $key) ? ' selected="selected"' : '') . '>' . $value . '</option>';
		}

		$s_sort_dir = '';
		foreach ($sort_dir_text as $key => $value)
		{
			$s_sort_dir .= '<option value="' . $key . '"' . (($sort_dir === $key) ? ' selected="selected"' : '') . '>' . $value . '</option>';
		}

		$s_filter_key = '';
		foreach ($filter_options as $key => $value)
		{
			$s_filter_key .= '<option value="' . $key . '"' . (($filter_key == $key) ? ' selected="selected"' : '') . '>' . $value . '</option>';
		}

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
				if (sizeof($users))
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

					$msg = sprintf($user->lang['SUCSESS_DELETE_COUNT'], $deleted_count);

					if ($report_failed_count)
					{
						$msg .= '<br />' . sprintf($user->lang['FAIL_ADD_DATA_COUNT'], $report_failed_count);
					}

					if ($backup_failed_count)
					{
						$msg .= '<br />' . sprintf($user->lang['FAIL_BACKUP_COUNT'], $backup_failed_count);
					}
				}
				else
				{
					$msg = $user->lang['NONE_SELECTED'];
				}

				meta_refresh(3, $pagination_url);
				trigger_error($msg . '<br /><br />' . sprintf($user->lang['RETURN_PAGE'], '<a href="' . $pagination_url . '">', '</a>'));
			}
			else
			{
				if (empty($users))
				{
					$msg = $user->lang['NONE_SELECTED'];
					meta_refresh(3, $pagination_url);
					trigger_error($msg . '<br /><br />' . sprintf($user->lang['RETURN_PAGE'], '<a href="' . $pagination_url . '">', '</a>'), E_USER_WARNING);
				}

				confirm_box(false, $user->lang['CONFIRM_DELETE'], build_hidden_fields(array(
					'id_list'	=> $users,
					'delmarked'	=> $delmarked,
				)));
			}

			return;
		}

		$current_time = time();
		$day = 86400;
		$periods = array(0 => $day, 1 => $day * 7, 2 => $day * 30, 3 => $day * 365, 4 => 0);
		$labels = array(0 => 'PER_DAY', 1 => 'PER_WEEK', 2 => 'PER_MONTH', 3 => 'PER_YEAR', 4 => 'PER_ALL_TIME');

		$action = isset($periods[$action]) ? $action : 0;
		$period = $periods[$action] ? ($current_time - $periods[$action]) : 0;

		$s_period = '';
		foreach ($labels as $key => $lang_key)
		{
			$s_period .= '<option value="' . $key . '"' . (($action == $key) ? ' selected="selected"' : '') . '>' . $user->lang[$lang_key] . '</option>';
		}
		$template->assign_var('S_PERIOD_SELECT', $s_period);

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
			else if (sizeof($check))
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
				'USER_IP'			=> (!empty($row['user_ip'])) ? htmlspecialchars($row['user_ip']) : $user->lang['READ_COMMENT'],
				'U_POSTS'			=> append_sid("{$phpbb_root_path}search.$phpEx", 'author_id=' . (int) $row['user_id'] . '&sr=posts'),
				'S_USER_IP'			=> (!empty($row['user_ip'])) ? $this->u_action . '&amp;whois=true&amp;ip=' . $row['user_ip'] : '',
				'U_FULL_CHECK'		=> $this->u_action . '&amp;full_check=true&amp;ch_user=' . (int) $row['user_id'],
				'S_FAIL_CHK'		=> $fail_chk,
			));
		}
		$db->sql_freeresult($result);

		$pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $total_users, $per_page, $start);

		$template->assign_vars(array(
			'S_MODE_SELECT'		=> $s_sort_key,
			'S_ORDER_SELECT'	=> $s_sort_dir,
			'FILTER'			=> $filter,
			'FILTER_OPTIONS'	=> $s_filter_key,
			'NOPOSTS'			=> (bool) $no_post,
			'UNACTIVE'			=> (bool) $s_inactive,
			'TOTAL_USERS'		=> ($total_users) ? $user->lang('LIST_USERS', $total_users) : '',
			'EXEC_TIME'			=> sprintf($user->lang['EXEC_TIME'], round($sfs->getmicrotime() - $time_start, 4)),
			'U_ACTION'			=> $pagination_url,
		));
	}
}
