{adminheader}
<div class="z-admin-content-pagetitle">
    {icon type="config" size="small"}
    <h3>{gt text='Import'}</h3>
</div>

<form class="z-form" action="{modurl modname="Zphpbb" type="admin" func="import_perform"}" method="post" enctype="application/x-www-form-urlencoded">
    <div>
    <input type="hidden" name="csrftoken" value="{insert name="csrftoken"}" />
    <fieldset>
        <legend>{gt text='Import from phpBB 3.0.11'}</legend>
        <div class="z-informationmsg">
            {gt text="Source tables must have phpBB 3.0.11 structures. If you like to import from ZphpBB2 or PNphpBB2, please convert them to phpBB, and then use this import."}
        </div>
        <div class="z-formrow">
            <label for="phpbb3_tableprefix">Prefix for phpBB tables</label>
            <input id="phpbb3_tableprefix" type="text" name="phpbb3_tableprefix" size="25" value="phpbb3_" />
            <em class="z-sub z-formnote">{gt text="Table prefix for this module is "}{$modvars.Zphpbb.table_prefix}. {gt text="Specified table prefix must be different."}</em>
        </div>
    </fieldset>
    <div class="z-buttons z-formbuttons">
        {button src="button_ok.png" set="icons/extrasmall" __alt="Import" __title="Import" __text="Import"}
        <a href="{modurl modname="Zphpbb" type="admin" func='import'}" title="{gt text="Cancel"}">{img modname=core src="button_cancel.png" set="icons/extrasmall" __alt="Cancel" __title="Cancel"} {gt text="Cancel"}</a>
    </div>
    </div>
</form>

<form class="z-form" action="{modurl modname="Zphpbb" type="admin" func="avatar_check"}" method="post" enctype="application/x-www-form-urlencoded">
    <div>
    <input type="hidden" name="csrftoken" value="{insert name="csrftoken"}" />
    <fieldset>
        <legend>{gt text='Check uploaded avatars'}</legend>
        <div class="z-informationmsg">
            {gt text="Integrates avatars between upload folder and database. To perform check only leave checkboxes unchecked."}<br />
            Upload avatar path: {$phpbb_root_path}{$board_config.avatar_path}
        </div>
        <div class="z-formrow">
            <label for="avatars_renamewrong">Rename wrong named</label>
            <input type="checkbox" value='1' id="avatars_renamewrong" name="avatars_renamewrong" />
            <em class="z-sub z-formnote">{gt text="These avatars are imported from phpBB2, but remained with old naming convention."}</em>
        </div>
        <div class="z-formrow">
            <label for="avatars_deleteorphaned">Delete orphaned</label>
            <input type="checkbox" value='1' id="avatars_deleteorphaned" name="avatars_deleteorphaned" />
            <em class="z-sub z-formnote">{gt text="This avatars are not found in database and are unusable."}</em>
        </div>
    </fieldset>
    <div class="z-buttons z-formbuttons">
        {button src="button_ok.png" set="icons/extrasmall" __alt="Perform" __title="Perform" __text="Perform"}
    </div>
    </div>
</form>
{adminfooter}