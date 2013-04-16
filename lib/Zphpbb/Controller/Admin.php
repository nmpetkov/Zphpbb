<?php
/**
 * Zphpbb Zikula Module
 *
 * @copyright Nikolay Petkov
 * @license GNU/GPL
 */
class Zphpbb_Controller_Admin extends Zikula_AbstractController
{
    /**
     * Main administration function
     */
    public function main()
    {
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('Zphpbb::', '::', ACCESS_ADMIN), LogUtil::getErrorMsgPermission());

        // Auto login/logout according to the state of current Zikula user
        Zphpbb_Util::phpbbSessionHandler();

        $phpbbdir = 'modules/Zphpbb/vendor/phpbb/';
        $iframe_src = $phpbbdir . 'adm/index.php';
        $this->view->assign('iframe_src', $iframe_src);

        return $this->view->fetch('admin/main.tpl');
    }

    public function index($args)
    {
        return $this->main($args);
    }

    public function modifyconfig()
    {
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('Zphpbb::', '::', ACCESS_ADMIN));

        $vars = ModUtil::getVar($this->name);
        // some default settings
        if (empty($vars['table_prefix'])) {
            $vars['table_prefix'] = 'phpbb_';
        }

        // get all groups
        $groups = UserUtil::getGroups('', 'ORDER BY gid');
        // count groups
        $groups[count] = count($groups, 0);

        $this->view->setCaching(false);
        $this->view->assign($vars);
        $this->getView()->assign('groups', $groups);

        return $this->view->fetch('admin/modifyconfig.tpl');
    }

    public function updateconfig()
    {
        $this->checkCsrfToken(); // confirm the forms authorisation key
		$this->throwForbiddenUnless(SecurityUtil::checkPermission($this->name . '::', '::', ACCESS_ADMIN));

        // get current module variables
        $vars = ModUtil::getVar($this->name);

        // get module variables from form
        $modvars = array();
        $modvars['table_prefix'] = FormUtil::getPassedValue('table_prefix', 'phpbb_', 'POST');
        $modvars['zphpbb_admingroups'] = FormUtil::getPassedValue('zphpbb_admingroups', '2');
        $modvars['zphpbb_moderatorgroups'] = FormUtil::getPassedValue('zphpbb_moderatorgroups', '');
        $modvars['page_title'] = FormUtil::getPassedValue('page_title', '', 'POST');
        $modvars['page_description'] = FormUtil::getPassedValue('page_description', '', 'POST');
        $modvars['page_robots'] = FormUtil::getPassedValue('page_robots', '', 'POST');
        $modvars['login_usezikula'] = FormUtil::getPassedValue('login_usezikula', '', 'POST');
        $modvars['display_rightsadmins'] = FormUtil::getPassedValue('display_rightsadmins', '', 'POST');
        $modvars['display_phpbbfooter'] = FormUtil::getPassedValue('display_phpbbfooter', '', 'POST');

        // table prefix change
        if ($vars['table_prefix'] && $vars['table_prefix']<>$modvars['table_prefix']) {
            // table prefix is changed
            $mandatoryinprefix = 'phpbb';
            if (strpos($modvars['table_prefix'], $mandatoryinprefix) === false) {
                return LogUtil::registerError($this->__('Error: table prefix must contain fragment <b>phpbb</b>.'));
            }
            // ok, let's change in db
            $connection = Doctrine_Manager::getInstance()->getCurrentConnection();
            $stmt = $connection->prepare("SHOW TABLES LIKE '%" . $vars['table_prefix'] . "_%'");
            try {
                $stmt->execute();
            } catch (Exception $e) {
                return LogUtil::registerError(__('Error: ') . $e->getMessage());
            }
            $items = $stmt->fetchAll(Doctrine_Core::FETCH_NUM);
            $tablenames = Zphpbb_Util::getTableNames();
            foreach ($items as $item) {
                $table_stem = Zphpbb_Util::getTableStem($item[0], $vars['table_prefix']);
                if (in_array($table_stem, $tablenames)) {
                    $stmt = $connection->prepare('RENAME TABLE ' . $item[0] . " TO " . $modvars['table_prefix'] . $table_stem);
                    try {
                        $stmt->execute();
                    } catch (Exception $e) {
                        return LogUtil::registerError(__('Error: ') . $e->getMessage());
                    }
                }
            }
        }

        // update module variables
		$this->setVars($modvars);

        // delete forum cache
        $this->deleteBoardCachedFiles();

        // the module configuration has been updated successfuly
        LogUtil::registerStatus($this->__('Done! Module configuration updated.'));

        return System::redirect(ModUtil::url($this->name, 'admin', 'modifyconfig'));
    }

    public function deletecache()
    {
        $this->deleteBoardCachedFiles();
        LogUtil::registerStatus($this->__('Done! Forum cached files are deleted.'));

        return System::redirect(ModUtil::url($this->name, 'admin', 'main'));
    }

    /**
     * Delete all cached templates or items in forum cache directory
     */
    public function deleteBoardCachedFiles()
    {
        // declare main phpbb items to be used from Zphpbb
        include_once 'modules/Zphpbb/vendor/phpbb_include.php';
        include_once $phpbb_root_path . 'common.' . $phpEx;

        $cache->purge();

        return true;
    }

    public function export_form()
    {
		$this->throwForbiddenUnless(SecurityUtil::checkPermission($this->name . '::', '::', ACCESS_ADMIN));

        $this->view->setCaching(false);

        return $this->view->fetch('admin/export.tpl');
    }

    public function export_perform()
    {
        $this->checkCsrfToken(); // confirm the forms authorisation key
		$this->throwForbiddenUnless(SecurityUtil::checkPermission($this->name . '::', '::', ACCESS_ADMIN));

        return System::redirect(ModUtil::url($this->name, 'admin', 'main'));
    }

    public function import_form()
    {
		$this->throwForbiddenUnless(SecurityUtil::checkPermission($this->name . '::', '::', ACCESS_ADMIN));

        $this->view->setCaching(false);

        // declare main phpbb items to be used from Zphpbb
        include_once 'modules/Zphpbb/vendor/phpbb_include.php';

        $this->view->assign('board_config', Zphpbb_Util::getBoardConfig());
        $this->view->assign('phpbb_root_path', $phpbb_root_path);

        return $this->view->fetch('admin/import.tpl');
    }

    public function import_perform()
    {
        $this->checkCsrfToken(); // confirm the forms authorisation key
		$this->throwForbiddenUnless(SecurityUtil::checkPermission($this->name . '::', '::', ACCESS_ADMIN));

        $tablenames = Zphpbb_Util::getTableNames();
        $tablesToExclude = array('bots', 'config', 'extensions', 'extension_groups', 'icons', 'lang', 'log', 'login_attempts', 'moderator_cache', 'modules', 'profile_fields', 'profile_fields_data', 'profile_fields_lang', 'profile_lang', 'reports', 'reports_reasons', 'search_results', 'search_wordlist', 'search_wordmatch', 'sessions', 'sessions_keys', 'sitelist', 'smilies', 'styles', 'styles_imageset', 'styles_imageset_data', 'styles_template', 'styles_template_data', 'styles_theme', 'users', 'user_group');
        $phpbb_tableprefix = Zphpbb_Util::getTablePrefix();
        $phpbbSource_tableprefix = FormUtil::getPassedValue('phpbb3_tableprefix', '', 'POST');

        $connection = Doctrine_Manager::getInstance()->getCurrentConnection();

        foreach ($tablenames as $tablename) {
            if (!in_array($tablename, $tablesToExclude)) {
                // copy (import) table
                LogUtil::registerStatus(__('Importing table ') . $phpbbSource_tableprefix.$tablename);
                $stmt = $connection->prepare("TRUNCATE TABLE " .$phpbb_tableprefix.$tablename);
                try {
                    $stmt->execute();
                } catch (Exception $e) {
                    LogUtil::registerError(__('Error: ') . $e->getMessage());
                }
                $stmt = $connection->prepare("INSERT " .$phpbb_tableprefix.$tablename . " SELECT * FROM " .$phpbbSource_tableprefix.$tablename);
                try {
                    $stmt->execute();
                } catch (Exception $e) {
                    LogUtil::registerError(__('Error: ') . $e->getMessage());
                }
            }
        }

        Zphpbb_Util::phpBBupdateAllAccounts(true);

        // transfer avatars
        $stmt = $connection->prepare("UPDATE ".$phpbb_tableprefix."users U SET U.user_avatar = (SELECT U2.user_avatar FROM ".$phpbbSource_tableprefix."users U2 WHERE U2.user_id = U.user_id) WHERE U.user_avatar=''");
        try {
            $stmt->execute();
        } catch (Exception $e) {
            LogUtil::registerError(__('Error: ') . $e->getMessage());
        }
        $stmt = $connection->prepare("UPDATE ".$phpbb_tableprefix."users U SET U.user_avatar_type = (SELECT U2.user_avatar_type FROM ".$phpbbSource_tableprefix."users U2 WHERE U2.user_id = U.user_id) WHERE U.user_avatar_type=0");
        try {
            $stmt->execute();
        } catch (Exception $e) {
            LogUtil::registerError(__('Error: ') . $e->getMessage());
        }
        $stmt = $connection->prepare("UPDATE ".$phpbb_tableprefix."users` SET user_avatar_width = '80', user_avatar_height = '80' WHERE user_avatar_width=0 AND user_avatar_height=0 AND user_avatar_type<>0");
        try {
            $stmt->execute();
        } catch (Exception $e) {
            LogUtil::registerError(__('Error: ') . $e->getMessage());
        }
        /* 
        global $phpbb2_tableprefix;
        $phpbb2_tableprefix = FormUtil::getPassedValue('phpbb2_tableprefix', '', 'POST');

        // phpbb specific
        define('IN_INSTALL', true);
        define('DEBUG_EXTRA', true);
        include_once 'modules/Zphpbb/vendor/phpbb_include.php';
        require($phpbb_root_path . 'config.' . $phpEx); // database settings
        require($phpbb_root_path . 'includes/functions.' . $phpEx);
        require($phpbb_root_path . 'includes/functions_content.' . $phpEx);
        require($phpbb_root_path . 'includes/template.' . $phpEx);
        require($phpbb_root_path . 'includes/acm/acm_file.' . $phpEx);
        include($phpbb_root_path . 'includes/auth.' . $phpEx);
        include($phpbb_root_path . 'includes/session.' . $phpEx);
        require($phpbb_root_path . 'includes/cache.' . $phpEx);
        require($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
        require($phpbb_root_path . 'includes/utf/utf_tools.' . $phpEx);
        require($phpbb_root_path . 'includes/functions_install.' . $phpEx);
        $language = System::getVar('language_i18n', 'en');
		if (!file_exists($phpbb_root_path . 'language/' . $language . '/common.' . $phpEx)) {
			$language = 'en';
		}
        include($phpbb_root_path . 'language/' . $language . '/common.' . $phpEx);
        include($phpbb_root_path . 'language/' . $language . '/install.' . $phpEx);

        // real convertion
        //require($phpbb_root_path . 'includes/functions_convert.' . $phpEx);
        require($phpbb_root_path . 'install/install_convert.php');
        $user = new user();
        $cache = new cache();
        $auth = new auth();
        $p_master = '';
        $convert = new install_convert($p_master);
        $convert->p_master = new module();
        $config = Zphpbb_Util::getBoardConfig();
        $_REQUEST['tag'] = 'phpbb20'; // for $convert->get_convert_settings
        $convert->get_convert_settings('settings');
        $convert->convert_data('in_progress');
        $convert->finish_conversion();
        */

        LogUtil::registerStatus($this->__('Done! Data impoted.'));

        return System::redirect(ModUtil::url($this->name, 'admin', 'import_form'));
    }

    // checks/integrates avatars between upload folder and database
    public function avatar_check()
    {
        $this->checkCsrfToken(); // confirm the forms authorisation key
		$this->throwForbiddenUnless(SecurityUtil::checkPermission($this->name . '::', '::', ACCESS_ADMIN));
 
        $avatars_renamewrong = (bool)FormUtil::getPassedValue('avatars_renamewrong', false, 'POST');
        $avatars_deleteorphaned = (bool)FormUtil::getPassedValue('avatars_deleteorphaned', false, 'POST');

        // declare main phpbb items to be used from Zphpbb
        include_once 'modules/Zphpbb/vendor/phpbb_include.php';
        $board_config = Zphpbb_Util::getBoardConfig();
        $avatar_path = $board_config['avatar_path']  . (substr($board_config['avatar_path'], -1) === '/' ? '' : '/');
        $phpbb_tableprefix = Zphpbb_Util::getTablePrefix();

        $aFiles = FileUtil::getFiles($phpbb_root_path.$board_config['avatar_path'], false, true, null, 'f');

        $connection = Doctrine_Manager::getInstance()->getCurrentConnection();

        foreach($aFiles as $file) {
            $resultStatus = '';
            if (strpos($file, $board_config['avatar_salt']) === 0) {
                $resultStatus = __('in new format');
            } else {
                $stmt = $connection->prepare("SELECT * FROM ".$phpbb_tableprefix."users WHERE user_avatar='".$file."'");
                $result = array();
                try {
                    $stmt->execute();
                    $result = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
                } catch (Exception $e) {
                    LogUtil::registerError(__('Error: ') . $e->getMessage());
                }
                $user = $result[0];
                if ($user['user_id']) {
                    $resultStatus = __('found in user ').$user['user_id'].', '.__('avatar type ').$user['user_avatar_type'];
                    if ($avatars_renamewrong) {
                        $resultStatus .= ': ';
                        // rename to new format
                        $fileNew = $board_config['avatar_salt'] . '_' . $user['user_id'] . FileUtil::getExtension($file, true);
                        if (rename($phpbb_root_path.$avatar_path.$file, $phpbb_root_path.$avatar_path.$fileNew)) {
                            $resultStatus .= __('renamed to ').$fileNew;
                            // rename to somethink like 286_1366044577.jpg
                            $fileNewDb = $user['user_id']."_".time().FileUtil::getExtension($file, true);
                            $stmt = $connection->prepare("UPDATE ".$phpbb_tableprefix."users SET user_avatar='".$fileNewDb."', user_avatar_type=1 WHERE user_id=".$user['user_id']);
                            try {
                                $stmt->execute();
                                $resultStatus .= __(', database avatar field set to ').$fileNewDb;
                            } catch (Exception $e) {
                                LogUtil::registerError(__('Error: ') . $e->getMessage());
                            }
                        } else {
                            $resultStatus .= __('error renaming');
                        }
                    }
                } else {
                    $resultStatus = __('orphan (not found in users)');
                    if ($avatars_deleteorphaned) {
                        $resultStatus .= ': ';
                        // defete prphan
                        if (unlink($phpbb_root_path.$avatar_path.$file)) {
                            $resultStatus .= __('deleted');
                        } else {
                            $resultStatus .= __('error deleting');
                        }
                    }
                }
            }
            LogUtil::registerStatus($this->__('File').': '.$file.': '.$resultStatus);
        }

        LogUtil::registerStatus($this->__('Done! Avatars checked.'));

        return System::redirect(ModUtil::url($this->name, 'admin', 'import_form'));
    }

    public function updateAccounts()
    {
		$this->throwForbiddenUnless(SecurityUtil::checkPermission($this->name . '::', '::', ACCESS_ADMIN));

        if (Zphpbb_Util::phpBBupdateAllAccounts(false)) {
            LogUtil::registerStatus($this->__('Done! Users accounts updated.'));
        } else {
            LogUtil::registerError($this->__('Error updating some of accounts.'));
        }

        return System::redirect(ModUtil::url($this->name, 'admin', 'main'));
    }
}
