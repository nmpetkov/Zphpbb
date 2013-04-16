{insert name='getstatusmsg'}

{if $INTYPE eq 'ifrm'}
    <script language="javascript" type="text/javascript">
    function zResizeIframe(obj) {
        obj.style.height = '450px';
        obj.style.height = obj.contentWindow.document.body.scrollHeight + 30 + 'px';
    }
    </script>
    <iframe src="{$iframe_src|safehtml}" onload='javascript:zResizeIframe(this);' scrolling="no" frameborder="0" style="width: 100%; height: 100%; min-height: 450px;">Browser don't support iframes.</iframe>
{else}
    {$content}
{/if}
