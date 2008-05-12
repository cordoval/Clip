<?php
include_once ('modules/pagemaster/common.php');

/**
 * pnForm handler for updating pubdata tables.
 *
 * @author kundi
 */
class pagemaster_user_dynHandler {

	var $tid;
	var $core_pid;
	var $id;
	var $pubfields;
	var $pubtype;
	var $tablename;
	var $goto;

	function initialize(& $pnRender) {

		$this->goto = FormUtil :: getPassedValue('goto', '');
		if ($this->id <> '') {
			$pubdata = DBUtil :: selectObjectByID($this->tablename, $this->id, 'id');
			$this->core_pid = $pubdata['core_pid'];
			$actions = WorkflowUtil :: getActionsForObject($pubdata, $this->tablename, 'id', 'pagemaster');
		} else {
			$actions = WorkflowUtil :: getActionsByState(str_replace('.xml', '', $this->pubtype['workflow']), 'pagemaster');
		}
		//check for set_ default values
		foreach ($this->pubfields as $field)
		{
			$fieldName = "set_$field[name]";
			$val = FormUtil :: getPassedValue($fieldName, '');
			if ($val <> '')
			$pubdata[$field['name']] = $val;
		}


		if (count($pubdata > 0))
		$pnRender->assign($pubdata);
		$pnRender->assign('actions', $actions);
		return true;
	}
	function handleCommand(& $pnRender, & $args) {
		if (!$pnRender->pnFormIsValid())
		return false;
		$data = $pnRender->pnFormGetValues();
		$data['tid'] = $this->tid;
		$data['id'] = $this->id;
		$data['core_pid'] = $this->core_pid;
		$data = pnModAPIFunc('pagemaster', 'user', 'editPub', array (
			'data' => $data,
			'commandName' => $args['commandName'],
			'pubfields' => $this->pubfields,
			'schema' => str_replace('.xml', '', $this->pubtype['workflow'])
		));
		if ($this->goto == '')
		{$this->goto = pnModURL('pagemaster', 'user', 'viewpub', array (
			'tid' => $this->tid,'pid' => $data['core_pid']
		));}
		elseif ($this->goto == 'stepmode') //stepmode can be used to go automaticaly from one workflowstep to the next
		{$this->goto = pnModURL('pagemaster', 'user', 'pubedit', array (
			'tid' => $this->tid,'pid' => $data['core_pid'],'goto' => 'stepmode'
			));}
			if (empty($data))
			return false;
			else
			return $pnRender->pnFormRedirect($this->goto);
	}

}

/**
 * Executes a Workflow command over a direct URL Request
 *
 * @param $args['tid']
 * @param $args['id']
 * @param $args['goto'] redirect to after execution
 * @param $args['schema'] optional workflow shema
 * @param $args['commandName'] commandName
 * @author kundi
 */
function pagemaster_user_executecommand() {

	$tid = FormUtil :: getPassedValue('tid');
	$id = FormUtil :: getPassedValue('id');
	$commandName = FormUtil :: getPassedValue('commandName');
	$schema = FormUtil :: getPassedValue('schema');
	$goto = FormUtil :: getPassedValue('goto');

	if ($tid == '')
	return LogUtil :: registerError("Missing argument 'tid'");
	if (!isset ($id))
	return LogUtil :: registerError("Missing argument 'id'");
	if ($commandName == '')
	return LogUtil :: registerError("Missing argument 'commandName'");

	if ($schema == '') {
		$pubtype = DBUtil :: selectObjectByID("pagemaster_pubtypes", $tid, 'tid');
		$schema = str_replace('.xml', '', $pubtype['workflow']);
	}

	$tablename = "pagemaster_pubdata" . $tid;
	$pub = DBUtil :: selectObjectByID($tablename, $id, 'id');
	if (!$pub)
	return LogUtil :: registerError("Publication not found");

	WorkflowUtil :: executeAction($schema, $pub, $commandName, "pagemaster_pubdata" . $tid, 'pagemaster');
	
	if ($goto <> ''){
		if ($goto == 'edit'){
			return pnRedirect(pnModURL('pagemaster', 'user', 'pubedit',array('tid'=>$tid,'id'=>$id)));
		}
		elseif ($goto == 'show')
			return pnRedirect(pnModURL('pagemaster', 'user', 'viewpub',array('tid'=>$tid,'id'=>$id)));
		else{
			return pnRedirect($goto);
		}
	}
	else
	return false ;
}

/**
 * Edit/Create a publication
 *
 * @param $args['tid']
 * @param $args['id']
 * @author kundi
 */
function pagemaster_user_pubedit() {

	$tid = FormUtil :: getPassedValue('tid');
	$id = FormUtil :: getPassedValue('id');
	$pid = FormUtil :: getPassedValue('pid');

	if ($tid == '')
	return LogUtil :: registerError("Missing argument 'tid'");

	//overview permission check, to hide input fields for disallowed users
	if (!SecurityUtil :: checkPermission('pagemaster:input:', 'tid::', ACCESS_EDIT))
	return LogUtil :: registerError(_NOT_AUTHORIZED);

	$pubfields = DBUtil :: selectObjectArray("pagemaster_pubfields", "pm_tid = $tid", 'pm_lineno');
	$pubtype = DBUtil :: selectObjectByID("pagemaster_pubtypes", $tid, 'tid');

	$pnRender = FormUtil :: newpnForm('pagemaster');
	$dynHandler = new pagemaster_user_dynHandler();


	if ($id == '' and $pid <>'') {
		$id = pnModAPIFunc('pagemaster', 'user', 'getId', array (
			'tid' => $tid,
			'pid' => $pid
		));
	}
	$dynHandler->tid = $tid;
	$dynHandler->id = $id;
	$dynHandler->pubfields = $pubfields;
	$dynHandler->pubtype = $pubtype;
	$dynHandler->tablename = "pagemaster_pubdata" . $tid;

	//get actual state for selecting pnForm Template
	if ('id' <> '') {
		$obj = array (
			'id' => $id
		);
		WorkflowUtil :: getWorkflowForObject($obj, $dynHandler->tablename, 'id', 'pagemaster');
		$stepname = $obj['__WORKFLOW__']['state'];
	}

	$user_defined_template = 'input/pubedit_' . $pubtype['formname'] . '_' . $stepname . '.htm';
	if ($pnRender->get_template_path($user_defined_template))
	return $pnRender->pnFormExecute($user_defined_template, $dynHandler);
	else {
		LogUtil :: registerStatus('template not found: ' . $user_defined_template);
		$user_defined_template = 'input/pubedit_' . $pubtype['formname'] . '_all.htm';
		if ($pnRender->get_template_path($user_defined_template))
		return $pnRender->pnFormExecute($user_defined_template, $dynHandler);
		else {
			global $editpub_template_code;
			$editpub_template_code = generate_editpub_template_code($tid, $pubfields, $pubtype);
			//TODO delete all the time, even if it's not needed
			$pnRender->force_compile = true;
			return $pnRender->pnFormExecute('var:editpub_template_code', $dynHandler);
		}

	}
}
/**
 * List of publications
 *
 * @param $args['tid']
 * @author kundi
 */
function pagemaster_user_main($args) {

	$tid = isset ($args['tid']) ? $args['tid'] : FormUtil :: getPassedValue('tid');
	$startnum = isset ($args['startnum']) ? $args['startnum'] : FormUtil :: getPassedValue('startnum');
	$filter = isset ($args['filter']) ? $args['filter'] : FormUtil :: getPassedValue('filter');
	$orderby = isset ($args['orderby']) ? $args['orderby'] : FormUtil :: getPassedValue('orderby');
	$template = isset ($args['template']) ? $args['template'] : FormUtil :: getPassedValue('template');
	$getApprovalState = isset ($args['getApprovalState']) ? $args['getApprovalState'] : FormUtil :: getPassedValue('getApprovalState');
	$handlePluginFields = isset ($args['handlePluginFields']) ? $args['handlePluginFields'] : FormUtil :: getPassedValue('handlePluginFields');
		
	if ($getApprovalState == '')
		$getApprovalState = true;	
	if ($handlePluginFields == '')
		$handlePluginFields = true;
		
	if ($tid == '')
	return LogUtil :: registerError("Missing argument 'tid'");

	$pubtype = DBUtil :: selectObjectByID("pagemaster_pubtypes", $tid, 'tid');

	if(isset($args['itemsperpage']))
	$itemsperpage = $args['itemsperpage'];
	elseif(FormUtil :: getPassedValue('itemsperpage') <> '')
	$itemsperpage = FormUtil :: getPassedValue('itemsperpage');
	else
	$itemsperpage = $pubtype['itemsperpage'];



	if ($template == '') {
		if ($pubtype['filename'] <> '') {
			//template comes from pubtype
			$sec_template = $pubtype['filename'];
			$template = 'output/publist_' . $pubtype['filename'] . '.htm';
		} else {
			//standart template
			$template = 'publist_template.htm';
			//do not check permission for dynamic template
			$sec_template = '';
		}
	} else {
		//template comes from parameter
		$sec_template = $template;
		$template = 'output/publist_' . $template . '.htm';
	}

	if (!SecurityUtil :: checkPermission('pagemaster:list:', "$tid:$sec_template", ACCESS_READ))
	return LogUtil :: registerError(_NOT_AUTHORIZED . ' pagemaster:list:  -  ' . "$tid:$sec_template");

	if ($filter <> '')
	$cacheid = 'publist' . $tid.'|'.$filter;
	else
	$cacheid = 'publist' . $tid.'|nofilter';

	if ($startnum == '')
	$cacheid .= '|nostartnum';
	else
	$cacheid .= '|'.$startnum;

	$pnRender = pnRender :: getInstance('pagemaster', $pubtype['cachetid'], $cacheid, true);
	if ($pubtype['cachetid'])
	{
		$pnRender->caching = true;
		$pnRender->cache_lifetime = $pubtype['cachelifetime'];
		if ($pnRender->is_cached($template, $cacheid)) {
			return $pnRender->fetch($template, $cacheid);
		}
	}else
	$pnRender->caching = false;

	$orderby = createOrderBy($orderby);
	$pubfields = DBUtil :: selectObjectArray("pagemaster_pubfields", "pm_tid = $tid");

	if ($itemsperpage <> 0)
	$countmode = 'both';
	else
	$countmode = 'no';

	$pubarr = pnModAPIFunc('pagemaster', 'user', 'pubList', array (
		'tid' => $tid,
		'pubfields' => $pubfields,
		'pubtype' => $pubtype,
		'countmode' => $countmode,
		'startnum' => $startnum,
		'filter' => $filter,
		'orderby' => $orderby,
		'itemsperpage' => $itemsperpage,
		'checkPerm' => false, //allready checked
		'handlePluginFields' => $handlePluginFields,
		'getApprovalState' => $getApprovalState
	));

	$publist = $pubarr['publist'];
	$pubcount = $pubarr['pubcount'];

	$core_title = getTitleField($pubfields);

	if ($itemsperpage <> 0) {
		$pnRender->assign('pager', array (
			'numitems' => $pubcount,
			'itemsperpage' => $itemsperpage
		));
	}
	$pnRender->assign('publist', $publist);
	$pnRender->assign('core_titlefield', $core_title);
	$pnRender->assign('tid', $tid);

	//check if template is available
	if ($template <> 'publist_template.htm')
	if (!$pnRender->get_template_path($template)) {
		LogUtil :: registerStatus('template not found: ' . $template);
		$template = 'publist_template.htm';
	}


	return $pnRender->fetch($template, $cacheid);

}

/**
 * View a publication
 *
 * @param $args['tid']
 * @param $args['pid']
 * @param $args['id'] (optional)
 * @param $args['template'] (optional)
 * @author kundi
 */
function pagemaster_user_viewpub($args) {

	$tid = isset ($args['tid']) ? $args['tid'] : FormUtil :: getPassedValue('tid');
	$pid = isset ($args['pid']) ? $args['pid'] : FormUtil :: getPassedValue('pid');
	$id = isset ($args['id']) ? $args['id'] : FormUtil :: getPassedValue('id');
	$template = isset ($args['template']) ? $args['template'] : FormUtil :: getPassedValue('template');

	if ($tid == '')
	return LogUtil :: registerError("Missing argument 'tid'");
	if ((!isset ($pid)) and (!isset ($id)))
	return LogUtil :: registerError("Missing argument 'id' or 'pid'");

	$pubtype = DBUtil :: selectObjectByID("pagemaster_pubtypes", $tid, 'tid');
	$pubfields = DBUtil :: selectObjectArray("pagemaster_pubfields", "pm_tid = $tid");

	if ($pid == '') {
		$pid = pnModAPIFunc('pagemaster', 'user', 'getPid', array (
			'tid' => $tid,
			'id' => $id
		));
	}

	if ($template == '') {
		if ($pubtype['filename'] <> '') {
			//template comes from pubtype
			$sec_template = $pubtype['filename'];
			$template = 'output/viewpub_' . $pubtype['filename'] . '.htm';
		} else {
			//standart template
			$template = 'var:viewpub_template_code';
			//do not check permission for dynamic template
			$sec_template = '';
		}
	} else {
		//template comes from parameter
		$sec_template = $template;
		$template = 'output/viewpub_' . $template . '.htm';
	}

	if (!SecurityUtil :: checkPermission('pagemaster:full:', "$tid:$pid:$sec_template", ACCESS_READ))
	return LogUtil :: registerError(_NOT_AUTHORIZED . ' ' . 'pagemaster:full: - ' . "$tid:$pid:$sec_template");

	$cacheid = 'viewpub' . $tid . '|' . $pid;
	$pnRender = pnRender :: getInstance('pagemaster', $pubtype['cachetid'], $cacheid, true);
	$pnRender->cache_lifetime = $pubtype['cachelifetime'];

	if ($pnRender->is_cached($template, $cacheid)) {
		return $pnRender->fetch($template, $cacheid);
	}

	$pubdata = pnModAPIFunc('pagemaster', 'user', 'getPub', array (
		'tid' => $tid,
		'id' => $id,
		'pid' => $pid,
		'checkPerm' => false, //check later, together with template
		'getApprovalState' => true,
		'handlePluginFields' => true
	));

	$core_title = getTitleField($pubfields);

	foreach ($pubdata as $key => $field) {
		$pnRender->assign($key, $field);
	}
	$pnRender->assign('core_tid', $tid);
	$pnRender->assign('core_approvalstate', $pubdata['__WORKFLOW__']['state']);
	if ($pubdata['cr_uid'] == pnUserGetVar('uid'))
	$pnRender->assign('core_creator', true);
	else
	$pnRender->assign('core_creator', false);
	$pnRender->assign('core_title', $pubdata[$core_title]);
	$pnRender->assign('core_uniqueid', "$tid_" . $pubdata['core_pid']);
	$pnRender->assign('core_titlefield', $core_title);

	//check if template is available
	if ($template <> 'var:viewpub_template_code')
	if (!$pnRender->get_template_path($template)) {
		LogUtil :: registerStatus('template not found: ' . $template);
		$template = 'var:viewpub_template_code';
	}

	if ($template == 'var:viewpub_template_code') {
		global $viewpub_template_code;
		$viewpub_template_code = generate_viewpub_template_code($tid, $pubdata, $pubtype, $pubfields);
		//TODO: only recompile if changed
		$pnRender->force_compile = true;
	}
	return $pnRender->fetch($template, $cacheid);
}