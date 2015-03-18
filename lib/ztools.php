<?php
/**
 * Zikula Tools - to use if Zikula is not loaded
 *
 * @copyright Nikolay Petkov
 * @license GNU/GPL
 */

/**
 * Ztools
 */
class Ztools
{
    /**
     * Zikula ZConfig.
     *
     * @var array
     */
    public static $ZConfig = array();

    /**
     * Connection link to database.
     *
     * @var MySQL link identifier 
     */
    public static $dblink;

    /**
     * Name of database.
     *
     * @var string
     */
    public static $dbname;

    /**
     * Array with data from Zikula session table.
     *
     * @var array
     */
    public static $sessiondata;
    
    /**
     * Array with data from Zikula users table.
     *
     * @var array
     */
    public static $userdata;
    public static $userattrib;

    /**
     * Module vars.
     *
     * @var array
     */
    public static $modvars = array();

    /**
     * Return location and file name for Zikula config.php.
     *
     * @param integer  $maxdeeplevel      Max deep level for calling root
     *
     * @return path and file name if found, empty string otherwise
     */
    public static function getConfigFile($maxdeeplevel = 8)
    {
        $configfile = '';
        $updirprefix = '';
        for ($i = 1; $i <= $maxdeeplevel; $i++) {
            $configfile = __DIR__.$updirprefix.'/config/config.php';
            if (file_exists($configfile)) {
                $configfile = realpath($configfile);
                break;
            }
            $updirprefix .= '/..';;
        }

        return $configfile;
    }

    /**
     * Include Zikula config.php.
     *
     * @param integer  $maxdeeplevel      Max deep level for calling root
     *
     * @return boolean true or false
     */
    public static function IncludeConfigFile($maxdeeplevel = 8)
    {
        $success = false;
        $configfile = self::getConfigFile($maxdeeplevel);
        if (!empty($configfile)) {
            // here if we include once config file, then have to make checking also self::$ZConfig = $ZConfig to accrue once!
            require $configfile;
            self::$ZConfig = $ZConfig;
            /*require_once $configfile;
            if (!isset(self::$ZConfig['DBInfo'])) {
                self::$ZConfig = $ZConfig;
            }*/
            $success = true;
        }

        return $success;
    }

    /**
     * Open a connection to Zikula MySQL.
     *
     * @return MySQL link identifier or false
     */
    public static function mysqlConnect()
    {
        self::$dbname = self::$ZConfig['DBInfo']['databases']['default']['dbname'];
        self::$dblink = mysqli_connect(self::$ZConfig['DBInfo']['databases']['default']['host'], self::$ZConfig['DBInfo']['databases']['default']['user'], self::$ZConfig['DBInfo']['databases']['default']['password']);
        if (self::$dblink) {
            if (!mysqli_select_db(self::$dblink, self::$dbname)) {
                echo 'Can not select database';
            }
        } else {
            echo 'Can not connect to MySql server. Please check PHP version to find if supports mysqli_* functions.';
        }

        return self::$dblink;
    }

    /**
     * Include Zikula config.php and open a connection to Zikula MySQL.
     *
     * @return MySQL link identifier or false
     */
    public static function ConfigMysqlConnect()
    {
        $configisok = self::IncludeConfigFile();
        if ($configisok) {
            self::mysqlConnect();
        }

        return self::$dblink;
    }

    /**
     * Execute a MySQL query.
     *
     * @param string  $sql
     *
     * @return MySQL resource or boolean
     */
    public static function MysqlQuery($sql)
    {
        if (!self::$dblink) {
            self::mysqlConnect();
        }
        if (self::$dblink) {
            $rSet = mysqli_query(self::$dblink, $sql) or die("Bad query: ".$sql);
        }

        return $rSet;
    }

    /**
     * Execute a MySQL query and return result (1 row only) as associative array.
     *
     * @param string  $sql
     *
     * @return array
     */
    public static function MysqlQueryFetchArray($sql)
    {
        $rSet = self::MysqlQuery($sql);
        if ($rSet) {
            return mysqli_fetch_array($rSet, MYSQLI_ASSOC);
        }

        return false;
    }

    /**
     * Zikula module variables.
     *
     * @param string  $modname
     *
     * @return array with Zikula module variables
     */
    public static function ZikulaModuleVars($modname)
    {
        $sql = 'SELECT * FROM `module_vars` WHERE `modname`="'.$modname.'"';
        $rSet = self::MysqlQuery($sql);
        $vars = array();
        while ($var = mysqli_fetch_array($rSet)){
            $vars[$var['name']] = unserialize($var['value']);
        }

        return $vars;
    }


    /**
     * Zikula module variable.
     *
     * @param string  $modname
     * @param string  $varname
     *
     * @return array with Zikula module variables
     */
    public static function ZikulaModuleVar($modname, $varname)
    {
        if (array_key_exists($modname, self::$modvars)) {
            // variables are cached, use them
        } else {
            self::ZikulaModuleVars($modname);
        }
        $var = self::$modvars[$modname][$varname];

        return $var;
    }

    /**
     * Zikula module url.
     *
     * @param string  $modname
     * @param string  $type
     * @param string  $func
     * @param string  $args
     * @param string  $fragment The framgment to target within the URL.
     * @param boolean|null $fqurl Fully Qualified URL. True to get full URL, eg for Redirect, else gets root-relative path unless SSL.
     *
     * @return string url or false
     */
    public static function ZikulaModuleUrl($modname, $type, $func, $args = array(), $ssl = null, $fragment = null, $fqurl = null)
    {
        if (empty($modname)) {
            return false;
        }
        $modinfo = self::ZikulaModuleInfo($modname);
        if ($modinfo) {
            if ($modinfo['url']) {
                $modname = $modinfo['url'];
            } else {
                $modname = strtolower($modname);
            }
        }
        // Main url
        // $entrypoint = System::getVar('entrypoint');
        $entrypoint = 'index.php';
        if (empty($type)) {
            $type = 'user';
        }
        $urlargs = "module=".$modname."&type=".$type."&func=".$func;
        $url = $entrypoint.'?'.$urlargs;
        // Parameters
        if (is_array($args)) {
            foreach ($args as $key => $value) {
                $url .= '&'.$key.'='.$value;
            }
        }
        if (isset($fragment)) {
            $url .= '#' . $fragment;
        }
        // Only produce full URL when HTTPS is on or $ssl is set (from Zikula ModUtil class)
        $siteRoot = '';
        $https = $_SERVER['HTTPS'];
        if ((isset($https) && $https == 'on') || $ssl != null || $fqurl == true) {
            $siteRoot = self::ZikulaSiteRootUrl($ssl);
        }

        return $siteRoot . $url;
    }

    /**
     * Zikula site root url.
     *
     * @return string url or false
     */
    public static function ZikulaSiteRootUrl($ssl = null)
    {
        $https = $_SERVER['HTTPS'];
        $protocol = 'http' . (($https == 'on' && $ssl !== false) || $ssl === true ? 's' : '');
        $host = $_SERVER['HTTP_HOST'];
        $baseuri = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
        return $protocol . '://' . $host . $baseuri . '/';
    }

    /**
     * Zikula module info.
     *
     * @param string  $modname
     *
     * @return array with Zikula module info, or false
     */
    public static function ZikulaModuleInfo($modname)
    {
        $modinfo = false;

        $sql = 'SELECT * FROM `modules` WHERE LOWER(`name`)="'.strtolower($modname).'"';
        $rSet = self::MysqlQuery($sql);
        if ($rSet) {
            $modinfo = mysqli_fetch_assoc($rSet);
        }

        return $modinfo;
    }

    /**
     * Zikula session info.
     *
     * @param string  $sessid
     *
     * @return array with data from Zikula session table
     */
    public static function ZikulaSessionInfo($sessid)
    {
        $sql = 'SELECT * FROM `session_info` WHERE `sessid`="'.$sessid.'"';
        self::$sessiondata = self::MysqlQueryFetchArray($sql);

        return self::$sessiondata;
    }

    /**
     * Zikula session user Id.
     *
     * @param string  $sessid
     *
     * @return integer User Id from Zikula session table
     */
    public static function ZikulaSessionUserid($sessid)
    {
        $userid = 0;
        if (!is_array(self::$sessiondata)) {
            self::ZikulaSessionInfo($sessid);
        }
        if (is_array(self::$sessiondata)) {
            $userid = self::$sessiondata['uid'];
        }

        return $userid;
    }

    /**
     * Zikula session(s) destroy by user Id. This logs out the specified user
     *
     * @param string  $userid
     *
     * @return boolean
     */
    public static function ZikulaSessionDestroy($userid)
    {
        $sql = 'DELETE FROM `session_info` WHERE `uid`='.$userid;

        return self::MysqlQuery($sql);
    }

    /**
     * Zikula user data.
     *
     * @param integer  $userid
     * @param integer  $username Valid if $userid === false
     *
     * @return array with data from Zikula users table
     */
    public static function ZikulaUserData($userid, $username = false)
    {
        if ($userid === false ) {
            $sql = 'SELECT * FROM `users` WHERE LOWER(`uname`)="'.strtolower($username).'"';
        } else {
            $sql = 'SELECT * FROM `users` WHERE `uid`="'.$userid.'"';
        }
        self::$userdata = self::MysqlQueryFetchArray($sql);

        return self::$userdata;
    }
    public static function ZikulaUserDataUpdate($userid, $userdata = array())
    {
        foreach ($userdata as $key => $value) {
            $sql = "UPDATE `users` SET ".$key."='".$value."' WHERE `uid`=".$userid;
        }

        return self::MysqlQuery($sql);
    }

    /**
     * Zikula user dynamic data (attributes).
     *
     * @param integer  $userid
     *
     * @return array with data from Zikula users table
     */
    public static function ZikulaUserAttrib($userid)
    {
        $sql = "SELECT `attribute_name`, `value` FROM `objectdata_attributes` WHERE `object_type`='users' AND `object_id`=".$userid;

        $rSet = self::MysqlQuery($sql);
        self::$userattrib = array();
        while ($row = mysqli_fetch_assoc($rSet)){
            self::$userattrib[$row['attribute_name']] = $row['value'];
        }

        return self::$userattrib;
    }
    public static function ZikulaUserAttribUpdate($userid, $userdata = array())
    {
        foreach ($userdata as $key => $value) {
            $sql = "UPDATE `objectdata_attributes` SET `value`='".$value."' WHERE `attribute_name`='".$key."' AND `object_type`='users' AND `object_id`=".$userid;
        }

        return self::MysqlQuery($sql);
    }

    /**
     * Zikula group Ids for given user.
     *
     * @param string  $userid
     *
     * @return array Array with group Id's to which user belongs
     */
    public static function ZikulaUserGroupids($userid)
    {
        $usergroupids = array();
        if ($userid > 0) {
            $sql = 'SELECT * FROM `group_membership` WHERE `uid`='.$userid;
            $rSet = self::MysqlQuery($sql);
            while ($r = mysqli_fetch_object($rSet)){
                $usergroupids[] = $r->gid;
            }
        }

        return $usergroupids;
    }

    /**
     * Zikula groups returned as array
     *
     * @param string  $userid
     *
     * @return array Array with groups data
     */
    public static function ZikulaUserGroups()
    {
        $sql = 'SELECT * FROM `groups` ORDER BY `gid` ASC';
        $rSet = self::MysqlQuery($sql);
        $groups = array();
        while ($groupdata = mysqli_fetch_array($rSet)){
            $groups[] = $groupdata;
        }

        return $groups;
    }

    /**
     * Zikula user admin status
     *
     * @param string  $userid
     * @param string  $groupslist    Group, or list of groups to check
     *
     * @return boolean 
     */
    public static function ZikulaUserIsAdmin($userid, $groupadminslist = '2')
    {
        if ($userid > 0) {
            if (empty($groupadminslist)) {
                // 2 is default Zikula admin group Id
                $groupadminslist = '2';
            }
            return self::ZikulaUserIsInGroup($userid, $groupadminslist);
        }

        return false;
    }

    /**
     * Zikula user group belonging
     *
     * @param string  $userid
     * @param string  $groupslist    Group, or list of groups to check
     *
     * @return boolean 
     */
    public static function ZikulaUserIsInGroup($userid, $groupslist = '')
    {
        if ($userid > 0 and $groupslist) {

            // get groups for the given user
            $usergroupids = self::ZikulaUserGroupids($userid);
            // chech to see if at least one of user group ids is in the given list of group ids
            $arrayids = explode(",", $groupslist);
            foreach ($usergroupids as $usergroupid) {
                if (in_array($usergroupid, $arrayids)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Zikula active users login data.
     *
     * @param string  $daysago How many days ago usera have been active
     *
     * @return MySql resource set with users login data from Zikula users table
     */
    public static function ZikulaActiveUsersLoginData($daysago = 0)
    {
        $sql = "SELECT `uid`, `uname`, `pass`, `lastlogin` FROM `users` 
            WHERE `activated` AND `lastlogin`<>'' AND `lastlogin`<>'0000-00-00 00:00:00' AND `lastlogin`<>'1970-01-01 00:00:00'";
        if ($daysago > 0) {
            $sql .= " AND DATEDIFF(CURDATE(), `lastlogin`) < ".$daysago;
        }
        $sql .= " ORDER BY `lastlogin` DESC";

        return self::MysqlQuery($sql);
    }

    /**
     * Based on Zikula SecurityUtil class
     */
    public static function passwordsMatch($unhashedPassword, $hashedPassword)
    {
        return self::checkSaltedHash($unhashedPassword, $hashedPassword);
    }

    /**
     * Based on Zikula SecurityUtil class
     */
    public static function checkSaltedHash($unhashedData, $saltedHash, array $hashMethodCodeToName = array(1 => 'md5', 5 => 'sha1', 8 => 'sha256'), $saltDelimeter = '$')
    {
        $dataMatches = false;

        $algoList = hash_algos();

        if (is_string($unhashedData) && is_string($saltedHash) && is_string($saltDelimeter) && (strlen($saltDelimeter) == 1)
                && (strpos($saltedHash, $saltDelimeter) !== false)) {
            list ($hashMethod, $saltStr, $correctHash) = explode($saltDelimeter, $saltedHash);
            if (!empty($hashMethodCodeToName)) {
                if (is_numeric($hashMethod) && ((int)$hashMethod == $hashMethod)) {
                    $hashMethod = (int)$hashMethod;
                }
                if (isset($hashMethodCodeToName[$hashMethod])) {
                    $hashMethodName = $hashMethodCodeToName[$hashMethod];
                } else {
                    $hashMethodName = $hashMethod;
                }
            } else {
                $hashMethodName = $hashMethod;
            }

            if (array_search($hashMethodName, $algoList) !== false) {
                $dataHash = hash($hashMethodName, $saltStr . $unhashedData);
                $dataMatches = is_string($dataHash) ? (int)($dataHash == $correctHash) : false;
            }
        }

        return $dataMatches;
    }

}
