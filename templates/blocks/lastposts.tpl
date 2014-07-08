<ul style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
    {foreach from=$items item=item}
    <li>
        {assign var='postid' value='#p'|cat:$item.post_id}
        <a class="z-normal" href="{modurl modname='Zphpbb' type='user' func='viewtopic' t=$item.topic_id|cat:$postid}" title="{gt text='Goto post'}">
        <img style="display:inline;" src="{$item.replyicon}" alt="{gt text='Goto post'}" />
        {$item.topic_title|strip_tags:false|truncate:65|safehtml}
        <br />
        {gt text='by'} {$item.post_username|safetext}
        {* &nbsp;({$item.post_replies}) *}
        {if $display_date or $display_time}
            {if $display_date}
                {gt text='on'} {$item.post_time|dateformat}
            {/if}
            {if $display_time}
                {gt text='at'} {$item.post_time|dateformat:'%H:%M'}
            {/if}
        {/if}
        {if $display_text_chars}
            <br />{$item.post_text|strip_tags:false|truncate:$display_text_chars|safetext}
        {/if}
        </a>
    </li>
    {/foreach}
</ul>
