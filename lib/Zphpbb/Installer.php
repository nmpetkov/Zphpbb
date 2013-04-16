<?php
/**
 * Zphpbb Zikula Module
 *
 * @copyright Nikolay Petkov
 * @license GNU/GPL
 */
class Zphpbb_Installer extends Zikula_AbstractInstaller
{
    /**
     * Initializes a new install
     *
     * @return  boolean    true/false
     */
    public function install()
    {
        // Delete traces from any attempts to install this module before
        // Uncomment this line ONLY if you have unsuccessful attempts  to install
        // This will delete all tables with name prefix phpbb_ !!!
        //$this->uninstall();

        // Installing the module
        $connection = Doctrine_Manager::getInstance()->getCurrentConnection();
        
        $table_prefix = 'phpbb_';
        $this->setVar('table_prefix', $table_prefix);
        $this->setVar('zphpbb_admingroups', '2');
        $this->setVar('zphpbb_moderatorgroups', '');

        // check for existing tables like phpbb_*
        // str_replace is because _ is wildcard character, as %
        $stmt = $connection->prepare("SHOW TABLES LIKE '" . str_replace('_', '\_', $table_prefix) . "%'");
        $stmt->execute();
        $tablesexist = $stmt->fetchAll(Doctrine_Core::FETCH_NUM);
        if ($tablesexist) {
            $message = '';
            foreach($tablesexist as $table) {
                $message .= $table[0].'<br />';
            }
            return LogUtil::registerError('Similar table exist:<br />'.$message);
        }

        // phpbb specific
        define('IN_INSTALL', true);
        define('DEBUG_EXTRA', true);
        include_once 'modules/Zphpbb/vendor/phpbb_include.php';
        require($phpbb_root_path . 'config.' . $phpEx); // database settings
        require($phpbb_root_path . 'includes/functions.' . $phpEx);
        require($phpbb_root_path . 'includes/template.' . $phpEx);
        require($phpbb_root_path . 'includes/acm/acm_file.' . $phpEx);
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

        // create tables and load data
        require($phpbb_root_path . 'install/install_install.php');
        $install = new install_install();
        $cache = new cache();
        if ($install->load_schema('', '') === true) {
            $install->build_search_index('', '');
            $install->add_modules('', '');
            $install->add_language('', '');
            //$install->add_bots('', '');
            Zphpbb_Util::phpBBupdateAllAccounts(true);
        } else {
            return false;
        }

        // Attempt to copy site logo
        $site_logo = 'images/logo.gif';
        if (file_exists($site_logo)) {
            @copy($site_logo, $phpbb_root_path.'styles/prosilver/imageset/site_logo.gif');
            @copy($site_logo, $phpbb_root_path.'styles/subsilver2/imageset/site_logo.gif');
        }

        // Module variables
        $this->setVar('page_title', 'Forum');
        $this->setVar('page_description', 'Place for discussions and support');
        $this->setVar('page_robots', 'noindex, nofollow, noarchive');
        $this->setVar('display_rightsadmins', '1');
        $this->setVar('display_phpbbfooter', '1');
        $this->setVar('login_usezikula', '1');

        // Register hooks
        HookUtil::registerSubscriberBundles($this->version->getHookSubscriberBundles());

        // Register event handlers
        EventUtil::registerPersistentModuleHandler('Zphpbb', 'user.account.create', array('Zphpbb_Listener_UsersSynch', 'createAccountListener'));
        EventUtil::registerPersistentModuleHandler('Zphpbb', 'user.account.update', array('Zphpbb_Listener_UsersSynch', 'updateAccountListener'));
        EventUtil::registerPersistentModuleHandler('Zphpbb', 'user.account.delete', array('Zphpbb_Listener_UsersSynch', 'deleteAccountListener'));

        return true;
    }
    
    /**
     * Upgrade module
     *
     * @param   string    $oldversion
     * @return  boolean   true/false
     */
    public function upgrade($oldversion)
    {
        // upgrade dependent on old version number
        switch ($oldversion)
        {
        case '1.0.0':
				// future upgrade routines
        }
        return true;
    }
    
    /**
     * Delete module
     *
     * @return  boolean    true/false
     */
    public function uninstall()
    {
        $table_prefix = ModUtil::getVar ('Zphpbb', 'table_prefix', 'phpbb_');
        $tablenames = Zphpbb_Util::getTableNames();

        $connection = Doctrine_Manager::getInstance()->getCurrentConnection();
        $stmt = $connection->prepare("SHOW TABLES LIKE '" . str_replace('_', '\_', $table_prefix) . "%'");
        try {
            $stmt->execute();
        } catch (Exception $e) {
            return LogUtil::registerError(__('Error: ') . $e->getMessage());
        }
        $items = $stmt->fetchAll(Doctrine_Core::FETCH_NUM);
        foreach ($items as $item) {
            $table_stem = Zphpbb_Util::getTableStem($item[0], $table_prefix);
            if (in_array($table_stem, $tablenames)) {
                $stmt = $connection->prepare("DROP TABLE `" . $item[0] . "`");
                try {
                    $stmt->execute();
                } catch (Exception $e) {
                    return LogUtil::registerError(__('Error: ') . $e->getMessage());
                }
            }
        }

        // Unregister event handlers
        EventUtil::unregisterPersistentModuleHandler('Zphpbb', 'user.account.create', array('Zphpbb_Listener_UsersSynch', 'createAccountListener'));
        EventUtil::unregisterPersistentModuleHandler('Zphpbb', 'user.account.update', array('Zphpbb_Listener_UsersSynch', 'updateAccountListener'));
        EventUtil::unregisterPersistentModuleHandler('Zphpbb', 'user.account.delete', array('Zphpbb_Listener_UsersSynch', 'deleteAccountListener'));

        return true;
    }
}