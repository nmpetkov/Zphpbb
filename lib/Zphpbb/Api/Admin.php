<?php
/**
 * Zphpbb Zikula Module
 *
 * @copyright Nikolay Petkov
 * @license GNU/GPL
 */
class Zphpbb_Api_Admin extends Zikula_AbstractApi
{
    /**
     * Get available admin panel links
     *
     * @return array array of admin links
     */
    public function getlinks()
    {
        $links = array();
    
        if (SecurityUtil::checkPermission('Zphpbb::', '::', ACCESS_ADMIN)) {
            $links[] = array(
                'url' => ModUtil::url('Zphpbb', 'admin', 'main'),
                'text' => $this->__('Administration'),
                'class' => 'z-icon-es-gears');

            $links[] = array(
                'url' => ModUtil::url('Zphpbb', 'admin', 'modifyconfig'),
                'text' => $this->__('Settings'),
                'class' => 'z-icon-es-config');

            $links[] = array(
                'url' => ModUtil::url('Zphpbb', 'admin', 'import_form'),
                'text' => $this->__('Import'),
                'class' => 'z-icon-es-import');

            $links[] = array(
                'url' => ModUtil::url('Zphpbb', 'admin', 'export_form'),
                'text' => $this->__('Export'),
                'class' => 'z-icon-es-export');
        }
    
        return $links;
    }
}