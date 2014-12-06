{adminheader}
<div class="z-admin-content-pagetitle">
    {icon type="config" size="small"}
    <h3>{gt text='Module settings'}</h3>
</div>

<form class="z-form" action="{modurl modname="Zphpbb" type="admin" func="updateconfig"}" method="post" enctype="application/x-www-form-urlencoded">
    <div>
    <input type="hidden" name="csrftoken" value="{insert name="csrftoken"}" />
    <fieldset>
        <legend>{gt text='General settings'}</legend>
        <div class="z-formrow">
            <label for="table_prefix">{gt text='Table prefix'}</label>
            <input id="table_prefix" type="text" name="table_prefix" value="{$table_prefix|safetext}" />
            <em class="z-sub z-formnote">{gt text='This will change module table prefix in database. Must contain <b>phpbb</b>.'}</em>
        </div>
        <div class="z-formrow">
            <label for="login_usezikula">{gt text='Use main site login screen'}</label>
            <input type="checkbox" value='1' id="login_usezikula" name="login_usezikula"{if $login_usezikula eq '1'} checked="checked"{/if}/>
            <em class="z-sub z-formnote">{gt text='You can also use phpBB login screen. No difference, except that will not be auto-logged in main site.'}</em>
        </div>
        <div class="z-formrow">
            <label for="display_phpbbfooter">{gt text='Display phpBB footer'}</label>
            <input type="checkbox" value='1' id="display_phpbbfooter" name="display_phpbbfooter"{if $display_phpbbfooter eq '1'} checked="checked"{/if}/>
        </div>
        <div class="z-formrow">
            <label for="display_rightsadmins">{gt text='Display user rights section and team link'}</label>
            <input type="checkbox" value='1' id="display_rightsadmins" name="display_rightsadmins"{if $display_rightsadmins eq '1'} checked="checked"{/if}/>
        </div>
        <div class="z-formrow">
            <label for="display_zichatlink">{gt text='Display Zichat link'}</label>
            <input type="checkbox" value='1' id="display_zichatlink" name="display_zichatlink"{if $display_zichatlink eq '1'} checked="checked"{/if}/>
        </div>
    </fieldset>
    <fieldset>
        <legend>{gt text='Permissions'}</legend>
            <div class="z-formrow">
                <label for="user_groups">{gt text='Available user groups'}</label>
                <span>
                <select name="user_groups" id="user_groups" size="{$groups.count}" disabled="disabled">
                    {foreach item=group from=$groups}
                        <option value="{$group.gid|safetext}">{$group.gid|safetext}  -  {$group.name|safetext}  -  {$group.description|safetext}</option>
                    {/foreach}
                </select>
                </span>
            </div>
            <div class="z-formrow">
                <label for="zphpbb_admingroups">{gt text='Groups with admin roles'}</label>
                <input type="text" id="zphpbb_admingroups" name="zphpbb_admingroups" value="{$zphpbb_admingroups|safetext}" />
                <p class="z-formnote z-sub">{gt text='Comma separated list of user groups IDs.'}</p>
            </div>
            <div class="z-formrow">
                <label for="zphpbb_moderatorgroups">{gt text='Groups with moderator roles'}</label>
                <input type="text" id="zphpbb_moderatorgroups" name="zphpbb_moderatorgroups" value="{$zphpbb_moderatorgroups|safetext}" />
                <p class="z-formnote z-sub">{gt text='Comma separated list of user groups IDs.'}</p>
            </div>
    </fieldset>
    <fieldset>
        <legend>{gt text='Forum SEO metatags'}</legend>
        <div class="z-informationmsg nl-round">
            {gt text='This information is for SEO and are metatags inserted in head section of generated HTML.'}
        </div>
        <div class="z-formrow">
            <label for="page_title">{gt text='Page title'}</label>
            <input id="page_title" type="text" name="page_title" value="{$page_title|safetext}" />
        </div>
        <div class="z-formrow">
            <label for="page_description">{gt text='Page description'}</label>
            <input id="page_description" type="text" name="page_description" value="{$page_description|safetext}" />
        </div>
        <div class="z-formrow">
            <label for="page_robots">{gt text='Robots'}</label>
            <input id="page_robots" type="text" name="page_robots" value="{$page_robots|safetext}" />
            <em class="z-sub z-formnote">{gt text='To index: <i>index, follow</i>, to prevent indexing: <i>noindex, nofollow, noarchive</i>'}</em>
        </div>
    </fieldset>
    <div class="z-buttons z-formbuttons">
        {button src="button_ok.png" set="icons/extrasmall" __alt="Save" __title="Save" __text="Save"}
        <a href="{modurl modname="Zphpbb" type="admin" func='modifyconfig'}" title="{gt text="Cancel"}">{img modname=core src="button_cancel.png" set="icons/extrasmall" __alt="Cancel" __title="Cancel"} {gt text="Cancel"}</a>
    </div>
    </div>
</form>
{adminfooter}