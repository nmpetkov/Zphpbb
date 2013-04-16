{adminheader}
<div class="z-admin-content-pagetitle">
    {icon type="info" size="small"}
    <h3>{gt text='Welcome to Zphpbb'}</h3>
</div>

<div>
    <p>
        <a href="https://github.com/nmpetkov/Zphpbb" target="_blank"><b>{gt text="Project page"}</b></a>
        &nbsp;|&nbsp;
        <a href="{modurl modname='Zphpbb' type='user' func='main'}"><b>{gt text="Frontend"}</b></a>
        &nbsp;|&nbsp;
        <a href="{modurl modname='Zphpbb' type='admin' func='updateAccounts'}"><b>{gt text="Update accounts"}</b></a>
        &nbsp;|&nbsp;
        <a href="{modurl modname='Zphpbb' type='admin' func='deletecache'}"><b>{gt text="Delete cache"}</b></a>
    </p>
</div>
<br />
<iframe src="{$iframe_src|safehtml}" style="width: 100%; height: 1500px;"></iframe>
{adminfooter}