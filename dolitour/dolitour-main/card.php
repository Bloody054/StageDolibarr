<?php
/* Copyright (C) 2003-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Marc Barilley / Ocebo	<marc@ocebo.com>
 * Copyright (C) 2005-2015	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2010-2013	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2011-2018	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2013	Christophe Battarel		<christophe.battarel@altairis.fr>
 * Copyright (C) 2012-2016	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2012       Cedric Salvador      	<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file 	htdocs/dolitour/card.php
 * \ingroup dolitour
 * \brief 	Page to show customer order
 */

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';

dol_include_once("/dolitour/class/dolitour.class.php");
dol_include_once("/dolitour/lib/dolitour.lib.php");

$langs->load("dolitour@dolitour");

$id = GETPOST('id', 'int');
$lineid = GETPOST('lineid', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage','alpha');

$result = restrictedArea($user, 'dolitour', $id);

$object = new DoliTour($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('dolitourcard','globalcard'));

$permissiondellink = $user->rights->dolitour->creer; 	// Used by the include of actions_dellink.inc.php

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($cancel)
	{
		if (! empty($backtopage))
		{
			header("Location: ".$backtopage);
			exit;
		}
		$action='';
	}

	if ($action == 'add' && !GETPOST('button', 'alpha'))
	{
		$action = 'create';
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once

	if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->dolitour->supprimer)
	{
		$result = $object->delete($user);
		if ($result > 0)
		{
			// Remove old one and create thumbs
			if ($object->element) {
				$fileimg = $conf->dolitour->dir_output.'/'.dol_sanitizeFileName($object->ref).'/'.$object->element;
				$dirthumbs = $conf->dolitour->dir_output.'/'.dol_sanitizeFileName($object->ref).'/thumbs';
				
				dol_delete_file($fileimg);
				dol_delete_dir_recursive($dirthumbs);
			}

			header('Location: list.php?restore_lastsearch_values=1');
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	else if ($action == 'add' && $user->rights->dolitour->creer)
	{
		$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
		if ($ret < 0) $error++;

        $date_start = dol_mktime(0, 0, 0, GETPOST('smonth'), GETPOST('sday'), GETPOST('syear'));
        $date_end = dol_mktime(0, 0, 0, GETPOST('emonth'), GETPOST('eday'), GETPOST('eyear'));
        $title = GETPOST('title', 'alpha');
        $description = GETPOST('description');

        if (empty($title) < 0)
        {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Title')), null, 'errors');
            $action = 'create';
            $error++;
        }

        if (!$error)
		{
			$object->title = $title;
			$object->description = $description;
            $object->date_start = GETPOST('sday') ? $date_start : null;
            $object->date_end = GETPOST('eday') ? $date_end : null;

			$id = $object->create($user);
		}
		
		if ($id > 0 && ! $error)
		{
			if (isset($_FILES['element']['tmp_name']) && trim($_FILES['element']['tmp_name'])) {
				$dir = $conf->dolitour->dir_output.'/'.dol_sanitizeFileName($object->ref);

				dol_mkdir($dir);

				if (@is_dir($dir)) {
					$newfile = $dir.'/'.dol_sanitizeFileName($_FILES['element']['name']);
					$result = dol_move_uploaded_file($_FILES['element']['tmp_name'], $newfile, 1, 0, $_FILES['element']['error']);

					if (!$result > 0) {
						setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
					} else {
						// Create thumbs
						$object->addThumbs($newfile);
					}

					$object->element = dol_sanitizeFileName($_FILES['element']['name']);
					$object->update($user);

				} else {
					$error ++;
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorFailedToCreateDir", $dir), $mesgs, 'errors');
				}
			}		
		}

		if ($id > 0 && ! $error)
		{
			header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
			exit;
		} else {
			$action = 'create';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
    else if ($action == 'setdates' && !GETPOST('cancel','alpha'))
    {
        $date_start = dol_mktime(0, 0, 0, GETPOST('smonth'), GETPOST('sday'), GETPOST('syear'));

        $object->date_start = $date_start;
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
    else if ($action == 'setdatee' && !GETPOST('cancel','alpha'))
    {
        $date_end = dol_mktime(0, 0, 0, GETPOST('emonth'), GETPOST('eday'), GETPOST('eyear'));

        $object->date_end = $date_end;
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
    else if ($action == 'setactive' && !GETPOST('cancel','alpha'))
    {
        $object->active = GETPOST('active', 'int');
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
	else if ($action == 'setdescription' && !GETPOST('cancel','alpha'))
	{
		$object->description = GETPOST('description');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	else if ($action == 'settitle' && !GETPOST('cancel','alpha'))
	{
		$object->title = GETPOST('title', 'alpha');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	else if ($action == 'setelement' && !GETPOST('cancel','alpha'))
	{
		$object->elementtoselect = GETPOST('elementtoselect', 'alpha');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	else if ($action == 'setcontext' && !GETPOST('cancel','alpha'))
	{
		$object->context = GETPOST('context', 'alpha');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	else if ($action == 'setside' && !GETPOST('cancel','alpha'))
	{
		$object->side = GETPOST('side', 'alpha');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	else if ($action == 'setalign' && !GETPOST('cancel','alpha'))
	{
		$object->align = GETPOST('align', 'alpha');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	if ($action == 'update_extras')
	{
		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from update form
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute','none'));
		if ($ret < 0) $error++;

		if (! $error)
		{
			// Actions on extra fields
			$result = $object->insertExtraFields('ONBOARDING_MODIFY');
			if ($result < 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) $action = 'edit_extras';
	}

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';
}


/*
 *	View
 */

llxHeader('', $langs->trans('DoliTour'));

$form = new Form($db);

// Mode creation
if ($action == 'create' && $user->rights->dolitour->creer)
{
	print load_fiche_titre($langs->trans('NewDoliTour'),'','dolitour@dolitour');


	print '<form id="crea_dolitour" name="crea_dolitour" action="' . $_SERVER["PHP_SELF"] . '" method="POST" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';

	dol_fiche_head('', '', '', -1);

    $date_start = dol_mktime(12, 0, 0, GETPOST('smonth'), GETPOST('sday'), GETPOST('syear'));
    $date_end = dol_mktime(12, 0, 0, GETPOST('emonth'), GETPOST('eday'), GETPOST('eyear'));

    print '<table class="border" width="100%">';

	// Reference
	print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('Ref') . '</td><td>' . $object->getNextNumRef($mysoc) . '</td></tr>';

	// Titre
	print '<tr><td class="fieldrequired">' . $langs->trans('Title') . '</td><td>';
	print '<input type="text" size="60" name="title" value="'.GETPOST('title').'"></td>';
	print '</tr>';

	// Description
	print '<tr><td>' . $langs->trans('Description') . '</td><td>';
	print '<textarea name="description" cols="60" rows="8">'.GETPOST('description').'</textarea></td>';
	print '</tr>';

	// element
	print '<tr><td>' . $langs->trans('element') . '</td><td>';
	print '<input type="file" class="flat maxwidth200onsmartphone" name="element" id="elementinput" /></td>';
	print '</tr>';


    print '<tr><td>' . $langs->trans('DateStart') . '</td><td>';
    print $form->selectDate($date_start ? $date_start : -1, 's', '', '', '', "dates", 1, 1);			// Always autofill date with current date
    print '</td></tr>';


    print '<tr><td>' . $langs->trans('DateEnd') . '</td><td>';
    print $form->selectDate($date_end ? $date_end : -1, 'e', '', '', '', "datee", 1, 1);			// Always autofill date with current date
    print '</td></tr>';

    // Visible
    print '<tr><td>' . $langs->trans('Visible') . '</td><td>';
    print $form->selectyesno('active', GETPOST('active'), 1);
    print '</td></tr>';

	// Other attributes
	$parameters = array('objectsrc' => '', 'socid'=> '');
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'edit');
	}

	print '</table>';

	dol_fiche_end();


	print '<div class="center">';
	print '<input type="submit" class="button" name="button" value="' . $langs->trans('CreateDoliTour') . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onclick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';

} else {
	// Mode view
	$now = dol_now();

	if ($object->id > 0) 
	{
		$author = new User($db);
		$author->fetch($object->user_author_id);

		$res = $object->fetch_optionals();
		
		$head = dolitour_prepare_head($object);
		
		dol_fiche_head($head, 'dolitour', $langs->trans("DoliTour"), -1, 'dolitour@dolitour');

		$formconfirm = '';

		// Confirmation to delete
		if ($action == 'delete') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteDoliTour'), $langs->trans('ConfirmDeleteDoliTour'), 'confirm_delete', '', 0, 1);
		}

		// Call Hook formConfirm
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
		elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;

		// Print form confirm
		print $formconfirm;


		// DoliTour card
		$url = dol_buildpath('/dolitour/list.php', 1).'?restore_lastsearch_values=1';
		$linkback = '<a href="' . $url . '">' . $langs->trans("BackToList") . '</a>';
			$morehtmlref = ' - '.dol_escape_htmltag($object->title);
		dol_banner_tab($object, 'ref', $linkback, $morehtmlref,1);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border" width="100%">';


		// Titre
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Title');
		print '</td>';

		if ($action != 'edittitle')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edittitle&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetTitle'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'edittitle') {
			print '<form name="settitle" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="settitle">';
			print '<input type="text" size="60" name="title" value="'.$object->title.'">';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->title;
		}
		print '</td>';
		print '</tr>';

		// Description
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Description');
		print '</td>';

		if ($action != 'editdescription')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editdescription&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetURL'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editdescription') {
			print '<form name="seturl" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setdescription">';
            print '<textarea name="description" cols="60" rows="8">'.$object->description.'</textarea>';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->description;
		}
		print '</td>';
		print '</tr>';
		
		// Element
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Element');
		print '</td>';

		if ($action != 'editelement')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editelement&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetElement'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editelement') {
			print '<form name="setelement" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setelement">';
			print '<input type="text" size="60" name="elementtoselect" value="'.$object->elementtoselect.'">';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->elementtoselect;
		}
		print '</td>';
		print '</tr>';		

		// Context
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Context');
		print '</td>';

		if ($action != 'editcontext')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editcontext&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetContext'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editcontext') {
			print '<form name="setcontext" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setcontext">';
			print '<input type="text" size="60" name="context" value="'.$object->context.'">';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->context;
		}
		print '</td>';
		print '</tr>';

		// Side
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Side');
		print '</td>';

		if ($action != 'editside')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editside&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetSide'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editside') {
			print '<form name="setside" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setside">';
			print '<input type="text" size="60" name="side" value="'.$object->side.'">';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->side;
		}
		print '</td>';
		print '</tr>';

		// Align
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Align');
		print '</td>';

		if ($action != 'editalign')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editalign&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetAlign'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editalign') {
			print '<form name="setalign" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setalign">';
			print '<input type="text" size="60" name="align" value="'.$object->align.'">';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->align;
		}
		print '</td>';
		print '</tr>';




        // Date start
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('DateStart');
        print '</td>';

        if ($action != 'editdates' && $user->rights->dolitour->modifier)
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editdates&amp;id=' . $object->id . '">' . img_edit($langs->trans('IntentionSetDate'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editdates') {
            print '<form name="setdates" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setdates">';
            print $form->selectDate($object->date_start ? $object->date_start : -1, 's', '', '', '', "setdates");
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->date_start ? dol_print_date($object->date_start, 'day') : '&nbsp;';
        }
        print '</td>';
        print '</tr>';

        // Date end
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('DateEnd');
        print '</td>';

        if ($action != 'editdatee' && $user->rights->dolitour->modifier)
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editdatee&amp;id=' . $object->id . '">' . img_edit($langs->trans('IntentionSetDate'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editdatee') {
            print '<form name="setdatee" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setdatee">';
            print $form->selectDate($object->date_end ? $object->date_end : -1, 'e', '', '', '', "setdatee");
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->date_end ? dol_print_date($object->date_end, 'day') : '&nbsp;';
        }
        print '</td>';
        print '</tr>';

        // Fk_user_group
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Fk_user_group');
		print '</td>';

		if ($action != 'editfk_user_group')
			print '<td fk_user_group="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfk_user_group&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetFk_user_group'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editfk_user_group') {
			print '<form name="setfk_user_group" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setfk_user_group">';
			print '<input type="text" size="60" name="title" value="'.$object->fk_user_group.'">';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->fk_user_group;
		}
		print '</td>';
		print '</tr>';

        // Actif
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('Active');
        print '</td>';

        if ($action != 'editactive')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editactive&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetForHuman'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editactive') {
            print '<form name="setactive" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setactive">';
            print $form->selectyesno('active', $object->active, 1);
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print yn($object->active);
        }
        print '</td>';
        print '</tr>';

		// Other attributes
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="ficheaddleft">';
		print '<div class="underbanner clearboth"></div>';

		print '</div>';
		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div><br>';


		dol_fiche_end();

		/*
		 * Buttons for actions
		 */

		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
																									// modified by hook
		// modified by hook
		if (empty($reshook)) {
			// Delete 
			if ($user->rights->dolitour->supprimer) {
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=delete">' . $langs->trans('DeleteDoliTour') . '</a></div>';
			}
		}

		print '</div>';

		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		// Show links to link elements
		$somethingshown = $form->showLinkedObjectBlock($object, '');

		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'dolitour', '', 1);

		print '</div></div></div>';
		
	}
}

// End of page
llxFooter();
$db->close();
