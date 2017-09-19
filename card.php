<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
dol_include_once('/personalhomepage/class/personalhomepage.class.php');
dol_include_once('/personalhomepage/lib/personalhomepage.lib.php');

if(empty($user->rights->personalhomepage->write)) accessforbidden();

$langs->load('personalhomepage@personalhomepage');
$langs->load('admin');

$action = GETPOST('action');
$id = GETPOST('id', 'int');
if (empty($id)) $id = GETPOST('fk_element');

$mode = 'view';
if (empty($user->rights->personalhomepage->write)) $mode = 'view'; // Force 'view' mode if can't edit object
else if ($action == 'create' || $action == 'edit') $mode = 'edit';

$PDOdb = new TPDOdb;
$object = new TPersonalHomePage;
$usergroup = new UserGroup($db);


if (!empty($id))
{
	$usergroup->fetch($id);
	$object->loadByGroupId($PDOdb, $id);
}

$hookmanager->initHooks(array('personalhomepagecard', 'globalcard'));

/*
 * Actions
 */

$parameters = array('id' => $id, 'ref' => $ref, 'mode' => $mode);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Si vide alors le comportement n'est pas remplacé
if (empty($reshook))
{
	$error = 0;
	switch ($action) {
		case 'save':
			$object->set_values($_REQUEST); // Set standard attributes
			$object->save($PDOdb);
			
			header('Location: '.dol_buildpath('/personalhomepage/card.php', 1).'?id='.$usergroup->id);
			exit;
			
			break;
	}
}

_fiche($usergroup, $object, $mode);

/**
 * View
 */
function _fiche(&$usergroup, &$object, $mode)
{
	global $langs,$conf;
	
	$form = new Form($db);
	
	$formcore = new TFormCore;
	$formcore->Set_typeaff($mode);

	$title=$langs->trans("PersonalHomePage");
	llxHeader('',$title);

	$head = group_prepare_head($usergroup);
	$picto = 'group';
	dol_fiche_head($head, 'personalhomepage_tab', $langs->trans("Group"), 0, $picto);

	dol_banner_tab($usergroup,'id','',$user->rights->user->user->lire || $user->admin);


	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="save">';
    print '<input type="hidden" name="entity" value="'.$conf->entity.'">';
    print '<input type="hidden" name="element_type" value="group">';
    print '<input type="hidden" name="fk_element" value="'.$usergroup->id.'">';
	
	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border" width="100%">';

	// Name (already in dol_banner, we keep it to have the GlobalGroup picto, but we should move it in dol_banner)
	if (! empty($conf->mutlicompany->enabled))
	{
		print '<tr><td class="titlefield">'.$langs->trans("Name").'</td>';
		print '<td class="valeur">'.$usergroup->name;
		if (empty($usergroup->entity))
		{
			print img_picto($langs->trans("GlobalGroup"),'redstar');
		}
		print "</td></tr>\n";
	}

	// Multicompany
	if (! empty($conf->multicompany->enabled) && is_object($mc) && empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && $user->admin && ! $user->entity)
	{
		$mc->getInfo($usergroup->entity);
		print "<tr>".'<td class="tdtop">'.$langs->trans("Entity").'</td>';
		print '<td class="valeur">'.$mc->label;
		print "</td></tr>\n";
	}

	print '<tr>';
	print '<td width="15%">'.$form->textwithpicto($langs->trans('LandingPage'), $langs->trans('PersonalHomePageToolTip')).'</td>';
	print '<td>'.$formcore->texte('', 'url', $object->url, 150).'</td>';
	print '</tr>';

	print "</table>\n";

	dol_fiche_end();

	if ($mode == 'edit')
	{
		print '<br /><div class="center"><input class="button" value="'.$langs->trans("Update").'" type="submit"></div>';
	}
	else
	{
		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$usergroup->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
		print "</div>\n";
	}

	print "</form>";
}



/*
$formcore = new TFormCore;
$formcore->Set_typeaff($mode);

$form = new Form($db);

$formconfirm = getFormConfirm($PDOdb, $form, $object, $action);
if (!empty($formconfirm)) echo $formconfirm;

$TBS=new TTemplateTBS();
$TBS->TBS->protect=false;
$TBS->TBS->noerr=true;

if ($mode == 'edit') echo $formcore->begin_form($_SERVER['PHP_SELF'], 'form_personalhomepage');

$linkback = '<a href="'.dol_buildpath('/personalhomepage/list.php', 1).'">' . $langs->trans("BackToList") . '</a>';
print $TBS->render('tpl/card.tpl.php'
	,array() // Block
	,array(
		'object'=>$object
		,'view' => array(
			'mode' => $mode
			,'action' => 'save'
			,'urlcard' => dol_buildpath('/personalhomepage/card.php', 1)
			,'urllist' => dol_buildpath('/personalhomepage/list.php', 1)
			,'showRef' => ($action == 'create') ? $langs->trans('Draft') : $form->showrefnav($object->generic, 'ref', $linkback, 1, 'ref', 'ref', '')
			,'showLabel' => $formcore->texte('', 'label', $object->label, 80, 255)
//			,'showNote' => $formcore->zonetexte('', 'note', $object->note, 80, 8)
			,'showStatus' => $object->getLibStatut(1)
		)
		,'langs' => $langs
		,'user' => $user
		,'conf' => $conf
		,'TPersonalHomePage' => array(
			'STATUS_DRAFT' => TPersonalHomePage::STATUS_DRAFT
			,'STATUS_VALIDATED' => TPersonalHomePage::STATUS_VALIDATED
			,'STATUS_REFUSED' => TPersonalHomePage::STATUS_REFUSED
			,'STATUS_ACCEPTED' => TPersonalHomePage::STATUS_ACCEPTED
		)
	)
);

if ($mode == 'edit') echo $formcore->end_form();

if ($mode == 'view' && $object->getId()) $somethingshown = $form->showLinkedObjectBlock($object->generic);
*/
llxFooter();