<?php

function pagemaster_operation_createNewRevision(&$obj, $params)
{
    $online = isset($params['online']) ? $params['online'] : false;
    $obj['core_online'] = $online;
    
	if (!isset($params['nextstate']))
    	return;
    	
	$nextState = $params['nextstate'];
	
	if ($online == 1){
    	//set all other to offline
    	$data = array('core_online' => 0);
    	$result = DBUtil::updateObject($data, $obj['__WORKFLOW__']['obj_table'], 'pm_online = 1 and pm_pid = '.$obj['core_pid']);
	}
	$new_rev = $obj;
	unset($new_rev['id']);
	DBUtil::insertObject($new_rev, $obj['__WORKFLOW__']['obj_table'], 'id');
	$new_rev['__WORKFLOW__']['obj_id'] = $new_rev['id'];
	unset($new_rev['__WORKFLOW__']['id']);
	$workflow = new pnWorkflow($obj['__WORKFLOW__']['schemaname'],'pagemaster');
    $ret = $workflow->registerWorkflow($new_rev, $nextState);
    $revision = array('tid' => $obj['tid'], 'id' => $new_rev['id'], 'pid' => $obj['core_pid'], 'prevversion' => 1 );
    return DBUtil::insertObject($revision, 'pagemaster_revisions');

}

?>