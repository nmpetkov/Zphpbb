<?php
global $dbms, $dbhost, $dbport, $dbname, $dbuser, $dbpasswd, $table_prefix, $acm_type, $load_extensions;
if (defined('INZIK_TYPE') && INZIK_TYPE == 'embd') {
    $table_prefix = ModUtil::getVar ('Zphpbb', 'table_prefix', 'phpbb_');
} else {
    require_once __DIR__.'/../../lib/ztools.php';
    Ztools::IncludeConfigFile();
    $vars = Ztools::ZikulaModuleVars('Zphpbb');
    $table_prefix = $vars['table_prefix'];
}
$dbms = strtolower($GLOBALS['ZConfig']['DBInfo']['databases']['default']['dbdriver']);
if ($dbms == 'mysql') {
    $dbms == 'mysqli';
} elseif ($dbms == 'pgsql') {
    $dbms = 'postgres';
}
$dbhost = $GLOBALS['ZConfig']['DBInfo']['databases']['default']['host'];
$dbport = '';
$dbname = $GLOBALS['ZConfig']['DBInfo']['databases']['default']['dbname'];
$dbuser = $GLOBALS['ZConfig']['DBInfo']['databases']['default']['user'];
$dbpasswd = $GLOBALS['ZConfig']['DBInfo']['databases']['default']['password'];
$acm_type = 'file';
$load_extensions = '';

@define('PHPBB_INSTALLED', true);
// @define('DEBUG', true);
// @define('DEBUG_EXTRA', true);
