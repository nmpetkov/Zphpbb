<?php
/**
 * Zphpbb Zikula Module
 *
 * @copyright Nikolay Petkov
 * @license GNU/GPL
 */

class Zphpbb_Api_Account extends Zikula_AbstractApi
{
    /**
     * Return an array of items to show in the your account panel
     *
     * @return   array
     */
    public function getall($args)
    {
        $items = array();

        // Create an array of links to return
        if (SecurityUtil::checkPermission('Zphpbb::', '::', ACCESS_READ)) {
            $items[] = array('url' => ModUtil::url('Zphpbb', 'user', 'ucp'),
                    'module'  => 'Zphpbb',
                    'title'   => $this->__('Profile in the forum'),
                    'icon'    => 'user.png');

        }

        // Return the items
        return $items;
    }
}
