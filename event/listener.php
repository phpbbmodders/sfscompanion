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

namespace phpbbmodders\sfscompanion\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	protected $phpbb_root_path;
	protected $php_ext;

	/** SFS log_operation values this extension manages, and which log_type each lives under */
	const SFS_BLOCK_OPS = array('LOG_SFS_MESSAGE');
	const SFS_ERROR_OPS = array('LOG_SFS_DOWN', 'LOG_SFS_DOWN_USER_ALLOWED', 'LOG_SFS_CURL_ERROR', 'LOG_SFS_NEED_CURL');

	public function __construct(
		\phpbb\controller\helper $helper,
		\phpbb\template\template $template,
		\phpbb\auth\auth $auth,
		\phpbb\db\driver\driver_interface $db,
		$phpbb_root_path,
		$php_ext
	)
	{
		$this->helper			= $helper;
		$this->template			= $template;
		$this->auth				= $auth;
		$this->db				= $db;
		$this->phpbb_root_path	= $phpbb_root_path;
		$this->php_ext			= $php_ext;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'					=> 'load_language_on_setup',
			'core.permissions'					=> 'add_permission',
			'core.memberlist_view_profile'		=> 'chk_profile',
			'core.acp_users_overview_before'	=> 'users_overview',
			'core.get_logs_modify_type'			=> 'filter_sfs_logs',
		);
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'phpbbmodders/sfscompanion',
			'lang_set' => 'sfscompanion',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function add_permission($event)
	{
		$permissions = $event['permissions'];
		$permissions['m_chk_sfs'] = array('lang' => 'ACL_M_CHK_SFS', 'cat' => 'misc');
		$event['permissions'] = $permissions;
	}

	/**
	* Same permission check the controller itself enforces - kept in sync
	* so the link is never shown to someone the controller would reject.
	*/
	public function chk_profile($event)
	{
		if (!($this->auth->acl_get('a_') || $this->auth->acl_get('m_chk_sfs')))
		{
			return;
		}

		$member = $event['member'];

		$this->template->assign_vars(array(
			'U_CHK_SFS'	=> $this->helper->route('phpbbmodders_sfscompanion_finder', array('u' => $member['user_id'])),
		));
	}

	public function users_overview($event)
	{
		if (!($this->auth->acl_get('a_') || $this->auth->acl_get('m_chk_sfs')))
		{
			return;
		}

		$user_id = $event['user_row']['user_id'];

		$this->template->assign_vars(array(
			'U_CHK_SFS'	=> $this->helper->route('phpbbmodders_sfscompanion_finder', array('u' => $user_id)),
		));
	}

	/** @var array|null log_operation values the next view_log() call is limited to, or null for no limit */
	protected $log_filter_ops = null;

	/** @var string IP search for the next view_log() call */
	protected $log_filter_ip = '';

	/**
	* Limit phpBB's own view_log() to this extension's SFS log entries.
	*
	* Called by this extension's ACP log pages just before view_log() and
	* reset just after, so the filter can't leak into phpBB's own Logs page
	* or any other extension's log view, however the page was reached.
	*
	* @param array|null	$ops	log_operation values to show, or null to stop filtering
	* @param string		$ip		IP search; '*' is a wildcard
	*/
	public function set_log_filter($ops, $ip = '')
	{
		$this->log_filter_ops = $ops;
		$this->log_filter_ip = $ip;
	}

	/**
	* @param string $ip IP search; '*' is a wildcard
	* @return string SQL condition matching l.log_ip, shared by listing and deleting
	*/
	public function ip_search_sql($ip, $column = 'l.log_ip')
	{
		return $column . ' ' . $this->db->sql_like_expression(str_replace('*', $this->db->get_any_char(), $ip));
	}

	public function filter_sfs_logs($event)
	{
		if ($this->log_filter_ops === null)
		{
			return;
		}

		$sql_additional = $event['sql_additional'] . ' AND ' . $this->db->sql_in_set('l.log_operation', $this->log_filter_ops);

		if ($this->log_filter_ip !== '')
		{
			$sql_additional .= ' AND ' . $this->ip_search_sql($this->log_filter_ip);
		}

		$event['sql_additional'] = $sql_additional;
	}
}
