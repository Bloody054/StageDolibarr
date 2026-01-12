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
 *  \file       htdocs/dolitour/admin/setup.php
 *  \ingroup    dolitour
 *  \brief      Admin page
 */


$res=@include("../../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");    // For "custom" directory

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
dol_include_once("/dolitour/lib/dolitour.lib.php");
dol_include_once("/dolitour/class/dolitour.class.php");

// Translations
$langs->load("dolitour@dolitour");
$langs->load("admin");

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');

$reg = array();

/*
 * Actions
 */


include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

$error=0;

// Action mise a jour ou ajout d'une constante
if ($action == 'update')
{
	$constname=GETPOST('constname','alpha');
	$constvalue=(GETPOST('constvalue_'.$constname) ? GETPOST('constvalue_'.$constname) : GETPOST('constvalue'));


	$consttype=GETPOST('consttype','alpha');
	$constnote=GETPOST('constnote');
	$res = dolibarr_set_const($db,$constname,$constvalue,'chaine',0,$constnote,$conf->entity);

	if (! $res > 0) $error++;

	if (! $error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'updateMask')
{
	$maskconstdolitour=GETPOST('maskconstdolitour','alpha');
	$maskdolitour=GETPOST('maskdolitour','alpha');
	if ($maskconstdolitour) $res = dolibarr_set_const($db,$maskconstdolitour,$maskdolitour,'chaine',0,'',$conf->entity);

	if (! $res > 0) $error++;

 	if (! $error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'setmod')
{
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "ONBOARDING_ADDON",$value,'chaine',0,'',$conf->entity);
}

/*
 * View
 */

llxHeader('', $langs->trans('DoliTourSetup'));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans('DoliTourSetup'), $linkback);

// Configuration header
$head = dolitour_prepare_admin_head();
dol_fiche_head(
	$head,
	'settings',
	$langs->trans("ModuleDoliTourName"),
	0,
	"dolitour@dolitour"
);

$form = new Form($db);

/*
 *  Module numerotation
 */
print load_fiche_titre($langs->trans("DoliToursNumberingModules"),'','');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name")."</td>\n";
print '<td>'.$langs->trans("Description")."</td>\n";
print '<td class="nowrap">'.$langs->trans("Example")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

$dir = './../core/modules/dolitour/';
if (is_dir($dir))
{
	$handle = opendir($dir);
	if (is_resource($handle))
	{
		$var=true;

		while (($file = readdir($handle))!==false)
		{

			if (substr($file, 0, 15) == 'mod_dolitour_' && substr($file, dol_strlen($file)-3, 3) == 'php')
			{
				$file = substr($file, 0, dol_strlen($file)-4);

				require_once $dir.$file.'.php';

				$module = new $file;

				// Show modules according to features level
				if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
				if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

				if ($module->isEnabled())
				{

					print '<tr class="oddeven"><td>'.$module->nom."</td><td>\n";
					print $module->info();
					print '</td>';

					// Show example of numbering module
					print '<td class="nowrap">';
					$tmp=$module->getExample();
					if (preg_match('/^Error/',$tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
					elseif ($tmp=='NotConfigured') print $langs->trans($tmp);
					else print $tmp;
					print '</td>'."\n";

					print '<td align="center">';
					if ($conf->global->ONBOARDING_ADDON == "$file")
					{
						print img_picto($langs->trans("Activated"),'switch_on');
					}
					else
					{
						print '<a href="'.$_SERVER["PHP_SELF"].'?token='.newtoken().'&action=setmod&amp;value='.$file.'">';
						print img_picto($langs->trans("Disabled"),'switch_off');
						print '</a>';
					}
					print '</td>';

					$dolitour = new DoliTour($db);

					// Info
					$htmltooltip='';
					$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
					$nextval = $module->getNextValue($mysoc, $dolitour);
					if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
						$htmltooltip.=''.$langs->trans("NextValue").': ';
						if ($nextval) {
							if (preg_match('/^Error/',$nextval) || $nextval=='NotConfigured')
								$nextval = $langs->trans($nextval);
							$htmltooltip.=$nextval.'<br>';
						} else {
							$htmltooltip.=$langs->trans($module->error).'<br>';
						}
					}

					print '<td align="center">';
					print $form->textwithpicto('',$htmltooltip,1,0);
					print '</td>';

					print "</tr>\n";
				}
			}
		}
		closedir($handle);
	}
}

print "</table><br>\n";

print load_fiche_titre($langs->trans("Options"),'','');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td colspan="2">';
$text = $langs->trans("Value");
$helptext ='';
print $form->textwithpicto($text, $helptext, 1, 'help', '', 0, 2, 'idhelptext');
print '</td>';
print "</tr>\n";

//Show context
print '<tr class="oddeven">';
// Show constant
print '<td>';
print $langs->trans('DescONBOARDING_SHOW_ME_THE_CONTEXT');
print '</td>';
print '<td colspan="2">';
if ($conf->use_javascript_ajax)
{
    print ajax_constantonoff('ONBOARDING_SHOW_ME_THE_CONTEXT');
}
else
{
    if (empty($conf->global->ONBOARDING_SHOW_ME_THE_CONTEXT))
    {
        print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ONBOARDING_SHOW_ME_THE_CONTEXT">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
    }
    else
    {
        print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ONBOARDING_SHOW_ME_THE_CONTEXT">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
    }
}
print '</td>';
print '</tr>';

//Show Progress
print '<tr class="oddeven">';
// Show constant
print '<td>';
print $langs->trans('DescONBOARDING_SHOW_PROGRESS');
print '</td>';
print '<td colspan="2">';
if ($conf->use_javascript_ajax)
{
    print ajax_constantonoff('ONBOARDING_SHOW_PROGRESS');
}
else
{
    if (empty($conf->global->ONBOARDING_SHOW_PROGRESS))
    {
        print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ONBOARDING_SHOW_PROGRESS">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
    }
    else
    {
        print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ONBOARDING_SHOW_PROGRESS">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
    }
}
print '</td>';
print '</tr>';

//Allow close
print '<tr class="oddeven">';
// Show constant
print '<td>';
print $langs->trans('DescONBOARDING_ALLOW_CLOSE');
print '</td>';
print '<td colspan="2">';
if ($conf->use_javascript_ajax)
{
    print ajax_constantonoff('ONBOARDING_ALLOW_CLOSE');
}
else
{
    if (empty($conf->global->ONBOARDING_ALLOW_CLOSE))
    {
        print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ONBOARDING_ALLOW_CLOSE">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
    }
    else
    {
        print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ONBOARDING_ALLOW_CLOSE">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
    }
}
print '</td>';
print '</tr>';

//Color off overlay
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<tr class="oddeven">';
print '<td>';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="constname" value="ONBOARDING_OVERLAY_COLOR">';
print '<input type="hidden" name="constnote" value="">';
print $langs->trans('DescONBOARDING_OVERLAY_COLOR');
print '</td>';
print '<td>';
print '<input type="text" class="flat" name="constvalue" value="'.(!empty($conf->global->ONBOARDING_OVERLAY_COLOR) ? $conf->global->ONBOARDING_OVERLAY_COLOR : '').'" />';
print '<input type="hidden" name="consttype" value="chaine">';
print '</td>';
print '<td align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print '</td>';
print '</tr>';
print '</form>';

print '</table>';




// Page end
dol_fiche_end();
llxFooter();
