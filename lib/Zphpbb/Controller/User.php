<?php
/**
 * Zphpbb Zikula Module
 *
 * @copyright Nikolay Petkov
 * @license GNU/GPL
 */
class Zphpbb_Controller_User extends Zikula_AbstractController
{
    private $phpBBfile;

    /**
     * Main user function
     *
     * @param array $args Arguments.
     */
    public function main($args)
    {
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('Zphpbb::', '::', ACCESS_READ), LogUtil::getErrorMsgPermission());
        
        return $this->index($args);
    }

    public function index($args) {
        $this->phpBBfile = 'index';
        return $this->phpBBcall($args);
    }

    public function viewforum($args) {
        $this->phpBBfile = 'viewforum';
        return $this->phpBBcall($args);
    }

    public function viewtopic($args) {
        $this->phpBBfile = 'viewtopic';
        return $this->phpBBcall($args);
    }

    public function posting($args) {
        $this->phpBBfile = 'posting';
        return $this->phpBBcall($args);
    }

    public function ucp($args) {
        $this->phpBBfile = 'ucp';
        return $this->phpBBcall($args);
    }

    public function search($args) {
        $this->phpBBfile = 'search';
        return $this->phpBBcall($args);
    }

    public function mcp($args) {
        $this->phpBBfile = 'mcp';
        return $this->phpBBcall($args);
    }

    public function memberlist($args) {
        $this->phpBBfile = 'memberlist';
        return $this->phpBBcall($args);
    }

    public function viewonline($args) {
        $this->phpBBfile = 'viewonline';
        return $this->phpBBcall($args);
    }

    public function faq($args) {
        $this->phpBBfile = 'faq';
        return $this->phpBBcall($args);
    }

    public function cron($args) {
        $this->phpBBfile = 'cron';
        return $this->phpBBcall($args);
    }

    public function feed($args) {
        $this->phpBBfile = 'feed';
        return $this->phpBBcall($args);
    }

    public function report($args) {
        $this->phpBBfile = 'report';
        return $this->phpBBcall($args);
    }

    public function phpBBcall($args)
    {
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('Zphpbb::', '::', ACCESS_READ), LogUtil::getErrorMsgPermission());
        $phpbbdir = 'modules/Zphpbb/vendor/phpbb/';
        $integrType = 'ifrm';

        // => phpbb startup
        // Report all errors, except notices and deprecation messages
        if (!defined('E_DEPRECATED'))
        {
            define('E_DEPRECATED', 8192);
        }
        $level = E_ALL & ~E_NOTICE & ~E_DEPRECATED;
        if (version_compare(PHP_VERSION, '5.4.0-dev', '>='))
        {
            // PHP 5.4 adds E_STRICT to E_ALL.
            // Our utf8 normalizer triggers E_STRICT output on PHP 5.4.
            // Unfortunately it cannot be made E_STRICT-clean while
            // continuing to work on PHP 4.
            // Therefore, in phpBB 3.0.x we disable E_STRICT on PHP 5.4+,
            // while phpBB 3.1 will fix utf8 normalizer.
            // E_STRICT is defined starting with PHP 5
            if (!defined('E_STRICT'))
            {
                define('E_STRICT', 2048);
            }
            $level &= ~E_STRICT;
        }
        error_reporting($level);
        // <= phpbb startup
        
        // SEO
        $page_title = ModUtil::getVar ('Zphpbb', 'page_title', '');
        if ($page_title) {
            PageUtil::setVar('title', $page_title);
        }
        $page_description = ModUtil::getVar ('Zphpbb', 'page_description', '');
        if ($page_description) {
            PageUtil::setVar('description', $page_description);
        }
        $page_robots = ModUtil::getVar ('Zphpbb', 'page_robots', '');
        if ($page_robots) {
            $sm = ServiceUtil::getManager();
            $sm['zikula_view.metatags']['robots'] = $page_robots;
        }

        // Auto login/logout according to the state of current Zikula user
        define('INZIK_TYPE', 'embd'); // this is not change error handler with set_error_handler in common.php below
        Zphpbb_Util::phpbbSessionHandler();

        if ($integrType == 'ifrm') {
            $iframe_src = $phpbbdir . $this->phpBBfile.'.php';
            // set passed arguments
            $params = '';
            foreach ($_GET as $key => $value) {
                if ($key != 'module' && $key != 'type' && $key != 'func') {
                    $params .= ($params ? '&' : '?') . $key . '=' . $value;
                }
            }
            $this->view->assign('iframe_src', $iframe_src . $params);
        } else {
            ob_start();
            include $phpbbdir . $this->phpBBfile.'.php';
            $content = ob_get_contents();
            ob_end_clean();
            $this->view->assign('content', $content);
        }
        $this->view->assign('INTYPE', $integrType);

        return $this->view->fetch('user/call.tpl');
    }
}