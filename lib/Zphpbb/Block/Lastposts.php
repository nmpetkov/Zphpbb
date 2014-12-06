<?php
/**
 * Zphpbb Zikula Module
 *
 * @copyright Nikolay Petkov
 * @license GNU/GPL
 */
class Zphpbb_Block_Lastposts extends Zikula_Controller_AbstractBlock
{
    /**
     * Initialise block
     */
    public function init()
    {
        SecurityUtil::registerPermissionSchema('Zphpbb:lastpostsblock:', 'Block ID::');
    }
    
    /**
     * Return array with block information
     */
    public function info()
    {
        return array(
            'module'           => 'Zphpbb',
            'text_type'        => 'Zphpbb',
            'text_type_long'   => $this->__('Last forum posts'),
            'allow_multiple'   => true,
            'form_content'     => false,
            'form_refresh'     => false,
            'show_preview'     => true);
    }
    
    /**
     * Display block
     */
    public function display($blockinfo)
    {
        if (!SecurityUtil::checkPermission('Zphpbb_Lastposts::', $blockinfo[bid]."::", ACCESS_READ)) {
            return;
        }
        if (!ModUtil::available('Zphpbb')) {
            return;
        }

        // Get variables from content block
        $vars = BlockUtil::varsFromContent($blockinfo['content']);

        $blockinfo['content'] = "";

        // Implementation cached content
        $enable_cache = true;
        $write_to_cache = false;	# flag
        $cache_time = 180; # seconds
        if (isset($vars['cache_time'])) $cache_time = $vars['cache_time'];
        $content = "";
        if ($enable_cache and $cache_time>0) {
            $cachefilestem = 'Zphpbb_' . $blockinfo['bid'];
            $cachedir = System::getVar('temp');
            if (StringUtil::right($cachedir, 1)<>'/') $cachedir .= '/';
            if (isset($vars['cache_dir']) and !empty($vars['cache_dir'])) $cachedir .= $vars['cache_dir'];
            else $cachedir .= 'any_cache';
            $cachefile = $cachedir .'/'. $cachefilestem;
            // attempt to load from cache
            if (file_exists($cachefile)) {
                $file_time = filectime($cachefile);
                $now = time();
                $diff = ($now - $file_time);
                if ($diff <= $cache_time) {
                    $content = file_get_contents($cachefile);
                }
            }
            if (empty($content)) $write_to_cache = true; # not loaded, flag to write to cache later
        }
        if (empty($content)) {
            // Create output object
            $connection = Doctrine_Manager::getInstance()->getCurrentConnection();

            $table_prefix = ModUtil::getVar ('Zphpbb', 'table_prefix', 'phpbb_');

            // include some files
            define('IN_PHPBB', true);
            $phpbb_root_path = 'modules/Zphpbb/vendor/phpbb/';
            include_once($phpbb_root_path . "includes/constants.php");

            $vars['last_X_posts'] = $vars['last_X_posts'] ? $vars['last_X_posts'] : "5"; // defaults to some items if empty
            $excluded_forums = '';
            if (!is_null ($vars['excluded_forums']) && is_array($vars['excluded_forums'])) {
                 $excluded_forums = in_array("", $vars['excluded_forums']) ? "" : implode(", ", $vars['excluded_forums']);
            }
            $lastvisit  = 0;
            if (UserUtil::isLoggedIn()) {
                $uid = UserUtil::getVar('uid');
                $query = "SELECT user_lastvisit FROM   " . USERS_TABLE . " WHERE  user_id = $uid";
                $stmt = $connection->prepare($query);
                $stmt->execute();
                $result = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
                $lastvisit = $result[0]['user_lastvisit'];
            }
            if (SecurityUtil::checkPermission('Zphpbb_Lastposts::', $blockinfo[bid]."::", ACCESS_ADMIN)) {
                //user have complete access
            } else {
                // TODO: check forums to exclude if no permissions
            }
            // main query
            $query = "SELECT t.topic_title, t.topic_replies, t.topic_views, t.topic_id, u.username, p.post_id, p.poster_id, p.post_time, p.post_text, f.forum_id, f.forum_name 
                FROM ((" . TOPICS_TABLE . " t INNER JOIN " . POSTS_TABLE . " p ON ";
                if ($vars['group_topics']) {
                    // every topic to appear just once
                    $query .= "t.topic_last_post_id = p.post_id";
                } else {
                    $query .= "t.topic_id = p.topic_id";
                }
                $query .= ") 
                INNER JOIN " . USERS_TABLE . " u ON u.user_id = p.poster_id) 
                INNER JOIN " . FORUMS_TABLE . " f ON f.forum_id = t.forum_id 
                WHERE ";
                if ($excluded_forums) {
                    $query .= "f.forum_id NOT IN (" . $excluded_forums . ") AND ";
                }
                $query .= "t.topic_approved AND t.topic_status <> " . ITEM_MOVED;
                $query .= " ORDER BY post_time DESC LIMIT ".$vars['last_X_posts'];
            $stmt = $connection->prepare($query);
            $stmt->execute();
            $items = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
            if (!$items) {
                return BlockUtil::themesideblock($blockinfo);
            }

            // get module information
            $modinfo = ModUtil::getInfoFromName("Zphpbb");

            // add more info and icons
            foreach (array_keys($items) as $k) {
                $items[$k]['replyicon'] = 'modules/'.$modinfo['directory'].'/images/' . (($lastvisit > 0 && $items[$k]['post_time'] > $lastvisit) ? "icon_newest_reply.gif" : "icon_latest_reply.gif");
                $items[$k]['post_username'] = ($items[$k]['poster_id'] != ANONYMOUS) ? $items[$k]['username'] : $this->__('Guest');
                $items[$k]['profile_url'] = ($items[$k]['poster_id'] != ANONYMOUS) ? ModUtil::url('Zphpbb', 'user', 'memberlist', array('mode' => 'viewprofile', 'u' => $items[$k]['poster_id'])) : "";
            }
        }

        $render_template = 'blocks/lastposts.tpl';
        if ($this->view->is_cached($render_template)) {
            $content = $this->view->fetch($render_template);
        } else {
            $this->view->assign($vars);
            $this->view->assign('items', $items);
            $this->view->assign('phpbb_root_path', $phpbb_root_path);
            $content = $this->view->fetch($render_template);
        }

        if ($write_to_cache and !empty($content)) {
           // attempt to write to cache if not loaded before
            if (!file_exists($cachedir)) {
                mkdir($cachedir, 0777); # attempt to make the dir
            }
            if (!file_put_contents($cachefile, $content)) {
                //echo "<br />Could not save data to cache. Please make sure your cache directory exists and is writable.<br />";
            }
        }

        $lang = ZLanguage::getLanguageCode();
        if (isset($vars['blocktitle_' . $lang]) && !empty($vars['blocktitle_' . $lang])) {
            $blockinfo['title'] = $vars['blocktitle_' . $lang];
        }
		$blockinfo['content'] = $content;

        return BlockUtil::themeBlock($blockinfo);
    }
    
    /**
     * modify block settings ..
     */
    public function modify($blockinfo)
    {
        $languages = ZLanguage::getInstalledLanguages();

        $table_prefix = ModUtil::getVar ('Zphpbb', 'table_prefix', 'phpbb_');

        // get module information
        $modinfo = ModUtil::getInfoFromName("Zphpbb");

        // Get current content
        $vars = BlockUtil::varsFromContent($blockinfo['content']);

        // Defaults
        if (empty($vars['cache_dir'])) {
            $vars['cache_dir'] = "any_cache";
        }
        if (!isset($vars['cache_time'])) {
            $vars['cache_time'] = "120";
        }
        if (!is_array($vars['excluded_forums'])) {
            $vars['excluded_forums'] = array();
        }
        foreach ($languages as $langcode) {
            // Multilingual title of the block
            if (!isset($vars['blocktitle_' . $langcode])) {
                $vars['blocktitle_' . $langcode] = $blockinfo['title'];
            }
        }

        // Create forum list
        $connection = Doctrine_Manager::getInstance()->getCurrentConnection();
        $stmt = $connection->prepare("SELECT f.forum_id, f.forum_name, c.forum_name AS cat_title FROM ".$table_prefix."forums f LEFT JOIN ".$table_prefix."forums c ON c.forum_id = f.parent_id WHERE f.parent_id >0 ORDER BY c.forum_name, f.forum_name");
        $stmt->execute();
        $items = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
        foreach ($items as $item) {
            $selected = !is_array($vars['excluded_forums']) || in_array($item['forum_id'], $vars['excluded_forums']) ? 1 : 0;
            $forums[] = array('id' => $item['forum_id'], 'name' => $item['cat_title'] . ' / ' . $item['forum_name'], 'selected' => $selected);
        }

        $this->view->assign('vars', $vars);
        $this->view->assign('languages', $languages);
        $this->view->assign('forums', $forums);

        return $this->view->fetch('blocks/lastposts_modify.tpl');
    }
    
    /**
     * update block settings
     */
    public function update($blockinfo)
    {
        $languages = ZLanguage::getInstalledLanguages();

        $vars = BlockUtil::varsFromContent($blockinfo['content']);

        $this->view->setCaching(Zikula_View::CACHE_DISABLED);

        // alter the corresponding variable
        $vars['cache_time'] = (int)FormUtil::getPassedValue('cache_time', 0, 'POST');
        $vars['cache_dir'] = FormUtil::getPassedValue('cache_dir', 'any_cache', 'POST');
        $vars['last_X_posts'] = (int)FormUtil::getPassedValue('last_X_posts', 5, 'POST');
        $vars['display_date'] = (bool)FormUtil::getPassedValue('display_date', false, 'POST');
        $vars['display_time'] = (bool)FormUtil::getPassedValue('display_time', false, 'POST');
        $vars['group_topics'] = (bool)FormUtil::getPassedValue('group_topics', false, 'POST');
        $vars['excluded_forums'] = FormUtil::getPassedValue('excluded_forums', null, 'POST');
        $vars['display_text_chars'] = (int)FormUtil::getPassedValue('display_text_chars', 0, 'POST');
        foreach ($languages as $langcode) {
            // Multilingual title of the block
            $vars['blocktitle_' . $langcode] = htmlspecialchars_decode(FormUtil::getPassedValue('blocktitle_' . $langcode));
        }

        // write back the new contents
        $blockinfo['content'] = BlockUtil::varsToContent($vars);
    
        // clear the block cache
        $this->view->clear_cache('blocks/lastposts.tpl');
    
        return $blockinfo;
    }
}