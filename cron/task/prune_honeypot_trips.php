<?php
/**
 *
 * SFS Companion extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\sfscompanion\cron\task;

/**
* Deletes honeypot trip log entries older than the configured number of days.
* The entries hold email and IP addresses of people who were never members,
* so they shouldn't be kept longer than needed.
*/
class prune_honeypot_trips extends \phpbb\cron\task\base
{
	/** @var int How often we run the cron (in seconds). */
	protected $cron_frequency = 86400;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var string */
	protected $trips_table;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, $trips_table)
	{
		$this->config = $config;
		$this->db = $db;
		$this->trips_table = $trips_table;
	}

	public function run()
	{
		$cutoff = time() - ((int) $this->config['sfsc_hp_expire_days'] * 86400);

		$sql = 'DELETE FROM ' . $this->trips_table . '
			WHERE trip_time < ' . (int) $cutoff;
		$this->db->sql_query($sql);

		$this->config->set('sfsc_hp_last_prune', time(), false);
	}

	public function is_runnable()
	{
		return (bool) $this->config['sfsc_hp_expire_days'];
	}

	public function should_run()
	{
		return $this->config['sfsc_hp_last_prune'] < time() - $this->cron_frequency;
	}
}
