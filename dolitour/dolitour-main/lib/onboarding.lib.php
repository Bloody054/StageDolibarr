<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
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
 *	\file       htdocs/dolitour/lib/dolitour.lib.php
 *	\brief      Ensemble de fonctions de base pour le module dolitour
 * 	\ingroup	dolitour
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function dolitour_prepare_admin_head()
{
	global $db, $langs, $conf, $user;
	$langs->load("dolitour@dolitour");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/dolitour/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;


	$head[$h][0] = dol_buildpath("/dolitour/admin/extrafields.php", 1);
	$head[$h][1] = $langs->trans("Extrafields");
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = dol_buildpath("/dolitour/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function dolitour_prepare_head($object)
{
	global $db, $langs, $conf, $user;
	$langs->load("dolitour@dolitour");

	$h = 0;
	$head = array();

	if ($user->rights->dolitour->lire)
	{
		$head[$h][0] = dol_buildpath("/dolitour/card.php?id=".$object->id, 1);
		$head[$h][1] = $langs->trans("DoliTourCard");
		$head[$h][2] = 'dolitour';
		$h++;
	}

	$upload_dir = $conf->dolitour->dir_output . "/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	
	$head[$h][0] = dol_buildpath("/dolitour/document.php?id=".$object->id, 1);
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= ' <span class="badge">'.($nbFiles+$nbLinks).'</span>';
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = dol_buildpath("/dolitour/info.php?id=".$object->id, 1);
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	return $head;
}
