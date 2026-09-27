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

class main_info
{
	function module()
	{
		return array(
			'filename'	=> '\phpbbmodders\sfscompanion\acp\main_module',
			'title'		=> 'ACP_SFS_COMPANION',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'settings'	=> array(
					'title'	=> 'ACP_SFS_SETTINGS',
					'auth'	=> 'ext_phpbbmodders/sfscompanion && acl_a_board',
					'cat'	=> array('ACP_SFS_COMPANION'),
				),
				'blocks'	=> array(
					'title'	=> 'ACP_SFS_BLOCKS',
					'auth'	=> 'ext_phpbbmodders/sfscompanion && acl_a_board',
					'cat'	=> array('ACP_SFS_COMPANION'),
				),
				'errors'	=> array(
					'title'	=> 'ACP_SFS_ERRORS',
					'auth'	=> 'ext_phpbbmodders/sfscompanion && acl_a_board',
					'cat'	=> array('ACP_SFS_COMPANION'),
				),
				'scan'		=> array(
					'title'	=> 'ACP_SFS_SCAN',
					'auth'	=> 'ext_phpbbmodders/sfscompanion && acl_a_board && acl_a_user',
					'cat'	=> array('ACP_SFS_COMPANION'),
				),
			),
		);
	}
}
