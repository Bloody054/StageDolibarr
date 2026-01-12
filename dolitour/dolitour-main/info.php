<?php
/* Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/dolitour/info.php
 *      \ingroup    dolitour
 *		\brief      Page des dolitours d'une dolitour
 */

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
dol_include_once("/dolitour/class/dolitour.class.php");
dol_include_once("/dolitour/lib/dolitour.lib.php");

if (!$user->rights->dolitour->lire)	accessforbidden();


$langs->load("dolitour@dolitour");

$id = GETPOST("id",'int');
$ref=GETPOST('ref','alpha');

// Security check
$result=restrictedArea($user,'dolitour',$id,'');

$object = new DoliTour($db);
if (! $object->fetch($id, $ref) > 0)
{
    dol_print_error($db);
    exit;
}


/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans('DoliTour'), '', '', 0, 0, array('/dolitour/js/functions.js.php'));
$object->info($object->id);

$head = dolitour_prepare_head($object);
dol_fiche_head($head, 'info', $langs->trans("DoliTour"), 0, 'dolitour@dolitour');

// Order card

$url = dol_buildpath('/dolitour/list.php', 1).'?restore_lastsearch_values=1';
$linkback = '<a href="' . $url . '">' . $langs->trans("BackToList") . '</a>';


dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<br>';

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

dol_fiche_end();

// End of page
llxFooter();
$db->close();
