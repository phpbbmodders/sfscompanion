<?php
/**
*
* @package phpBB Extension - SFS Companion
* @copyright (c) 2026 phpBB Modders
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbmodders\sfs_companion\cron\task;

class prune_logs extends \phpbb\cron\task\base
{
	/** @var int How often we run the cron (in seconds). */
	protected $cron_frequency = 86400;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\log\log_interface */
	protected $phpbb_log;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\user $user,
		\phpbb\log\log_interface $phpbb_log
	)
	{
		$this->config = $config;
		$this->db = $db;
		$this->user = $user;
		$this->phpbb_log = $phpbb_log;
	}

	public function run()
	{
		if (!$this->config['sfsc_expire_days'])
		{
			return;
		}

		$diff = time() - ((int) $this->config['sfsc_expire_days'] * 86400);
		$ops = array('LOG_SFS_MESSAGE', 'LOG_SFS_DOWN', 'LOG_SFS_DOWN_USER_ALLOWED');

		$sql = 'DELETE FROM ' . LOG_TABLE . '
			WHERE log_time < ' . (int) $diff . '
				AND ' . $this->db->sql_in_set('log_operation', $ops);
		$this->db->sql_query($sql);

		$user_id = (empty($this->user->data)) ? ANONYMOUS : $this->user->data['user_id'];
		$user_ip = (empty($this->user->ip)) ? '' : $this->user->ip;
		$this->phpbb_log->add('admin', $user_id, $user_ip, 'LOG_CLEAR_SFS_LOGS', time(), false);

		$this->config->set('sfsc_last_prune', time(), true);
	}

	public function is_runnable()
	{
		return (bool) $this->config['sfsc_expire_days'];
	}

	public function should_run()
	{
		return $this->config['sfsc_last_prune'] < time() - $this->cron_frequency;
	}
}
