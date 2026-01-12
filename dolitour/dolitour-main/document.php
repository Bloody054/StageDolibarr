<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
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
 *	\file       htdocs/dolitour/document.php
 *	\ingroup    order
 *	\brief      Management page of documents attached to an order
 */

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

dol_include_once("/dolitour/class/dolitour.class.php");
dol_include_once("/dolitour/lib/dolitour.lib.php");

// Load translation files required by the page
$langs->load("dolitour@dolitour");
$langs->load("other");


$action		= GETPOST('action','aZ09');
$confirm	= GETPOST('confirm');
$id			= GETPOST('id','int');
$ref		= GETPOST('ref');


$result=restrictedArea($user,'dolitour',$id,'');

// Get parameters
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";

$object = new DoliTour($db);


/*
 * Actions
 */

if ($object->fetch($id))
{
	$object->fetch_thirdparty();
	$upload_dir = $conf->dolitour->dir_output . "/" . dol_sanitizeFileName($object->ref);
}

include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';


/*
 * View
 */

llxHeader('', $langs->trans('DoliTour'));


$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	if ($object->fetch($id, $ref))
	{
		$upload_dir = $conf->dolitour->dir_output.'/'.dol_sanitizeFileName($object->ref);

		$head = dolitour_prepare_head($object);
		dol_fiche_head($head, 'documents', $langs->trans('DoliTour'), 0, 'dolitour@dolitour');

		// Build file list
		$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
		$totalsize=0;
		foreach($filearray as $key => $file)
		{
		    $totalsize+=$file['size'];
		}

		// Order card

		$url = dol_buildpath('/dolitour/list.php', 1).'?restore_lastsearch_values=1';
		$linkback = '<a href="' . $url . '">' . $langs->trans("BackToList") . '</a>';
		// Datamatrix
		$url = '';
		if (!empty($conf->datamatrix->enabled))
		{
			$ref = dol_sanitizeFileName($object->ref);
			$file = $conf->dolitour->dir_output . "/" . $ref . "/" . $ref . ".png";
			if (file_exists($file))
			{
				$url = DOL_URL_ROOT.'/viewimage.php?modulepart=dolitour&file='.urlencode($ref . "/" . $ref . ".png");
			}
		}

		$morehtmlref = '<div id="object-datamatrix" data-image="'.$url.'" class="refidno">';
		$morehtmlref.= '</div>';

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border" width="100%">';

		print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
		print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize,1,1).'</td></tr>';

		print "</table>\n";

		print "</div>\n";

		print dol_fiche_end();

		$modulepart = 'dolitour';
		$permission = $user->rights->dolitour->creer;
		$permtoedit = $user->rights->dolitour->creer;
		$param = '&id=' . $object->id;
		include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
	}
	else
	{
		dol_print_error($db);
	}
}
else
{
	$url = dol_buildpath('/dolitour/list.php', 1).'?restore_lastsearch_values=1';

	header('Location: '.$url);
	exit;
}


// End of page
llxFooter();
$db->close();
