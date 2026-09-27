<?php
/**
*
* @package phpBB Extension - SFS Companion
* @copyright (c) 2026 phpBB Modders
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbmodders\sfscompanion\migrations;

class install_1_0_0 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['sfsc_version']);
	}

	static public function depends_on()
	{
		return array(
			'\phpbb\db\migration\data\v330\v330',
			'\rmcgirr83\stopforumspam\migrations\version_149',
		);
	}

	public function update_schema()
	{
		return array();
	}

	public function revert_schema()
	{
		return array();
	}

	public function update_data()
	{
		return array(
			array('config.add', array('sfsc_version', '1.0.0')),
			array('config.add', array('sfsc_expire_days', 0)),
			array('config.add', array('sfsc_last_prune', 0)),
			array('permission.add', array('m_chk_sfs')),
		);
	}
}
