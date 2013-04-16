<?php
/**
 * Newletter Plugin - Zphpbb
 */
class Zphpbb_NewsletterPlugin_Zphpbb extends Newsletter_AbstractPlugin
{
    public function getTitle()
    {
        return $this->__('Latest forum posts');
    }

    public function getDescription()
    {
        return $this->__('Displays a list of the latest forum topics.');
    }

    // $filtAfterDate is null if is not set, or in format yyyy-mm-dd hh:mm:ss
    function getPluginData($filtAfterDate=null)
    {
        if (!$this->pluginAvailable()) {
            return array();
        }

        if (!SecurityUtil::checkPermission('Zphpbb::', '::', ACCESS_READ, $this->userNewsletter)) {
            return array();
        }
        //ModUtil::load('Zphpbb');
        $table_prefix = ModUtil::getVar ('Zphpbb', 'table_prefix', 'phpbb_');
        $TOPICS_TABLE = $table_prefix."topics";
        $POSTS_TABLE = $table_prefix."posts";
        $FORUMS_TABLE = $table_prefix."forums";

        $connection = Doctrine_Manager::getInstance()->getCurrentConnection();
        $sql = "SELECT forum_id, forum_name FROM $FORUMS_TABLE WHERE forum_type>0 AND forum_status=0 ";
        $stmt = $connection->prepare($sql);
        try {
            $stmt->execute();
        } catch (Exception $e) {
            return LogUtil::registerError(__('Error in plugin').' Zphpbb: ' . $e->getMessage());
        }

        $userforums = $stmt->fetchAll(Doctrine_Core::FETCH_ASSOC);
        $allowedforums = array();
        foreach (array_keys($userforums) as $k) {
            if (SecurityUtil::checkPermission('Zphpbb::', ":".$userforums[$k]['forum_id'].":", ACCESS_READ, $this->userNewsletter)) {
                $allowedforums[] = $userforums[$k]['forum_id'];
            }
        }
        if (count($allowedforums)==0) {
            // user is not allowed to read any forum at all
            return array();
        }

        $sql = "SELECT $TOPICS_TABLE.topic_title, $TOPICS_TABLE.topic_replies, $TOPICS_TABLE.topic_views, $TOPICS_TABLE.topic_id, 
                     $POSTS_TABLE.post_id, $POSTS_TABLE.poster_id, $POSTS_TABLE.post_time, $POSTS_TABLE.post_subject, $POSTS_TABLE.post_text, 
                     $FORUMS_TABLE.forum_name 
                     FROM $TOPICS_TABLE 
                     INNER JOIN $POSTS_TABLE ON $POSTS_TABLE.topic_id = $TOPICS_TABLE.topic_id 
                     INNER JOIN $FORUMS_TABLE ON $FORUMS_TABLE.forum_id = $TOPICS_TABLE.forum_id";
        $sql .= " WHERE $TOPICS_TABLE.forum_id IN (" . implode(',', $allowedforums) . ")";
        if ($filtAfterDate) {
            $sql .= " AND FROM_UNIXTIME(post_time)>='".$filtAfterDate."'";
        }
        $sql .= " ORDER BY post_time DESC LIMIT ".$this->nItems;
        $stmt = $connection->prepare($sql);
        try {
            $stmt->execute();
        } catch (Exception $e) {
            return LogUtil::registerError(__('Error in plugin').' Zphpbb: ' . $e->getMessage());
        }
        $items = $stmt->fetchAll(Doctrine_Core::FETCH_BOTH);

        foreach (array_keys($items) as $k) {
            $items[$k]['topicurl'] = ModUtil::url('Zphpbb', 'user', 'viewtopic', array('t' => $items[$k]['topic_id']));
            $items[$k]['posturl'] = ModUtil::url('Zphpbb', 'user', 'viewtopic', array('p' => $items[$k]['post_id'].'#'.$items[$k]['post_id']));
            $items[$k]['postdate'] = DateUtil::getDatetime($items[$k]['post_time']);
            $items[$k]['username']= UserUtil::getVar('uname', $items[$k]['poster_id']);

            $items[$k]['nl_title'] = $items[$k]['topic_title'];
            $items[$k]['nl_url_title'] = System::getBaseUrl().$items[$k]['posturl'];
            $items[$k]['nl_content'] = $items[$k]['forum_name'].', '.$items[$k]['username']."<br />\n".$items[$k]['post_text'];
            $items[$k]['nl_url_readmore'] = $items[$k]['nl_url_title'];
        }

        return $items;
    }
}
