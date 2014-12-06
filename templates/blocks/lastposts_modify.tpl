{foreach from=$languages key='lang' item='langcode'}
{assign_concat name='varname' 1='blocktitle_' 2=$langcode}
<div class="z-formrow">
    <label for="blocktitle_{$langcode}">{gt text="Block title"} ({$langcode})</label>
    <input id="blocktitle_{$langcode}" type="text" name="blocktitle_{$langcode}" value="{$vars.$varname|htmlspecialchars}" />
</div>
{/foreach}
<div class="z-formrow">
    <label for="cache_time">{gt text='Cache time (enter positive number in seconds to enable cache)'}</label>
    <input id="cache_time" type="text" name="cache_time" size="10" maxlength="50" value="{$vars.cache_time|safetext}" />
</div>
<div class="z-formrow">
    <label for="cache_dir">{gt text='Cache directory name (within Zikula Temp directory)'}</label>
    <input id="cache_dir" type="text" name="cache_dir" size="30" maxlength="255" value="{$vars.cache_dir|safetext}" />
</div>
<div class="z-formrow">
    <strong>{gt text='Display Last Forum Posts in block'}</strong>
</div>
<div class="z-formrow">
    <label for="last_X_posts">{gt text='Number of entries to display'}</label>
    <input id="last_X_posts" type="text" name="last_X_posts" value="{$vars.last_X_posts|safehtml}" size="5" maxlength="5" />
</div>
<div class="z-formrow">
    <label for="display_text_chars">{gt text='Number of characters to display from text'}</label>
    <input id="display_text_chars" type="text" name="display_text_chars" value="{$vars.display_text_chars|safehtml}" size="5" maxlength="5" />
</div>
<div class="z-formrow">
    <label for="display_date">{gt text='Display Date'}</label>
    <input id="display_date" type="checkbox" name="display_date" value="1"{if $vars.display_date} checked="checked"{/if} />
</div>
<div class="z-formrow">
    <label for="display_time">{gt text='Display Time'}</label>
    <input id="display_time" type="checkbox" name="display_time" value="1"{if $vars.display_time} checked="checked"{/if} />
</div>
<div class="z-formrow">
    <label for="group_topics">{gt text='Show only the last post of every topic'}</label>
    <input id="group_topics" type="checkbox" name="group_topics" value="1"{if $vars.group_topics} checked="checked"{/if} />
</div>
<div class="z-formrow">
    <label for="excluded_forums">{gt text='Do not show posts of the following forums (CTRL+LEFT CLICK sets/clears selections)'}</label>
    <select id="excluded_forums" name="excluded_forums[]" size="8" multiple="multiple">
        {foreach from=$forums item='forum'}
        <option value="{$forum.id}"{if $forum.selected} selected="selected"{/if}>{$forum.name|safetext}</option>
		{/foreach}
    </select>
</div>
