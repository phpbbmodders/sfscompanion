<?php
/**
*
* @package phpBB Extension - SFS Companion
* @copyright (c) 2026 phpBB Modders
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbmodders\sfs_companion\acp;

class main_info
{
	function module()
	{
		return array(
			'filename'	=> '\phpbbmodders\sfs_companion\acp\main_module',
			'title'		=> 'ACP_SFS_COMPANION',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'settings'	=> array(
					'title'	=> 'ACP_SFS_SETTINGS',
					'auth'	=> 'ext_phpbbmodders/sfs_companion && acl_a_board',
					'cat'	=> array('ACP_SFS_COMPANION'),
				),
				'blocks'	=> array(
					'title'	=> 'ACP_SFS_BLOCKS',
					'auth'	=> 'ext_phpbbmodders/sfs_companion && acl_a_board',
					'cat'	=> array('ACP_SFS_COMPANION'),
				),
				'errors'	=> array(
					'title'	=> 'ACP_SFS_ERRORS',
					'auth'	=> 'ext_phpbbmodders/sfs_companion && acl_a_board',
					'cat'	=> array('ACP_SFS_COMPANION'),
				),
				'scan'		=> array(
					'title'	=> 'ACP_SFS_SCAN',
					'auth'	=> 'ext_phpbbmodders/sfs_companion && acl_a_board && acl_a_user',
					'cat'	=> array('ACP_SFS_COMPANION'),
				),
			),
		);
	}
}
