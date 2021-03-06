
<div class="clip-edit clip-edit-{$pubtype.urltitle} clip-edit-{$pubtype.urltitle}-{$clipargs.edit.state}">
    {include file='generic_navbar.tpl'}

    <h2>
        {if $pubdata.id}
            {gt text='Edit post'}
        {else}
            {gt text='New post'}
        {/if}
    </h2>

    {form cssClass='z-form z-form-light' enctype='multipart/form-data'}
        <div>
            {formvalidationsummary}
            <fieldset class="z-linear">
                <div class="z-formrow">
                    {clip_form_plugin field='title' cssClass='z-form-text-big'}
                    <span class="z-formnote z-sub">{gt text='Enter title here'}</span>
                </div>

                <div class="z-formrow">
                    {clip_form_label for='content' __text='Content'}
                    {clip_form_plugin field='content' maxLength='65535' rows='25' cols='70'}
                </div>

                <div class="z-formrow">
                    {clip_form_label for='summary' __text='Summary'}
                    {clip_form_plugin field='summary' maxLength='65535' rows='4' cols='70'}
                    <span class="z-formnote z-sub">{gt text='Optional hand-crafted summary of your content that can be used in your templates.'}</span>
                </div>

                <div class="z-formrow">
                    {clip_form_label for='category' __text='Category'}
                    {clip_form_plugin field='category'}
                </div>
            </fieldset>

            {if $relations}
            <fieldset>
                <legend>{gt text='Related publications'}</legend>

                {foreach from=$relations key='field' item='item' name='relations'}
                <div class="z-formrow">
                    {clip_form_label for=$field text=$item.title|clip_translate}
                    {clip_form_relation field=$field relation=$item minchars=2 op='likefirst'}
                </div>
                {/foreach}

            </fieldset>
            {/if}

            <fieldset>
                <legend>{gt text='Post options'}</legend>

                <div class="z-formrow">
                    {clip_form_label for='core_language' __text='Language'}
                    {clip_form_plugin field='core_language' mandatory=false}
                </div>

                <div class="z-formrow">
                    {clip_form_label for='core_publishdate' __text='Publish date'}
                    {clip_form_plugin field='core_publishdate' includeTime=true}
                    <em class="z-formnote z-sub">{gt text='Leave blank if you do not want to schedule the publication'}</em>
                </div>

                <div class="z-formrow">
                    {clip_form_label for='core_expiredate' __text='Expire date'}
                    {clip_form_plugin field='core_expiredate' includeTime=true}
                    <em class="z-formnote z-sub">{gt text='Leave blank if you do not want the plublication expires'}</em>
                </div>

                <div class="z-formrow">
                    {clip_form_label for='core_visible' __text='Visible'}
                    {clip_form_plugin field='core_visible'}
                    <em class="z-formnote z-sub">{gt text='If not visible, will be excluded from lists and search results'}</em>
                </div>

                <div class="z-formrow">
                    {clip_form_label for='core_locked' __text='Locked'}
                    {clip_form_plugin field='core_locked'}
                    <em class="z-formnote z-sub">{gt text='If enabled, the publication will be closed for changes'}</em>
                </div>
            </fieldset>

            <div class="clip-hooks-edit">
                {notifydisplayhooks eventname=$pubtype->getHooksEventName('form_edit') id=$pubdata.core_uniqueid}
            </div>

            <div class="z-buttons">
                {foreach item='action' from=$actions}
                    {formbutton commandName=$action.id text=$action.title zparameters=$action.parameters.button|default:''}
                {/foreach}
                <input class="clip-bt-reload" type="reset" value="{gt text='Reset'}" title="{gt text='Reset the form to its initial state'}" />
                {formbutton commandName='cancel' __text='Cancel' class='z-bt-cancel'}
            </div>
        </div>
    {/form}
</div>
