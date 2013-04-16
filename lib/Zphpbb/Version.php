<?php
/**
 * Zphpbb Zikula Module
 *
 * @copyright Nikolay Petkov
 * @license GNU/GPL
 */
class Zphpbb_Version extends Zikula_AbstractVersion
{
    public function getMetaData()
    {
        $meta = array();
        $meta['displayname']    = 'Zphpbb';
        $meta['url']            = 'forum';
        $meta['description']    = $this->__('Forum based on phpBB');
        $meta['version']        = '1.0.0';
        $meta['capabilities']   = array(HookUtil::SUBSCRIBER_CAPABLE => array('enabled' => true));
        $meta['securityschema'] = array('Zphpbb::' => '::');
        $meta['core_min']       = '1.3.0';

        return $meta;
    }

    protected function setupHookBundles()
    {
        // Register hooks
        $bundle = new Zikula_HookManager_SubscriberBundle($this->name, 'subscriber.zphpbb.ui_hooks.items', 'ui_hooks', $this->__('Zphpbb Items Hooks'));
        $bundle->addEvent('display_view', 'zphpbb.ui_hooks.items.display_view');
        $bundle->addEvent('form_edit', 'zphpbb.ui_hooks.items.form_edit');
        $this->registerHookSubscriberBundle($bundle);
    }
}