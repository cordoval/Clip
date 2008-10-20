<?php
/**
 * PageMaster
 *
 * @copyright (c) 2008, PageMaster Team
 * @link        http://code.zikula.org/pagemaster/
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @version     $ Id $
 * @package     Zikula_3rd_party_Modules
 * @subpackage  pagemaster
 */

require_once('system/pnForm/plugins/function.pnformdropdownlist.php');

class pnFormPluginType extends pnFormDropdownList
{
    function getFilename()
    {
        return __FILE__; // FIXME: may be found in smarty's data???
    }

    function __construct()
    {
        $this->autoPostBack = true;
        $plugins = pagemasterGetPluginsOptionList();

        foreach ($plugins as $plugin) {
            $items[] = array (
                'text'  => $plugin['plugin']->title,
                'value' => $plugin['file']
            );
        }
        $this->items = $items;

        parent::__construct();
    }

    function render($render)
    {
        $result = parent::render($render);
        $typeDataHtml = '';
        if ($this->selectedValue <> '' || ($this->selectedValue == '' && !empty($this->items))) {
            if ($this->selectedValue == '') {
                $this->selectedValue = $this->items[0]['value'];
            }

            if (!file_exists('javascript/livepipe/livepipe.js') || !file_exists('javascript/livepipe/livepipe.css') ||  !file_exists('javascript/livepipe/window.js')) {
                LogUtil::registerError(pnML('_PAGEMASTER_LIVEPIPE_NOTFOUND', null, true));
            } else {
                PageUtil::addVar('javascript', 'javascript/livepipe/livepipe.js');
                PageUtil::addVar('javascript', 'javascript/livepipe/window.js');
                PageUtil::addVar('stylesheet', 'javascript/livepipe/livepipe.css');
            }

            $script =  "<script type=\"text/javascript\">\n//<![CDATA[\n";

            $plugin = pagemasterGetPlugin($this->selectedValue);
            if (method_exists($plugin, 'getTypeHtml'))
            {    
                if (method_exists($plugin, 'getSaveTypeDataFunc')) {
                    $script .= $plugin->getSaveTypeDataFunc($this);
                } else {
                    $script .= 'function saveTypeData(){ closeTypeData(); }';
                }
                // init functions for modalbox and unobtrusive buttons 
                $script .= '
                function closeTypeData() {
                    pm_modalbox.close();
                }
                function pm_enablePluginConfig(){
                    $(\'saveTypeButton\').observe(\'click\', saveTypeData);
                    $(\'cancelTypeButton\').observe(\'click\', closeTypeData);
                    pm_modalbox = new Control.Modal($(\'showTypeButton\'), {
                        overlayOpacity: 0.6,
                        className: \'modal\',
                        fade: true,
                        iframeshim: false,
                        closeOnClick: false
                    });
                    $(document.body).insert($(\'typeDataDiv\'));
                }
                Event.observe( window, \'load\', pm_enablePluginConfig, false);
                ';

                $typeDataHtml  = '
                <a id="showTypeButton" href="#typeDataDiv"><img src="images/icons/extrasmall/utilities.gif" alt="' . _MODIFYCONFIG .'" /></a>
                <div id="typeDataDiv" class="modal">
                    <div>'.$plugin->getTypeHtml($this, $render).'</div>
                    <div>
                        <button type="button" id="saveTypeButton" name="saveTypeButton"><img src="images/icons/extrasmall/filesave.gif" alt="' . _SAVE . '" /></button>&nbsp;
                        <button type="button" id="cancelTypeButton" name="cancelTypeButton"><img src="images/icons/extrasmall/button_cancel.gif" alt="' . _CANCEL . '" /></button>
                    </div>
                </div>';
            } else {
                $script .= 'Event.observe( window, \'load\', function() { $(\'typedata\').hide(); }, false);';
            }
            $script .= "\n// ]]>\n</script>"; 
            PageUtil::setVar('rawtext', $script);
        }
        return $result . $typeDataHtml;
    }
}

function smarty_function_pnformplugintype($params, &$render) {
    return $render->pnFormRegisterPlugin('pnFormPluginType', $params);
}
