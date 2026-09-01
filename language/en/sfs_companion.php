<?php
/**
*
* @package phpBB Extension - SFS Companion
* @copyright (c) 2026 phpBB Modders
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'ACL_M_CHK_SFS'					=> 'Can check users via SFS database',

	'ACP_SFS_COMPANION'				=> 'SFS Companion',
	'ACP_SFS_SETTINGS'					=> 'Settings',
	'ACP_SFS_SETTINGS_EXPLAIN'			=> 'Companion tools for rmcgirr83/stopforumspam: a browsable log of what it has already blocked, and a manual lookup/removal tool for suspected spammers.',
	'ACP_SFS_BLOCKS'					=> 'Spam blocks',
	'ACP_SFS_ERRORS'					=> 'SFS errors',
	'ACP_SFS_SCAN'						=> 'Scan users',
	'ACP_SFS_SCAN_EXPLAIN'				=> 'Lists recently-registered users and checks each one against StopForumSpam. You can report and remove confirmed spammers directly from this page.',

	'SFSC_EXPIRE_DAYS'					=> 'Prune log entries after',
	'SFSC_EXPIRE_DAYS_EXPLAIN'			=> 'Automatically delete SFS block/error log entries older than this many days. 0 disables pruning.',
	'DAYS'								=> 'days',

	'SORT_BY'							=> 'Sort by',
	'SEARCH_IP'							=> 'Search by IP',
	'LIMIT_DAYS'						=> 'Limit to',
	'GO'								=> 'Go',
	'USERNAME'							=> 'Username',
	'IP'								=> 'IP',
	'DATE'								=> 'Date',
	'ACTION'							=> 'Action',
	'NO_ENTRIES'						=> 'No matching log entries.',
	'DELETE_MARKED'						=> 'Delete marked',
	'DELETE_ALL'						=> 'Delete all',

	'ALL_ENTRIES'						=> 'All entries',
	'1_DAY'								=> '1 day',
	'7_DAYS'							=> '7 days',
	'2_WEEKS'							=> '2 weeks',
	'1_MONTH'							=> '1 month',
	'3_MONTHS'							=> '3 months',
	'6_MONTHS'							=> '6 months',
	'1_YEAR'							=> '1 year',

	'SORT_DATE'							=> 'Date',
	'SORT_USERNAME'						=> 'Username',
	'SORT_IP'							=> 'IP address',
	'SORT_POST'							=> 'Posts',
	'SORT_EMAIL'						=> 'Email address',
	'SORT_JOINED'						=> 'Joined',
	'LAST_VISIT'						=> 'Last visit',
	'NEVER'								=> 'Never',
	'ASCENDING'							=> 'Ascending',
	'DESCENDING'						=> 'Descending',
	'REGISTERED'						=> 'Registered',

	'SEARCH_OPTION'						=> 'Search by',
	'SELECT_SORT'						=> 'Sort by',
	'NO_POSTS_ONLY'						=> 'Only users with no posts',
	'UNACTIVE'							=> 'Only inactive users',
	'USER_NAME'							=> 'Username',
	'USER_EMAIL'						=> 'Email address',
	'FULL_CHECK'						=> 'Full check',
	'NOT_FIND'							=> 'No users found matching this period and these conditions.',
	'DELETE_SELECTED'					=> 'Report and delete selected',
	'LIST_USERS'						=> 'Users: %s',
	'EXEC_TIME'							=> 'Scan took %s seconds',

	'SFS'								=> 'Check via StopForumSpam',
	'RESUME'							=> 'Summary',
	'SFS_INFO'							=> 'StopForumSpam report',
	'NOT_SPAMMER'						=> 'This does not look like a spammer.',
	'SPAMMER'							=> 'This is very likely a spammer.',
	'POSSIBLE_YES'						=> 'This may be a spammer.',
	'IP_FIND'							=> 'IP address on record %d time(s).',
	'IP_NOT_FIND'						=> 'No record of this IP address.',
	'EMAIL_FIND'						=> 'Email address on record %d time(s).',
	'EMAIL_NOT_FIND'					=> 'No record of this email address.',
	'NICK_FIND'							=> 'Username on record %d time(s).',
	'NICK_NOT_FIND'						=> 'No record of this username.',
	'CONNECTION_ERROR'					=> 'Could not reach StopForumSpam right now. Try again shortly.',

	'ADD_DATA'							=> 'Report to StopForumSpam',
	'ADD_AND_DELETE'					=> 'Report to StopForumSpam and delete this user',
	'FAIL_ADD_DATA'						=> 'Failed to report this user to StopForumSpam - check the API key configured for rmcgirr83/stopforumspam.',
	'CONFIRM_DELETE'					=> 'Report this user to StopForumSpam and permanently delete their account? This cannot be undone.',
	'SUCSESS_DELETE'					=> 'User reported and deleted. A backup of their account row was saved to store/sfs_companion/.',
	'WARNING_MESSAGE'					=> 'This action is irreversible. A backup of each deleted user\'s row is saved to store/sfs_companion/ first.',

	'WHOIS'								=> 'WHOIS',

	'LOG_CLEAR_SFS_BLOCKS'				=> '<strong>Cleared SFS block log</strong>',
	'LOG_CLEAR_SFS_ERRORS'				=> '<strong>Cleared SFS error log</strong>',
	'LOG_CLEAR_SFS_LOGS'				=> '<strong>Pruned old SFS log entries</strong>',
));
