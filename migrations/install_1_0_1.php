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

namespace phpbbmodders\sfscompanion\migrations;

/**
* Registers the ACP category and modules install_1_0_0 was missing. Kept as
* its own migration - rather than folded back into install_1_0_0 - so boards
* that already ran the original migration still get the modules on upgrade.
*/
class install_1_0_1 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['sfsc_version']) && version_compare($this->config['sfsc_version'], '1.0.1', '>=');
	}

	static public function depends_on()
	{
		return array('\phpbbmodders\sfscompanion\migrations\install_1_0_0');
	}

	public function update_data()
	{
		return array(
			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_SFS_COMPANION',
			)),
			array('module.add', array(
				'acp',
				'ACP_SFS_COMPANION',
				array(
					'module_basename'	=> '\phpbbmodders\sfscompanion\acp\main_module',
					'modes'				=> array('settings', 'blocks', 'errors', 'scan'),
				),
			)),
			array('config.update', array('sfsc_version', '1.0.1')),
		);
	}
}
