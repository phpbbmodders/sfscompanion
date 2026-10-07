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
	'ACP_SFS_SETTINGS_EXPLAIN'			=> 'Companion tools for phpbbmodders/stopforumspam: a browsable log of what it has already blocked, and a manual lookup/removal tool for suspected spammers.',
	'ACP_SFS_BLOCKS'					=> 'Spam blocks',
	'ACP_SFS_ERRORS'					=> 'SFS errors',
	'ACP_SFS_SCAN'						=> 'Scan users',
	'ACP_SFS_SCAN_EXPLAIN'				=> 'Lists recently-registered users and checks each one against StopForumSpam. You can report and remove confirmed spammers directly from this page.',

	'SFSC_EXPIRE_DAYS'					=> 'Prune log entries after',
	'SFSC_EXPIRE_DAYS_EXPLAIN'			=> 'Automatically delete SFS block/error log entries older than this many days. 0 disables pruning.',

	'SFSC_SEARCH_IP'							=> 'Search by IP',
	'SFSC_LIMIT_DAYS'						=> 'Limit to',
	'SFSC_DATE'								=> 'Date',


	'SFSC_SORT_POST'							=> 'Posts',
	'SFSC_SORT_EMAIL'						=> 'Email address',
	'SFSC_REGISTERED'						=> 'Registered',

	'SFSC_PER_DAY'							=> 'Today',
	'SFSC_PER_WEEK'							=> 'This week',
	'SFSC_PER_MONTH'							=> 'This month',
	'SFSC_PER_YEAR'							=> 'This year',
	'SFSC_PER_ALL_TIME'						=> 'All time',

	'SFSC_SEARCH_OPTION'						=> 'Search by',
	'SFSC_SELECT_SORT'						=> 'Sort by',
	'SFSC_NO_POSTS_ONLY'						=> 'Only users with no posts',
	'SFSC_INACTIVE_ONLY'							=> 'Only inactive users',
	'SFSC_USER_NAME'							=> 'Username',
	'SFSC_USER_EMAIL'						=> 'Email address',
	'SFSC_FULL_CHECK'						=> 'Full check',
	'SFSC_NOT_FIND'							=> 'No users found matching this period and these conditions.',
	'SFSC_DELETE_SELECTED'					=> 'Report and delete selected',
	'SFSC_LIST_USERS'						=> 'Users: %s',
	'SFSC_EXEC_TIME'							=> 'Scan took %s seconds',
	'SFSC_STATUS'							=> 'Status',
	'SFSC_CHECK_FAILED'					=> 'Check failed',
	'SFSC_NONE_SELECTED'						=> 'No users were selected.',
	'SFSC_READ_COMMENT'						=> 'No IP recorded',

	'SFSC_CHECK_SFS'								=> 'Check via StopForumSpam',
	'SFSC_RESUME'							=> 'Summary',
	'SFSC_INFO'							=> 'StopForumSpam report',
	'SFSC_NOT_SPAMMER'						=> 'This does not look like a spammer.',
	'SFSC_SPAMMER'							=> 'This is very likely a spammer.',
	'SFSC_SPAM_REASON'					=> 'Spam',
	'SFSC_POSSIBLE_YES'						=> 'This may be a spammer.',
	'SFSC_IP_FIND'							=> 'IP address on record %d time(s).',
	'SFSC_IP_NOT_FIND'						=> 'No record of this IP address.',
	'SFSC_EMAIL_FIND'						=> 'Email address on record %d time(s).',
	'SFSC_EMAIL_NOT_FIND'					=> 'No record of this email address.',
	'SFSC_NICK_FIND'							=> 'Username on record %d time(s).',
	'SFSC_NICK_NOT_FIND'						=> 'No record of this username.',
	'SFSC_CONNECTION_ERROR'					=> 'Could not reach StopForumSpam right now. Try again shortly.',

	'SFSC_ADD_DATA'							=> 'Report to StopForumSpam',
	'SFSC_ADD_AND_DELETE'					=> 'Report to StopForumSpam and delete this user',
	'SFSC_FAIL_ADD_DATA'						=> 'Failed to report this user to StopForumSpam - check the API key configured for phpbbmodders/stopforumspam.',
	'SFSC_FAIL_ADD_DATA_COUNT'				=> '%d user(s) were deleted but could not be reported to StopForumSpam - check the API key configured for phpbbmodders/stopforumspam.',
	'SFSC_FAIL_BACKUP'						=> 'Could not save a backup of this account before deletion - the account was NOT deleted. Check that store/sfscompanion/ is writable.',
	'SFSC_FAIL_BACKUP_COUNT'					=> '%d user(s) were left untouched because a backup could not be saved - check that store/sfscompanion/ is writable.',
	'SFSC_CONFIRM_DELETE'					=> 'Report this user to StopForumSpam and permanently delete their account? This cannot be undone.',
	'SFSC_REPORTED_DONE'					=> 'User reported to StopForumSpam.',
	'SFSC_SUCCESS_DELETE'					=> 'User reported and deleted. A backup of their account row was saved to store/sfscompanion/.',
	'SFSC_SUCCESS_DELETE_COUNT'				=> 'Reported and deleted %d user(s). A backup of each account row was saved to store/sfscompanion/.',
	'SFSC_WARNING_MESSAGE'					=> 'This action is irreversible. A backup of each deleted user\'s row, without their password, is saved to store/sfscompanion/ first.',


	'LOG_CLEAR_SFS_BLOCKS'				=> '<strong>Cleared SFS block log</strong>',
	'LOG_CLEAR_SFS_ERRORS'				=> '<strong>Cleared SFS error log</strong>',
	'LOG_CLEAR_SFS_LOGS'				=> '<strong>Pruned old SFS log entries</strong>',

	'ACP_SFSC_HONEYPOT'					=> 'Honeypot',
	'ACP_SFSC_HONEYPOT_EXPLAIN'			=> 'Hidden fields real users never see or fill in, plus a minimum time between showing a form and submitting it. Scripted bots often fail one or the other. Registrations that trip a check are rejected; on posts and private messages the account is quietly moved into a hidden group that can’t post or reply. Every trip is logged under Honeypot trips.',
	'ACP_SFSC_HONEYPOT_TRIPS'			=> 'Honeypot trips',
	'ACP_SFSC_HONEYPOT_TRIPS_EXPLAIN'	=> 'Everyone who tripped the honeypot. Report a trip to Stop Forum Spam only when you are sure it is a spammer: people who are just quick can trip the time check.',

	'SFSC_HP_ENABLED'					=> 'Enable honeypot',
	'SFSC_HP_ENABLED_EXPLAIN'			=> 'Turns every honeypot check, the trip log and automatic bans on or off. Members already in the restricted group stay restricted while it is off.',
	'SFSC_HP_CHECK_REGISTER'			=> 'Check registrations',
	'SFSC_HP_CHECK_POSTING'				=> 'Check posts',
	'SFSC_HP_CHECK_PM'					=> 'Check private messages',
	'SFSC_HP_MIN_SECONDS'				=> 'Minimum time to submit',
	'SFSC_HP_MIN_SECONDS_EXPLAIN'		=> 'Submissions faster than this count as a trip. Uses phpBB’s own signed form timestamp. 0 disables this check.',
	'SFSC_HP_NOTIFY_POSTS'				=> 'Notify staff from',
	'SFSC_HP_NOTIFY_POSTS_EXPLAIN'		=> 'When an account with at least this many posts trips the honeypot on a post or private message, staff are notified, since it may be a compromised account or a false positive.',
	'SFSC_HP_EXPIRE_DAYS'				=> 'Delete trips after',
	'SFSC_HP_EXPIRE_DAYS_EXPLAIN'		=> 'Trips include email and IP addresses, so they are deleted automatically after this many days. 0 keeps them.',
	'SFSC_HP_POSTS'						=> 'posts',
	'SFSC_HP_SECONDS'					=> 'seconds',

	'SFSC_HP_ERROR'						=> 'Your submission could not be processed. Please try again.',
	'SFSC_HP_DECOY_WEBSITE'				=> 'Website',
	'SFSC_HP_DECOY_PHONE'				=> 'Phone',

	'SFSC_HP_TRIP'						=> 'Trip',
	'SFSC_HP_EMAIL'						=> 'Email address',
	'SFSC_HP_MESSAGE'					=> 'Message',
	'SFSC_HP_SFS'						=> 'Stop Forum Spam',
	'SFSC_HP_USER_AGENT'				=> 'Browser',
	'SFSC_HP_SUBMITTED_IN'				=> 'Submitted in',
	'SFSC_HP_FORM_REGISTER'				=> 'Registration',
	'SFSC_HP_FORM_POSTING'				=> 'Post',
	'SFSC_HP_FORM_PM'					=> 'Private message',
	'SFSC_HP_REASON_FIELD'				=> 'Hidden field filled in',
	'SFSC_HP_REASON_TIME'				=> 'Submitted too quickly',
	'SFSC_HP_REPORT'					=> 'Report',
	'SFSC_HP_REPORTED'					=> 'Reported',
	'SFSC_HP_MISSING_DETAILS'			=> 'Needs username, email and IP',
	'SFSC_HP_NO_API_KEY'				=> 'Reporting needs a Stop Forum Spam API key, set in the Stop Forum Spam extension’s settings.',
	'SFSC_HP_REPORT_CONFIRM'			=> 'Report %1$s (%2$s, %3$s) to Stop Forum Spam? This adds them to a public database.',
	'SFSC_HP_REPORTED_SUCCESS'			=> '%s was reported to Stop Forum Spam.',
	'SFSC_HP_ALREADY_REPORTED'			=> 'This trip has already been reported.',
	'SFSC_HP_CANNOT_REPORT'				=> 'This trip can’t be reported: it needs a username, email address and IP address, and a Stop Forum Spam API key must be set.',
	'SFSC_HP_TRIP_NOT_FOUND'			=> 'The trip no longer exists.',
	'SFSC_HP_EVIDENCE'					=> 'Honeypot: %1$s on %2$s.',
	'LOG_SFSC_HP_REPORTED'				=> '<strong>Reported honeypot trip to Stop Forum Spam</strong><br />» %1$s (%2$s)',
	'LOG_SFSC_HP_TRIPS_CLEARED'			=> '<strong>Deleted honeypot trips</strong>',

	'SFSC_HP_AUTO_BAN'					=> 'Automatic IP bans',
	'SFSC_HP_AUTO_BAN_EXPLAIN'			=> 'Ban an IP address once it keeps tripping the honeypot. The ban’s reason is “SFS Companion honeypot auto-ban” in the ACP ban list, and banned visitors are shown no reason. Administrators and moderators are never banned this way.',
	'SFSC_HP_BAN_THRESHOLD'				=> 'Ban after',
	'SFSC_HP_BAN_THRESHOLD_EXPLAIN'		=> 'Number of matching trips. 0 turns automatic bans off.',
	'SFSC_HP_TRIPS'						=> 'trips',
	'SFSC_HP_BAN_WINDOW'				=> 'Within',
	'SFSC_HP_HOURS'						=> 'hours',
	'SFSC_HP_BAN_DAYS'					=> 'Ban length',
	'SFSC_HP_BAN_DAYS_EXPLAIN'			=> '0 bans permanently, until the ban is deleted.',
	'SFSC_HP_BAN_MATCH'					=> 'Count trips with',
	'SFSC_HP_BAN_MATCH_IP'				=> 'The same IP address',
	'SFSC_HP_BAN_MATCH_IP_EMAIL'		=> 'The same IP address and email address',
	'SFSC_HP_BAN_MATCH_ANY'				=> 'The same IP address, email address or username',
	'SFSC_HP_BAN_COUNTS'				=> 'Trips that count',
	'SFSC_HP_BAN_COUNTS_FIELD'			=> 'Hidden field filled in only',
	'SFSC_HP_BAN_COUNTS_ALL'			=> 'All trips, including submitted too quickly',
	'SFSC_HP_DELETE_AUTO_BANS'			=> 'Delete automatic bans',
	'SFSC_HP_DELETE_AUTO_BANS_EXPLAIN'	=> 'Removes every ban made by the honeypot. Bans added any other way are left alone.',
	'SFSC_HP_DELETE_AUTO_BANS_CONFIRM'	=> 'Delete every automatic honeypot IP ban? Other bans are not affected.',
	'SFSC_HP_AUTO_BAN_COUNT'			=> 'Active automatic bans',
	'SFSC_HP_AUTO_BANS_DELETED'			=> array(
		0	=> 'There were no automatic bans to delete.',
		1	=> 'Deleted %d automatic ban.',
		2	=> 'Deleted %d automatic bans.',
	),
	'SFSC_HP_IP_BANNED'					=> 'IP banned',

	'SFSC_NOTIFICATION_TYPE_HP_RESTRICTED'	=> 'A member tripped the honeypot and was restricted',
	'SFSC_NOTIFICATION_HP_RESTRICTED'		=> '<strong>%1$s</strong> (%2$d posts) tripped the honeypot and was restricted. This may be a compromised account or a false positive.',
));
