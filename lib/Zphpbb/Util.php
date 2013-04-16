<?php
/**
 * Zphpbb Zikula Module
 *
 * @copyright Nikolay Petkov
 * @license GNU/GPL
 */
class Zphpbb_Util
{
    /**
     * Loads phpBB board config table in array
     * @return boolean
     */
    public static function getBoardConfig()
    {
        $dom = ZLanguage::getModuleDomain('Zphpbb');

        $connection = Doctrine_Manager::getInstance()->getCurrentConnection();
        $stmt = $connection->prepare("SELECT * FROM " . self::getTablePrefix() . 'config');
        $stmt->execute();
        $data = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);

        $board_config = array();
        if ($data) {
            foreach ($data as $row) {
                $board_config[$row['config_name']] = $row['config_value'];
            }
        } else {
            return LogUtil::registerError(__('Error: Could not obtain config information.', $dom));
        }

        return $board_config;
    }

    /**
     * Update phpBB user account from Zikula account by given Id
     * @return boolean
     */
    public static function phpBBupdateAccountById($user_id)
    {
        $dom = ZLanguage::getModuleDomain('Zphpbb');

        $userObj = UserUtil::getVars($user_id);

        if ($userObj) {
            $class = 'Zphpbb_Listener_UsersSynch';
            if (!class_exists($class)) {
                include_once 'modules/Zphpbb/lib/Zphpbb/Listener/UsersSynch.php';
            }

            return Zphpbb_Listener_UsersSynch::updateAccount($userObj);
        } else {
            return LogUtil::registerError(__('Error: Could not obtain user information, Id '.$user_id, $dom));
        }
    }

    /**
     * Update phpBB user account from Zikula account by given Id
     * @return boolean
     */
    public static function phpBBupdateAllAccounts($newInstall = false)
    {
        $dom = ZLanguage::getModuleDomain('Zphpbb');

        $class = 'Zphpbb_Listener_UsersSynch';
        if (!class_exists($class)) {
            include_once 'modules/Zphpbb/lib/Zphpbb/Listener/UsersSynch.php';
        }

        $success = true;

        $connection = Doctrine_Manager::getInstance()->getCurrentConnection();

        if ($newInstall) {
            // Delete users, except for  Anonymous = 1, and Admin = 2
            $stmt = $connection->prepare("DELETE FROM " . self::getTablePrefix() . "users WHERE user_id<>1 AND user_id<>2");
            $stmt->execute();
            $stmt = $connection->prepare("DELETE FROM " . self::getTablePrefix() . "user_group WHERE 1");
            $stmt->execute();
            // insert group GUESTS for anonimous user 
            $stmt = $connection->prepare("SELECT group_id FROM " . self::getTablePrefix() . "groups WHERE group_name='GUESTS'");
            $stmt->execute();
            $data = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
            if (isset($data[0]['group_id'])) {
                $stmt = $connection->prepare("INSERT INTO " . self::getTablePrefix() . "user_group (group_id, user_id, group_leader, user_pending) VALUES ('".$data[0]['group_id']."', '1', '0', '0')");
                $stmt->execute();
            }
            // insert group users for administrator 
            $stmt = $connection->prepare("SELECT group_id FROM " . self::getTablePrefix() . "groups WHERE group_name='users'");
            $stmt->execute();
            $data = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
            if (isset($data[0]['group_id'])) {
                $stmt = $connection->prepare("INSERT INTO " . self::getTablePrefix() . "user_group (group_id, user_id, group_leader, user_pending) VALUES ('".$data[0]['group_id']."', '2', '0', '0')");
                $stmt->execute();
            }
            // insert group REGISTERED for administrator 
            $stmt = $connection->prepare("SELECT group_id FROM " . self::getTablePrefix() . "groups WHERE group_name='REGISTERED'");
            $stmt->execute();
            $data = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
            if (isset($data[0]['group_id'])) {
                $stmt = $connection->prepare("INSERT INTO " . self::getTablePrefix() . "user_group (group_id, user_id, group_leader, user_pending) VALUES ('".$data[0]['group_id']."', '2', '0', '0')");
                $stmt->execute();
            }
            // insert group GLOBAL_MODERATORS for administrator 
            $stmt = $connection->prepare("SELECT group_id FROM " . self::getTablePrefix() . "groups WHERE group_name='GLOBAL_MODERATORS'");
            $stmt->execute();
            $data = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
            if (isset($data[0]['group_id'])) {
                $stmt = $connection->prepare("INSERT INTO " . self::getTablePrefix() . "user_group (group_id, user_id, group_leader, user_pending) VALUES ('".$data[0]['group_id']."', '2', '0', '0')");
                $stmt->execute();
            }
            // insert group ADMINISTRATORS for administrator 
            $stmt = $connection->prepare("SELECT group_id FROM " . self::getTablePrefix() . "groups WHERE group_name='ADMINISTRATORS'");
            $stmt->execute();
            $data = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
            if (isset($data[0]['group_id'])) {
                $stmt = $connection->prepare("INSERT INTO " . self::getTablePrefix() . "user_group (group_id, user_id, group_leader, user_pending) VALUES ('".$data[0]['group_id']."', '2', '0', '0')");
                $stmt->execute();
            }
        }
        
        // Get usernames for Anonymous = 1, and Admin = 2
        $stmt = $connection->prepare("SELECT username FROM " . self::getTablePrefix() . "users WHERE user_id=1 OR user_id=2");
        $stmt->execute();
        $result = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
        $sysUsers = array();
        foreach ($result as $row) {
            $sysUsers[] = strtolower($row['username']);
        }

        $users = UserUtil::getUsers();

        foreach ($users as $user) {
            $userObj = UserUtil::getVars($user['uid']);

            if ($user['activated'] > 0) {
                if (in_array(strtolower($user['uname']), $sysUsers)) {
                    LogUtil::registerStatus(__('Skipped system user, Id '.$user['uid'].', '.$user['uname'], $dom));
                } else {
                    if ($userObj) {
                        if (!Zphpbb_Listener_UsersSynch::updateAccount($userObj)) {
                            $success = false;
                        }
                    } else {
                        LogUtil::registerError(__('Error: Could not obtain user information, Id '.$user['uid'], $dom));
                        $success = false;
                    }
                }
            }
        }

        return $success;
    }

    /**
     * Return array of phpBB tables
     * @return array
     */
    public static function getTableNames()
    {
        return array('acl_groups', 'acl_options', 'acl_roles', 'acl_roles_data', 'acl_users', 'attachments', 'banlist', 'bbcodes', 'bookmarks', 'bots', 'config', 'confirm', 'disallow', 'drafts', 'extensions', 'extension_groups', 'forums', 'forums_access', 'forums_track', 'forums_watch', 'groups', 'icons', 'lang', 'log', 'login_attempts', 'moderator_cache', 'modules', 'poll_options', 'poll_votes', 'posts', 'privmsgs', 'privmsgs_folder', 'privmsgs_rules', 'privmsgs_to', 'profile_fields', 'profile_fields_data', 'profile_fields_lang', 'profile_lang', 'ranks', 'reports', 'reports_reasons', 'search_results', 'search_wordlist', 'search_wordmatch', 'sessions', 'sessions_keys', 'sitelist', 'smilies', 'styles', 'styles_imageset', 'styles_imageset_data', 'styles_template', 'styles_template_data', 'styles_theme', 'topics', 'topics_posted', 'topics_track', 'topics_watch', 'users', 'user_group', 'warnings', 'words', 'zebra');
    }

    /**
     * Return table name witout prefix
     * @return array
     */
    public static function getTableStem($table_name, $table_prefix)
    {
        $table_stem = $table_name;
        $pos = strpos($table_name, $table_prefix);
        if ($pos !== false) {
            $len = strlen($table_prefix);
            $table_stem = substr($table_name, $pos+$len);
        }

        return $table_stem;
    }   

    public static function getTablePrefix()
    {
        return ModUtil::getVar ('Zphpbb', 'table_prefix', 'phpbb_');
    }

    /**
     * phpBB session handler
     *
     * @param integer $user_id User Id to handle
     *
     * @return array
     */
    public static function phpbbSessionHandler($user_id = false)
    {
        if ($user_id === false) {
            $user_id = SessionUtil::getVar('uid');
        }
        // info: $user_id = 1 is ANONYMOUS in phpBB, in Zikula is Guest
        if (empty($user_id)) {
            $zUserdata = array();
            $zUser_isLoggedIn = false;
        } else {
            $zUserdata = UserUtil::getVars($user_id);
            $zUser_isLoggedIn = true;
        }

        // declare main phpbb items to be used from Zphpbb
        include_once 'modules/Zphpbb/vendor/phpbb_include.php';
        include_once $phpbb_root_path . 'common.' . $phpEx;

        // Start session management
        $user->session_begin();
        // here in $user->data['user_id'] we have: 1 = anonimous, >1 is registered
        // let's check if our user Id differs from phpBB one
        $autoregister_in_phpbb = false;
        if ($user_id <= 1 && $user->data['user_id'] > 1) {
            // we are anonimous, they are registered, so log them out, and nothing more
            $new_session = true;   // to create anonimous session
            $user->session_kill($new_session); // this in practice is logout
        } elseif ($user_id <= 1 && $user->data['user_id'] <= 1) {
            // we are anonimous, they are anonimous too, nothing to do
        } elseif ($user_id > 1 && $user->data['user_id'] <= 1) {
            // we are registered, they are anonimous, have to auto-register them
            $autoregister_in_phpbb = true;
        } elseif ($user_id > 1 && $user->data['user_id'] > 1) {
            // we are registered, they are registered to
            if ($user_id == $user->data['user_id']) {
                // same registered user, nothing to do more
            } else {
                // different registered user, log them out, then auto-register them
                $new_session = true;
                $user->session_kill($new_session);
                $autoregister_in_phpbb = true;
            }
        }
        if ($autoregister_in_phpbb) {
            // auto login the user in phpBB, here they have anonimous session

            // First check if user exists in phpBB database
            // It have to be already created by listener handler, but to be sure, and prevention against missed Zikula events
            $connection = Doctrine_Manager::getInstance()->getCurrentConnection();
            $stmt = $connection->prepare("SELECT user_id FROM " . self::getTablePrefix() . "users WHERE user_id=" . $user_id);
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                // if we decide to update user info in phpBB database, can skip new user check above. and to execute next line directly instead
                self::phpBBupdateAccountById($user_id);
            }
            
            /* decided to simplify login, so comment this section
            $auth->acl($user->data); // Init permissions
            // $user->setup(); // Setup basic user-specific items (style, language, ...)
            $username = $zUserdata['uname'];
            $password = $zUserdata['pass'];
            $autologin = 1;
            $result = $auth->login($username, $password, $autologin);
            // $result: Array ( [status] => 11 [error_msg] => LOGIN_ERROR_PASSWORD [user_row] => Array ( [user_id] => 2 [username] => npetkov [user_password] => $H$9OXtHdCflXm3.sgusPwma0WmMY.8JD/ [user_passchg] => 0 [user_pass_convert] => 0 [user_email] => nmpetkov@gmail.com [user_type] => 3 [user_login_attempts] => 0 ) )
            if ($result['status'] == LOGIN_SUCCESS) {
                //User was successfully logged into phpBB
            } else {
                //User's login failed
            }*/

            // simple login - create session
            $autologin = 1;
            $viewonline = 1;
            $result = $user->session_create($user_id, 0, $autologin, $viewonline);
        }

        return $result;
    }
}