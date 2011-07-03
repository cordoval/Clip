
{* resolve the title depending of any existing filter *}
{if 'null'|in_array:$clipargs.getallapi.filter.category.ops}
    {assign var='op' value='null'}
{elseif 'sub'|in_array:$clipargs.getallapi.filter.category.ops}
    {assign var='op' value='sub'}
{elseif 'eq'|in_array:$clipargs.getallapi.filter.category.ops}
    {assign var='op' value='eq'}
{/if}

<div class="clip-list clip-list-{$pubtype.urltitle}">
    {include file='clip_generic_navbar.tpl'}

    {if $op eq 'null'}
        {* title for uncategorized list *}
        {gt text='Uncategorized' assign='pagetitle'}
        {if !$homepage}{pagesetvar name='title' value="$pagetitle - `$pubtype.title` - `$modvars.ZConfig.sitename`"}{/if}
        <h2>{$pubtype.title}</h2>
        <p>{gt text='Uncategorized pages published:'}</p>
    {elseif $op AND $clipargs.getallapi.filter.category.$op.0}
        {* title for a specific category requested *}
        {category_path id=$clipargs.getallapi.filter.category.$op.0 idcolumn='id' field='display_name' assign='categorytitle'}
        {if isset($categorytitle[$modvars.ZConfig.language_i18n])}
            {assign var='pagetitle' value=$categorytitle[$modvars.ZConfig.language_i18n]}
        {else}
            {category_path id=$clipargs.getallapi.filter.category.$op.0 idcolumn='id' field='name' assign='pagetitle'}
        {/if}
        {if !$homepage}{pagesetvar name='title' value="$pagetitle - `$pubtype.title` - `$modvars.ZConfig.sitename`"}{/if}
        <h2>{gt text='Category: %s' tag1=$pagetitle}</h2>
        <p>{gt text='Pages published under this category:'}</p>
    {else}
        {* generic title *}
        {if !$homepage}{pagesetvar name='title' value="`$pubtype.title` - `$modvars.ZConfig.sitename`"}{/if}
        <h2>{$pubtype.title}</h2>
    {/if}

    <table class="z-datatable clip-list-items">
        <tbody>
            {foreach from=$publist item='pubdata'}
            <tr class="{cycle values='z-even,z-odd'}">
                <td>
                    <a href="{modurl modname='Clip' type='user' func='display' tid=$pubtype.tid pid=$pubdata.core_pid title=$pubdata.core_title|formatpermalink}">{$pubdata.core_title|safetext}</a>
                    <span class="z-sub z-floatright">{gt text='%s read' plural='%s reads' count=$pubdata.core_hitcount tag1=$pubdata.core_hitcount}</span>
                </td>
                <td class="z-right z-nowrap">
                    {strip}
                    <a href="{modurl modname='Clip' type='user' func='display' tid=$pubtype.tid pid=$pubdata.core_pid title=$pubdata.core_title|formatpermalink}">
                        {img modname='core' src='demo.png' set='icons/extrasmall' __title='View' __alt='View'}
                    </a>
                    {clip_accessblock tid=$pubtype.tid pid=$pubdata context='edit'}
                    &nbsp;
                    <a href="{modurl modname='Clip' type='user' func='edit' tid=$pubtype.tid pid=$pubdata.core_pid}">
                        {img modname='core' src='edit.png' set='icons/extrasmall' __title='Edit' __alt='Edit'}
                    </a>
                    {/clip_accessblock}
                    {/strip}
                </td>
            </tr>
            {foreachelse}
            <tr class="z-datatableempty">
                <td>{gt text='No pages found.'}</td>
            </tr>
            {/foreach}
        </tbody>
    </table>

    {if $pager.itemsperpage neq $modvars.Clip.maxperpage}
        {pager display='page' posvar='page' rowcount=$pager.numitems limit=$pager.itemsperpage maxpages=7}
        {*pager display='startnum' posvar='startnum' rowcount=$pager.numitems limit=$pager.itemsperpage maxpages=7*}
    {/if}
</div>
