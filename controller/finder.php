<?php
/**
*
* @package phpBB Extension - SFS Companion
* @copyright (c) 2026 phpBB Modders
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbmodders\sfscompanion\controller;

use phpbbmodders\sfscompanion\core\functions as sfs_functions;

class finder
{
	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var sfs_functions */
	protected $sfs;

	public function __construct(
		\phpbb\template\template $template,
		\phpbb\request\request_interface $request,
		\phpbb\controller\helper $helper,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		sfs_functions $sfs
	)
	{
		$this->template	= $template;
		$this->request	= $request;
		$this->helper	= $helper;
		$this->user		= $user;
		$this->auth		= $auth;
		$this->sfs		= $sfs;
	}

	public function main()
	{
		if (!($this->auth->acl_get('a_') || $this->auth->acl_get('m_chk_sfs')))
		{
			trigger_error($this->user->lang['NOT_AUTHORISED']);
		}

		$id = $this->request->variable('u', 0);
		$url = $this->helper->route('phpbbmodders_sfscompanion_finder', array('u' => $id));

		// full_check() renders its own header/body/footer and terminates via
		// exit_handler() - matching the original, which behaves the same way.
		// Nothing after this call ever runs.
		page_header();
		$this->sfs->full_check($id, $url);
	}
}
