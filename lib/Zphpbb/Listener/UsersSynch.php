<?php
/**
 * Zphpbb Zikula Module
 *
 * @copyright Nikolay Petkov
 * @license GNU/GPL
 */
class Zphpbb_Listener_UsersSynch
{
    /**
     * Create a user in forum database, if created in Zikula Users.
     *
     * @param Zikula_Event $event The event that triggered this handler.
     *
     * @return void
     */
    public static function createAccountListener(Zikula_Event $event)
    {
        $userObj = $event->getSubject();

        self::updateAccount($userObj);
    }

    /**
     * Updates user information in forum database.
     *
     * @param Zikula_Event $event The event that triggered this handler.
     *
     * @return void
     */
    public static function updateAccountListener(Zikula_Event $event)
    {
        $userObj = $event->getSubject();

        self::updateAccount($userObj);
    }

    /**
     * Updates user information in forum database.
     *
     * @param array with user information.
     *
     * @return boolean
     */
    public static function updateAccount($userObj)
    {
        if (is_array($userObj) && $userObj['uid'] > 0) {
            if ($userObj['uid'] == 1) {
                // This is Anonymous user, don't change anyting
                return true;
            }
            if ($userObj['uid'] == 2) {
                // This is admin phpbb user, don't change anyting
                return true;
            }

            $connection = Doctrine_Manager::getInstance()->getCurrentConnection();
            $table_users = self::getTableUsers();

            // We need some board config information to create board user_error
            $board_config = Zphpbb_Util::getBoardConfig();

            // Determine user actual permissions for the forum
            $aUserGroups = UserUtil::getGroupsForUser($userObj['uid']);
            $aAdminGroups = explode(',', ModUtil::getVar ('Zphpbb', 'zphpbb_admingroups', '2'));
            $aModeratorGroups = explode(',', ModUtil::getVar ('Zphpbb', 'zphpbb_moderatorgroups', ''));
            // Chech if user is with admin and/or moderator role
            $user_is_admin = 0;
            $user_is_moderator = 0;
            if ($aUserGroups) {
                foreach ($aUserGroups as $userGroup) {
                    if ($aAdminGroups) {
                        foreach ($aAdminGroups as $adminGroup) {
                            if ($userGroup == $adminGroup) {
                                $user_is_admin = 1;
                            }
                        }
                    }
                    if ($aModeratorGroups) {
                        foreach ($aModeratorGroups as $moderatorGroup) {
                            if ($userGroup == $moderatorGroup) {
                                $user_is_moderator = 1;
                            }
                        }
                    }
                }
            }

            // Standard groups from groups table (relay on install default!)
            // 1 - GUESTS, 2 - REGISTERED, 3 - REGISTERED_COPPA, 4 - GLOBAL_MODERATORS, 5 - ADMINISTRATORS, 6 - BOTS, 7 - NEWLY_REGISTERED
            $group_id_registered = 2;
            $stmt = $connection->prepare("SELECT group_id FROM " . self::getTablePrefix() . "groups WHERE group_name='REGISTERED'");
            $stmt->execute();
            $data = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
            if (isset($data[0]['group_id'])) {
                $group_id_registered = $data[0]['group_id'];
            }
            $group_id_admins = 5;
            $stmt = $connection->prepare("SELECT group_id FROM " . self::getTablePrefix() . "groups WHERE group_name='ADMINISTRATORS'");
            $stmt->execute();
            $data = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
            if (isset($data[0]['group_id'])) {
                $group_id_admins = $data[0]['group_id'];
            }
            $group_id_moderators = 4;
            $stmt = $connection->prepare("SELECT group_id FROM " . self::getTablePrefix() . "groups WHERE group_name='GLOBAL_MODERATORS'");
            $stmt->execute();
            $data = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
            if (isset($data[0]['group_id'])) {
                $group_id_moderators = $data[0]['group_id'];
            }

            // Determine group_id for the user (main group)
            $group_id = $group_id_registered;
            if ($user_is_admin) {
                $group_id = $group_id_admins;
            } elseif ($user_is_moderator) {
                $group_id = $group_id_moderators;
            }

            // Determine user_type
            // USER_NORMAL - 0, USER_INACTIVE - 1), USER_IGNORE - 2 (BOTs), USER_FOUNDER - 3
            $user_type = 0;
            if (isset($userObj['activated']) && !$userObj['activated']) { // Check for deactivated user
                $user_type = 1;
            } elseif ($user_is_admin) {
                $user_type = 3;
            }

            // Check for new user
            $stmt = $connection->prepare("SELECT * FROM " . $table_users . " WHERE user_id=" . $userObj['uid']);
            try {
                $stmt->execute();
            } catch (Exception $e) {
                return LogUtil::registerError('Error: Could not get data from users table. ' . $e->getMessage());
            }
            if ($stmt->rowCount() == 0) {
                // New forum user

                // Wrap adding new user in transaction
                $connection->beginTransaction();
                try {
                    // new user - add in forum database
                    $sql = "INSERT INTO " . $table_users . " (";
                    $sql .= "user_id, user_type, group_id, username, username_clean, user_password, ";
                    $sql .= "user_regdate, user_allow_viewemail, user_allow_viewonline, user_notify, user_notify_pm, user_dateformat, ";
                    $sql .= "user_lang, user_style, user_allow_pm";
                    $sql .= ") VALUES (";
                    $sql .= $userObj['uid'] . ", " . $user_type . ", " . $group_id . ", '" . DataUtil::formatForStore($userObj['uname']) . "', '" . DataUtil::formatForStore(strtolower($userObj['uname'])) . "', '" . DataUtil::formatForStore($userObj['pass']) . "', ";
                    $sql .= time() . ", 0, 1, 0, 1, '" . $board_config['default_dateformat'] . "', ";
                    $sql .= "'" . $board_config['default_lang'] . "', " . $board_config['default_style'] . ", 1";
                    $sql .= ")";
                    $stmt = $connection->prepare($sql);
                    $stmt->execute();

                    // Insert in user_group table
                    self::user_group_member($userObj['uid'], $group_id_registered);
                    if ($user_is_moderator) {
                        self::user_group_member($userObj['uid'], $group_id_moderators);
                    }
                    if ($user_is_admin) {
                        self::user_group_member($userObj['uid'], $group_id_admins);
                    }

                    // end transaction
                    $connection->commit(); 
                } catch (Exception $e) {
                    // error, rollback the transaction
                    $connection->rollback();
                    return LogUtil::registerError('Error: Could not insert data for new user. ' . $e->getMessage());
                }

                // Get inserted row
                $stmt = $connection->prepare("SELECT * FROM " . $table_users . " WHERE user_id=" . $userObj['uid']);
                try {
                    $stmt->execute();
                } catch (Exception $e) {
                    return LogUtil::registerError('Error: Could not get data from users table. ' . $e->getMessage());
                }
                $result = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
                $userObjOld = $result[0];
            } else {
                // Existing user
                $result = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
                $userObjOld = $result[0];
            }

            // Update data
            $sql = "UPDATE " . $table_users . " SET ";
            $sql .= "user_id = " . $userObj['uid'] . ", ";
            $sql .= "user_type = " . $user_type . ", ";
            $sql .= "group_id = " . $group_id . ", ";
            if (isset($userObj['uname'])) {
                $sql .= "username = '" . DataUtil::formatForStore($userObj['uname']) . "', ";
            }
            /*if (isset($userObj['pass'])) {
                $sql .= "user_password = '" . DataUtil::formatForStore($userObj['pass']) . "', ";
            }*/
            if (isset($userObj['email'])) {
                $sql .= "user_email = '" . DataUtil::formatForStore($userObj['email']) . "', ";
                $sql .= "user_email_hash = '" . self::phpbb_email_hash(DataUtil::formatForStore($userObj['email'])) . "', ";
            }
            // Data optionally coming from Profile module
            if (isset($userObj['__ATTRIBUTES__']['icq'])) {
                $sql .= "user_icq = '" . DataUtil::formatForStore($userObj['__ATTRIBUTES__']['icq']) . "', ";
            }
            if (isset($userObj['__ATTRIBUTES__']['yim'])) {
                $sql .= "user_yim = '" . DataUtil::formatForStore($userObj['__ATTRIBUTES__']['yim']) . "', ";
            }
            if (isset($userObj['__ATTRIBUTES__']['aim'])) {
                $sql .= "user_aim = '" . DataUtil::formatForStore($userObj['__ATTRIBUTES__']['aim']) . "', ";
            }
            if (isset($userObj['__ATTRIBUTES__']['msnm'])) {
                $sql .= "user_msnm = '" . DataUtil::formatForStore($userObj['__ATTRIBUTES__']['msnm']) . "', ";
            }
            if (isset($userObj['__ATTRIBUTES__']['url'])) {
                $url = "http://" . preg_replace ("'http://'i", '',  $userObj['__ATTRIBUTES__']['url']);
                if ($url == 'http://') {
                    $url = '';
                }
                $sql .= "user_website = '" . DataUtil::formatForStore($url) . "', ";
            }
            if (isset($userObj['__ATTRIBUTES__']['signature'])) {
                $sql .= "user_sig = '" . DataUtil::formatForStore($userObj['__ATTRIBUTES__']['signature']) . "', ";
            }
            if (isset($userObj['__ATTRIBUTES__']['city'])) {
                $sql .= "user_from = '" . DataUtil::formatForStore($userObj['__ATTRIBUTES__']['city']) . "', ";
            }
            if (isset($userObj['__ATTRIBUTES__']['occupation'])) {
                $sql .= "user_occ = '" . DataUtil::formatForStore($userObj['__ATTRIBUTES__']['occupation']) . "', ";
            }
            if (isset($userObj['__ATTRIBUTES__']['interests'])) {
                $sql .= "user_interests = '" . DataUtil::formatForStore($userObj['__ATTRIBUTES__']['interests']) . "', ";
            }
            if (isset($userObj['__ATTRIBUTES__']['tzoffset'])) {
                $sql .= "user_timezone = '" . DataUtil::formatForStore($userObj['__ATTRIBUTES__']['tzoffset']) . "', ";
            }
            // Avatars are not synchronized yet
            /*if (isset($userObj['__ATTRIBUTES__']['avatar'])) {
                $sql .= "user_avatar = '" . DataUtil::formatForStore($userObj['__ATTRIBUTES__']['avatar']) . "', ";
                $sql .= "user_avatar_type = 3, ";
            }*/
            $sql = rtrim($sql, ' ,');
            $sql .= " WHERE user_id=" . $userObj['uid'];
            $stmt = $connection->prepare($sql);
            try {
                $stmt->execute();
            } catch (Exception $e) {
                return LogUtil::registerError('Error: Could not update data for the user.' . $e->getMessage());
            }

            // Update user rights
            self::user_group_member($userObj['uid'], $group_id);

            return true;
        }

        return false;
    }

    function user_group_member($user_id, $group_id, $deleteMember = false)
    {
        // Check for existing row
        $connection = Doctrine_Manager::getInstance()->getCurrentConnection();
        $stmt = $connection->prepare("SELECT * FROM " . self::getTablePrefix() . "user_group WHERE user_id=" . $user_id . " AND group_id=" . $group_id);
        try {
            $stmt->execute();
        } catch (Exception $e) {
            return LogUtil::registerError('Error: Could not get data from user_group table. ' . $e->getMessage());
        }
        if ($stmt->rowCount() == 0 && !$deleteMember) {
            $stmt = $connection->prepare("INSERT INTO " . self::getTablePrefix() . "user_group (user_id, group_id, user_pending) VALUES (".$user_id.", ".$group_id.", 0)");
            $stmt->execute();
        } elseif ($stmt->rowCount() > 0 && $deleteMember) {
            $stmt = $connection->prepare("DELETE FROM " . self::getTablePrefix() . "user_group WHERE user_id=" . $user_id . " AND group_id=" . $group_id);
            $stmt->execute();
        }

        return true;
    }

    function phpbb_email_hash($email)
    {
        return sprintf('%u', crc32(strtolower($email))) . strlen($email);
    }

    /**
     * Deletes the user in forum database, if deleted in Zikula Users.
     *
     * @param Zikula_Event $event The event that triggered this handler.
     *
     * @return void
     */
    public static function deleteAccountListener(Zikula_Event $event)
    {
        $userObj = $event->getSubject();

        if (is_array($userObj) && $userObj['uid'] > 0) {
            $connection = Doctrine_Manager::getInstance()->getCurrentConnection();
            $stmt = $connection->prepare("DELETE FROM " . self::getTableUsers() . " WHERE user_id=" . $userObj['uid']);
            $stmt->execute();
        }
    }

    public static function getTableUsers()
    {
        return self::getTablePrefix() . 'users';
    }

    public static function getTablePrefix()
    {
        return ModUtil::getVar ('Zphpbb', 'table_prefix', 'phpbb_');
    }
}
