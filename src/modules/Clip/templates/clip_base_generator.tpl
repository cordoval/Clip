{ajaxheader module='Clip' filename='Clip.Generator.js' ui=true}

<div class="z-admin-content-pagetitle">
    {img modname='core' src='exec.png' set='icons/small' __title='Show code' alt=''}
    <h3>{$pubtype.title} &raquo; {gt text='Template code'}</h3>
    {clip_adminmenu tid=$pubtype.tid code=$code}
</div>

{insert name='getstatusmsg'}

<hr />

{clip_copytoclipboard id='clip_generatorcode' class='tooltips'}

{switch expr=$code}
    {case expr='form'}
        <h4>{gt text='Form template'}</h4>
        {assign var='cliptplname' value='form_all.tpl'}
    {/case}
    {case expr='list'}
        <h4>{gt text='List template'}</h4>
        {assign var='cliptplname' value='list.tpl'}
    {/case}
    {case expr='display'}
        <h4>{gt text='Display template'}</h4>
        {assign var='cliptplname' value='display.tpl'}
    {/case}
    {case expr='main'}
        <h4>{gt text='Index template'}</h4>
        {assign var='cliptplname' value='main.tpl'}
    {/case}
    {case expr='blocklist'}
        <h4>{gt text='List block template'}</h4>
        {assign var='cliptplname' value='list_block_XYZ.tpl'}
    {/case}
    {case expr='blockpub'}
        <h4>{gt text='Pub block template'}</h4>
        {assign var='cliptplname' value='display_block_XYZ.tpl'}
    {/case}
{/switch}

{if isset($cliptplname)}
<p class="z-informationmsg">
    {gt text='Create a template file named <strong>%1$s</strong> with this code, and store it in the the config directory: <strong>/config/templates/Clip/%2$s/%1$s</strong>, or within your theme in the <strong>/themes/YourTheme/templates/modules/Clip/%2$s/%1$s</strong>.' tag1=$cliptplname tag2=$pubtype.folder}
</p>
{/if}

{switch expr=$code}
    {case expr='main'}
        {assign var='tag1' value='index.php?module=Clip&type=user&func=main&tid=1&template=welcome'|safehtml}
        {capture assign='tag2'}{ldelim}modfunc modname='Clip' type='user' func='display' tid=1 pid=1 template='categories'{rdelim}{/capture}
        <p class="z-informationmsg">
            {gt text='You can also have custom templates like <strong>main_TEMPLATE.tpl</strong> depending of the <var>template</var> parameter passed. . For instance, <code>%1$s</code> will use <strong>main_welcome.tpl</strong>, and <code>%2$s</code> will use <strong>main_categories.tpl</strong>.' tag1=$tag1 tag2=$tag2|safehtml}
        </p>
    {/case}
    {case expr='list'}
        {assign var='tag1' value='index.php?module=Clip&type=user&func=list&tid=1&template=categories'|safehtml}
        {capture assign='tag2'}{ldelim}modfunc modname='Clip' type='user' func='list' tid=1 template='snippet'{rdelim}{/capture}
        <p class="z-informationmsg">
            {gt text='You can also have custom templates like <strong>list_TEMPLATE.tpl</strong> depending of the <var>template</var> parameter passed. For instance, <code>%1$s</code> will use <strong>list_categories.tpl</strong>, and <code>%2$s</code> will use <strong>list_snippet.tpl</strong>.' tag1=$tag1 tag2=$tag2|safehtml}
        </p>
    {/case}
    {case expr='display'}
        {assign var='tag1' value='index.php?module=Clip&type=user&func=display&tid=1&pid=1&template=mini'|safehtml}
        {capture assign='tag2'}{ldelim}modfunc modname='Clip' type='user' func='display' tid=1 pid=1 template='snippet'{rdelim}{/capture}
        <p class="z-informationmsg">
            {gt text='You can also have custom templates like <strong>display_TEMPLATE.tpl</strong> depending of the <var>template</var> parameter passed. For instance, <code>%1$s</code> will use <strong>display_mini.tpl</strong>, and <code>%2$s</code> will use <strong>display_snippet.tpl</strong>.' tag1=$tag1 tag2=$tag2|safehtml}
        </p>
    {/case}
    {case expr='form'}
        <p class="z-informationmsg">
            {gt text='You can also have custom templates like <strong>form_custom_TEMPLATE.tpl</strong> depending of the <var>template</var> parameter passed; or have individual templates according to the current state of the publication workflow <strong>form_STATE.tpl</strong>, for instance: form_initial.tpl, form_approved.tpl, etc.'}
        </p>
    {/case}
    {case expr='blocklist'}
        <p class="z-informationmsg">
            {gt text='<strong>XYZ</strong> is the template name chosen on your block configuration.'}
        </p>
    {/case}
    {case expr='blockpub'}
        <p class="z-informationmsg">
            {gt text='<strong>XYZ</strong> is the template name chosen on your block configuration.'}
        </p>
    {/case}
{/switch}

{* HTML workaround: SCRIPT is the unoque that do not scape HTML nor any Chars *}
<script id="clip_generatorcode" type="text/html">{{$output}}</script>

<pre class="clip-generatorcode">
    {$output|safetext}
</pre>
