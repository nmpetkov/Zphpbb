<?php
define('INZIK_TYPE', 'ifrm'); // integrated with iframes (ztools used and Util.php)
//define('INZIK_TYPE', 'embd'); // embeded (all Zikula classes are available)

if (INZIK_TYPE == 'embd') {
    define('PHPBB_ROOT_PATH', 'modules/Zphpbb/vendor/phpbb/');
    include_once 'modules/Zphpbb/lib/Zphpbb/Util.php';
}
if (INZIK_TYPE == 'ifrm') {
    require_once __DIR__.'/../../lib/ztools.php';
    Ztools::IncludeConfigFile();
}

global $phpbb_root_path, $phpEx, $lang, $db, $template, $cache, $config, $user, $auth, $phpbb_hook;

// Check if to redirect to Full Zikula module page (only in case iframed)
if (INZIK_TYPE == 'ifrm') {
    //$_SERVER['HTTP_REFERER'] // like http://www.xyz.com/index.php
    //$_SERVER['SERVER_NAME'] // like www.abc.com
    if (strlen($_SERVER['HTTP_REFERER']) > 3 && strlen($_SERVER['SERVER_NAME']) > 3 
        && strpos($_SERVER['HTTP_REFERER'], str_replace("www.", "", $_SERVER['SERVER_NAME'])) === false) {
        if (strpos($_SERVER['REQUEST_URI'], 'modules/Zphpbb/vendor/phpbb/') !== false) {
            // page request from outside, if not "staandard" Zikula url - reformat and redirect
            //$_SERVER['REQUEST_URI'] like /modules/Zphpbb/vendor/phpbb/viewforum.php?f=2
            //$_SERVER['SERVER_PROTOCOL'] like HTTP/1.1
            $protocol = 'http' .($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != 'off' ? 's' : ''). '://';
            $url = $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
            //print_r(zphpbb_url($url, true));echo '<br />';
            header('Location: ' . zphpbb_url($url, true));
        }
    }
}

/**
* Zphpbb function to modify url
*/
function zphpbb_url($url, $makeFull = false, $is_amp = false)
{
    $aUrl = parse_url($url);
    $file = $aUrl['path'];
    $dir = '';
    $pos = strrpos ($file, '/');
    if ($pos !== false) {
        $dir = substr($file, 0, $pos+1);
        $file = substr($file, $pos+1);
    }
    $type = 'user';
    if (strpos($dir, 'adm/') !== false) {
        $type = 'admin';
    }
    $func = '';
    $pos = strrpos ($file, '.php');
    if ($pos !== false) {
        $func = substr($file, 0, $pos);
    }
    if (!empty($func)) {
        // ignored files from main dir: common, config, style
        $aUserEntries = array('index', 'viewforum', 'viewtopic', 'posting', 'ucp', 'search', 'mcp', 'memberlist', 'viewonline', 'faq', 'cron', 'feed', 'report');
        if (($type == 'admin' && ($func == 'index')) || ($type == 'user' && in_array($func, $aUserEntries))) {
            // Build Zikula module url
            if (class_exists('ModUtil')) {
                $aParams = explode(($is_amp ? '&amp;' : '&'), $aUrl['query']);
                $aPararg = array();
                foreach ($aParams as $param) {
                    $apr = explode('=', $param);
                    $aPararg[$apr[0]] = $apr[1];
                }
                $modurl = ModUtil::url('Zphpbb', $type, $func, $aPararg);
            } else {
                $modurl = Ztools::ZikulaModuleUrl('Zphpbb', $type, $func);
                if ($aUrl['query']) {
                    $modurl .= '&' . $aUrl['query'];
                }
            }
            if ($makeFull) {
                // Make full url
                // Array ( [scheme] => http [host] => www.xyz.com [path] => /modules/Zphpbb/vendor/phpbb/viewforum.php [query] => f=2&topic=3 )
                if (empty($aUrl['scheme'])) {
                    $aUrl['scheme'] = 'http' . ($_SERVER['HTTPS'] ? 's' : '');
                }
                if (empty($aUrl['host'])) {
                    $aUrl['host'] = $_SERVER['HTTP_HOST'];
                }
                //$baseuri = System::getBaseUri();
                $baseuri = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
                // remove phpbb part
                $pos = strpos($baseuri, 'modules/Zphpbb/vendor/phpbb');
                if ($pos !== false) {
                    $baseuri = substr($baseuri, 0, $pos);
                }
                $modurl = $aUrl['scheme'] . '://' . $aUrl['host'] . $baseuri . $modurl;
            }

            if ($is_amp) {
                $modurl = htmlspecialchars($modurl);
            }
            return $modurl;
        }
    }

    return $url;
}

/**
* Zphpbb function to build Zikula module url
*/
function zikula_url($modname, $type, $func, $args = array(), $ssl = null, $fragment = null, $fqurl = null)
{
    if (!isset($fqurl)) {
        $fqurl = (INZIK_TYPE == 'ifrm');
    }

    if (class_exists('ModUtil')) {
        $url = ModUtil::url($modname, $type, $func, $args, $ssl, $fragment, $fqurl);
    } else {
        $url = Ztools::ZikulaModuleUrl($modname, $type, $func, $args, $ssl, $fragment, $fqurl);
    }
    // remove unnesessery part...
    $url = str_replace('modules/Zphpbb/vendor/phpbb/', '', $url);

    return $url;
}

/**
* Zphpbb site root url
*/
function zikula_siteurl($is_amp = false)
{
    $url = Ztools::ZikulaSiteRootUrl();

    // remove unnesessery part...
    $url = str_replace('modules/Zphpbb/vendor/phpbb/', '', $url);

    if ($is_amp) {
        $url = htmlspecialchars($url);
    }

    return $url;
}

/**
* Zphpbb function to return url for Zikula login page
*/
function zikula_login($args = array(), $is_amp = false)
{
    if (!is_array($args)) {
        $args = array('returnpage' => urlencode(zikula_url('Zphpbb', 'user', 'main')));
    }
    $url = zikula_url('Users', 'user', 'login', $args, null, null, true);
    if ($is_amp) {
        $url = htmlspecialchars($url);
    }

    return $url;
}

/**
* Zphpbb function to return url for Zikula registeration page
*/
function zikula_register($args = array(), $is_amp = false)
{
    $url = zikula_url('Users', 'user', 'register', $args, null, null, true);
    if ($is_amp) {
        $url = htmlspecialchars($url);
    }

    return $url;
}
function zikula_profile($args = array(), $is_amp = false)
{
    $url = zikula_url('Users', 'user', 'main', $args, null, null, true);
    if ($is_amp) {
        $url = htmlspecialchars($url);
    }

    return $url;
}
function zikula_chnageemail($args = array(), $is_amp = false)
{
    $url = zikula_url('Users', 'user', 'changeEmail', $args, null, null, true);
    if ($is_amp) {
        $url = htmlspecialchars($url);
    }

    return $url;
}
function zikula_chnagepassword($args = array(), $is_amp = false)
{
    $url = zikula_url('Users', 'user', 'changePassword', $args, null, null, true);
    if ($is_amp) {
        $url = htmlspecialchars($url);
    }

    return $url;
}

/**
* Zphpbb functionS update Zikula user profile
*/
function zikula_updateProfile($user_id, $aData = array())
{
    $userData = Ztools::ZikulaUserData($user_id);
    $userAttrib = Ztools::ZikulaUserAttrib($user_id);
    
    foreach ($aData as $key => $value) {
        if ($key == 'user_icq') {
            Ztools::ZikulaUserAttribUpdate($user_id, array('icq' => $value));
        } elseif ($key == 'user_aim') {
            Ztools::ZikulaUserAttribUpdate($user_id, array('aim' => $value));
        } elseif ($key == 'user_msnm') {
            Ztools::ZikulaUserAttribUpdate($user_id, array('msnm' => $value));
        } elseif ($key == 'user_yim') {
            Ztools::ZikulaUserAttribUpdate($user_id, array('yim' => $value));
        } elseif ($key == 'user_jabber') {
        } elseif ($key == 'user_website') {
            Ztools::ZikulaUserAttribUpdate($user_id, array('url' => $value));
        } elseif ($key == 'user_from') {
            Ztools::ZikulaUserAttribUpdate($user_id, array('city' => $value));
        } elseif ($key == 'user_interests') {
            Ztools::ZikulaUserAttribUpdate($user_id, array('interests' => $value));
        } elseif ($key == 'user_occ') {
            Ztools::ZikulaUserAttribUpdate($user_id, array('occupation' => $value));
        } elseif ($key == 'user_notify_type') {
        } elseif ($key == 'user_birthday') {
        } elseif ($key == 'user_sig') {
            Ztools::ZikulaUserAttribUpdate($user_id, array('signature' => $value));
        } elseif (false) {
            // no files to update for now in users table
            Ztools::ZikulaUserDataUpdate($user_id, array($key => $value));
        }
    }

    return true;
}

/**
* Zphpbb module variable
*/
function zikula_zphpbb_getvar($varname)
{
    return Ztools::ZikulaModuleVar('Zphpbb', $varname);
}
