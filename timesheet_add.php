<?php
// +----------------------------------------------------------------------+
// | Anuko Time Tracker
// +----------------------------------------------------------------------+
// | Copyright (c) Anuko International Ltd. (https://www.anuko.com)
// +----------------------------------------------------------------------+
// | LIBERAL FREEWARE LICENSE: This source code document may be used
// | by anyone for any purpose, and freely redistributed alone or in
// | combination with other software, provided that the license is obeyed.
// |
// | There are only two ways to violate the license:
// |
// | 1. To redistribute this code in source form, with the copyright
// |    notice or license removed or altered. (Distributing in compiled
// |    forms without embedded copyright notices is permitted).
// |
// | 2. To redistribute modified versions of this code in *any* form
// |    that bears insufficient indications that the modifications are
// |    not the work of the original author(s).
// |
// | This license applies to this document only, not any other software
// | that it may be combined with.
// |
// +----------------------------------------------------------------------+
// | Contributors:
// | https://www.anuko.com/time_tracker/credits.htm
// +----------------------------------------------------------------------+

require_once('initialize.php');
import('form.Form');
import('ttTimesheetHelper');

// Access checks.
if (!(ttAccessAllowed('manage_own_timesheets') || ttAccessAllowed('manage_timesheets') || ttAccessAllowed('manage_all_timesheets'))) {
  header('Location: access_denied.php');
  exit();
}
if (!$user->isPluginEnabled('ts')) {
  header('Location: feature_disabled.php');
  exit();
}
// End of access checks.

if ($request->isPost()) {
  $cl_name = trim($request->getParameter('timesheet_name'));
  $cl_client = $request->getParameter('client');
  $cl_project = $request->getParameter('project');
  $cl_start = $request->getParameter('start');
  $cl_finish = $request->getParameter('finish');
  $cl_comment = trim($request->getParameter('comment'));
}

$user_id = $user->getUser();

$form = new Form('timesheetForm');
$form->addInput(array('type'=>'text','maxlength'=>'100','name'=>'timesheet_name','style'=>'width: 250px;','value'=>$cl_name));

// Dropdown for clients if the clients plugin is enabled.
$showClient = $user->isPluginEnabled('cl');
if ($showClient) {
  $clients = ttGroupHelper::getActiveClients();
  $form->addInput(array('type'=>'combobox','name'=>'client','style'=>'width: 250px;','data'=>$clients,'datakeys'=>array('id','name'),'value'=>$cl_client,'empty'=>array(''=>$i18n->get('dropdown.select'))));
}
// Dropdown for projects.
$showProject = MODE_PROJECTS == $user->getTrackingMode() || MODE_PROJECTS_AND_TASKS == $user->getTrackingMode();
if ($showProject) {
  $projects = $user->getAssignedProjects();
  $form->addInput(array('type'=>'combobox','name'=>'project','style'=>'width: 250px;','data'=>$projects,'datakeys'=>array('id','name'),'value'=>$cl_project,'empty'=>array(''=>$i18n->get('dropdown.all'))));
}
$form->addInput(array('type'=>'datefield','maxlength'=>'20','name'=>'start','value'=>$cl_start));
$form->addInput(array('type'=>'datefield','maxlength'=>'20','name'=>'finish','value'=>$cl_finish));
$form->addInput(array('type'=>'textarea','name'=>'comment','style'=>'width: 250px; height: 40px;','value'=>$cl_comment));
$form->addInput(array('type'=>'submit','name'=>'btn_add','value'=>$i18n->get('button.add')));

if ($request->isPost()) {
  // Validate user input.
  if (!ttValidString($cl_name)) $err->add($i18n->get('error.field'), $i18n->get('label.thing_name'));
  if (!ttValidDate($cl_start)) $err->add($i18n->get('error.field'), $i18n->get('label.start_date'));
  if (!ttValidDate($cl_finish)) $err->add($i18n->get('error.field'), $i18n->get('label.end_date'));
  if (!ttValidString($cl_comment, true)) $err->add($i18n->get('error.field'), $i18n->get('label.comment'));
  if ($err->no() && ttTimesheetHelper::getTimesheetByName($cl_name, $user_id)) $err->add($i18n->get('error.object_exists'));
  $fields = array('user_id' => $user_id,
    'name' => $cl_name,
    'client_id' => $cl_client,
    'project_id' => $cl_project,
    'start_date' => $cl_start,
    'end_date' => $cl_finish,
    'comment' => $cl_comment);
  if ($err->no() && !ttTimesheetHelper::timesheetItemsExist($fields)) $err->add($i18n->get('error.no_records'));
  // Finished validating user input.

  /*
  if ($err->no()) {
    // TODO: use $fields.
    if (ttTimesheetHelper::insert(array('user_id' => $user_id,
        'client_id' => $bean->getAttribute('client'),
        'name' => $cl_name,
        'comment' => $cl_comment))) {
          header('Location: timesheets.php');
          exit();
        } else
          $err->add($i18n->get('error.db'));
    } */
} // isPost

$smarty->assign('forms', array($form->getName()=>$form->toArray()));
$smarty->assign('onload', 'onLoad="document.timesheetForm.timesheet_name.focus()"');
$smarty->assign('show_client', $showClient);
$smarty->assign('show_project', $showProject);
$smarty->assign('title', $i18n->get('title.add_timesheet'));
$smarty->assign('content_page_name', 'timesheet_add.tpl');
$smarty->display('index.tpl');
