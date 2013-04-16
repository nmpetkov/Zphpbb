<?php
// declare main phpbb items to be used from Zphpbb
global $phpbb_root_path, $phpEx, $lang, $db, $cache, $config, $template, $auth, $user; // important to be visible in phpbb functions!
define('IN_PHPBB', true);
define('PHPBB_ROOT_PATH', 'modules/Zphpbb/vendor/phpbb/');
$phpbb_root_path = PHPBB_ROOT_PATH;
$phpEx = substr(strrchr(__FILE__, '.'), 1);
