<?php
/* 
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
 *	\file       htdocs/dolitour/list.php
 *	\ingroup    dolitour
 *	\brief      Page to list orders
 */

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

dol_include_once("/dolitour/class/dolitour.class.php");


$langs->load("dolitour@dolitour");

$action = GETPOST('action','aZ09');
$massaction = GETPOST('massaction','alpha');
$confirm = GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage','aZ') ? GETPOST('contextpage','aZ') : 'dolitourlist';


$search_dyear = GETPOST("search_dyear","int");
$search_dmonth = GETPOST("search_dmonth","int");
$search_dday = GETPOST("search_dday","int");

$search_syear = GETPOST("search_syear","int");
$search_smonth = GETPOST("search_smonth","int");
$search_sday = GETPOST("search_sday","int");

$search_eyear = GETPOST("search_eyear","int");
$search_emonth = GETPOST("search_emonth","int");
$search_eday = GETPOST("search_eday","int");

$search_ref = GETPOST('search_ref','alpha')!='' ? GETPOST('search_ref','alpha') : GETPOST('sref','alpha');
$search_rank = GETPOST('search_rank','alpha')!='' ? GETPOST('search_rank','alpha') : GETPOST('srank','alpha');

$search_user_author_id = GETPOST('search_user_author_id','int');

$search_title = GETPOST('search_title');
$search_description = GETPOST('search_description');
$search_active = isset($_POST['search_active']) ? GETPOST('search_active','int') : -1;


$sall = trim((GETPOST('search_all', 'alphanohtml')!='') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));

$optioncss = GETPOST('optioncss','alpha');
$search_btn = GETPOST('button_search','alpha');
$search_remove_btn = GETPOST('button_removefilter','alpha');

// Security check
$id = GETPOST('id','int');
$result = restrictedArea($user, 'dolitour', $id,'');

$diroutputmassaction = $conf->dolitour->dir_output . '/temp/massgeneration/'.$user->id;

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='e.rank';
if (! $sortorder) $sortorder='ASC';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new DoliTour($db);
$hookmanager->initHooks(array('dolitourlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('dolitour');
$search_array_options=$extrafields->getOptionalsFromPost($object->table_element,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'e.ref'=>'Ref',
);

$arrayfields=array(
	'e.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'e.rank'=>array('label'=>$langs->trans("Rank"), 'checked'=>1),

	'e.title'=>array('label'=>$langs->trans("Title"), 'checked'=>1),
	'e.description'=>array('label'=>$langs->trans("Description"), 'checked'=>1),
	'e.elementtoselect'=>array('label'=>$langs->trans("Element"), 'checked'=>1),
	'e.context'=>array('label'=>$langs->trans("Context"), 'checked'=>1),
	'e.side'=>array('label'=>$langs->trans("Side"), 'checked'=>1),
	'e.align'=>array('label'=>$langs->trans("Align"), 'checked'=>1),
	'e.fk_user_group'=>array('label'=>$langs->trans("fk_user_group"), 'checked'=>1),

    'e.date_start'=>array('label'=>$langs->trans("DateStart"), 'checked'=>1),
    'e.date_end'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>1),

    'e.active'=>array('label'=>$langs->trans("Active"), 'checked'=>1),

    'e.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>1),
	'e.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
);

// Extra fields
if (isset($extrafields->attribute_label) && is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
	foreach($extrafields->attribute_label as $key => $val)
	{
		if (! empty($extrafields->attribute_list[$key])) $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>(($extrafields->attribute_list[$key]<0)?0:1), 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>(abs($extrafields->attribute_list[$key])!=3 && $extrafields->attribute_perms[$key]));
	}
}



/*
 * Actions
 */

$error = 0;

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array('socid'=>'');
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
	{
		$search_dyear = '';
		$search_dmonth = '';
		$search_dday = '';

        $search_syear = '';
        $search_smonth = '';
        $search_sday = '';

        $search_eyear = '';
        $search_emonth = '';
        $search_eday = '';

		$search_title = '';
		$search_description = '';
        $search_active = '';

		$search_ref = '';
		$search_rank = '';
		$search_user_author_id = '';

		$toselect = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')
	 || GETPOST('button_search_x','alpha') || GETPOST('button_search.x','alpha') || GETPOST('button_search','alpha'))
	{
		$massaction='';     // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	
	// Mass actions. Controls on number of lines checked.
	$maxformassaction=(empty($conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS)?1000:$conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS);
	if (! empty($massaction) && is_array($toselect) && count($toselect) < 1)
	{
		$error++;
		setEventMessages($langs->trans("NoRecordSelected"), null, "warnings");
	}
	if (! $error && is_array($toselect) && count($toselect) > $maxformassaction)
	{
		setEventMessages($langs->trans('TooManyRecordForMassAction', $maxformassaction), null, 'errors');
		$error++;
	}


	if ($action == "up" || $action == "down") {

		$object->fetch($id);

		$new_rank = $object->rank;
		$new_rank+= $action == "up" ? -1 : +1;

		$sql = "SELECT s.rowid FROM ".MAIN_DB_PREFIX."dolitour s WHERE s.rowid <> ".$id." AND s.entity IN (".getEntity('dolitour').") ORDER BY s.rank ASC";
		$res = $db->query($sql);
		
		$num = $db->num_rows($res);

		$i = 0;
		$ranks = array();
		while ($i < $num)
		{
			$obj = $db->fetch_object($res);
			$ranks[] = $obj->rowid;

			$i++;
		}
		
		$updated_ranks = array_slice($ranks, 0, $new_rank-1); // splice in at position 3
		$updated_ranks[] = $id;

		if (count(array_slice($ranks, $new_rank-1))) {
			foreach (array_slice($ranks, $new_rank-1) as $val) {
				$updated_ranks[] = $val;
			}
		}

		foreach ($updated_ranks as $rank => $rowid) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."dolitour s SET s.rank = ".($rank+1)." WHERE s.rowid = ".$rowid;
			$db->query($sql);	
		}
	}

    // Mass actions
    $objectclass = 'DoliTour';
    $objectlabel = 'Onboardings';
    $permtoread = $user->rights->dolitour->lire;
    $permtodelete = $user->rights->dolitour->supprimer;
    $permtomodify = $user->rights->dolitour->modifier;
    $uploaddir = $conf->dolitour->dir_output;
    $trigger_name='ONBOARDING_SENTBYMAIL';
    include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * View
 */

$now=dol_now();

$form = new Form($db);
$formother = new FormOther($db);

$userstatic = new User($db);

$title = $langs->trans("DoliToursMenu");
$help_url = "";

$sql = 'SELECT';
if ($sall) $sql = 'SELECT DISTINCT';

$sql.= " e.rowid, e.rank, e.ref, e.title, e.description, e.elementtoselect, e.context, e.side, e.align, e.fk_user_group, e.datec, e.date_start, e.date_end, e.user_author_id, e.entity, e.active, e.tms ";

// Add fields from extrafields
if(!empty($extrafields->attribute_label)){
	foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');	
}

// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= ' FROM '.MAIN_DB_PREFIX.'dolitour as e';

if(!empty($extrafields->attribute_label)){
	if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."dolitour_extrafields as ef on (e.rowid = ef.fk_object)";
}
$sql.= ' WHERE e.entity IN ('.getEntity('dolitour').')';

if ($search_ref) $sql .= natural_search('e.ref', $search_ref);
if ($search_rank) $sql .= natural_search('e.rank', $search_rank);
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);

if ($search_dmonth > 0)
{
	if ($search_dyear > 0 && empty($search_dday))
	$sql.= " AND e.datec BETWEEN '".$db->idate(dol_get_first_day($search_dyear, $search_dmonth, false))."' AND '".$db->idate(dol_get_last_day($search_dyear, $search_dmonth, false))."'";
	else if ($search_dyear > 0 && ! empty($search_dday))
	$sql.= " AND e.datec BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_dmonth, $search_dday, $search_dyear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_dmonth, $search_dday, $search_dyear))."'";
	else
	$sql.= " AND date_format(e.datec, '%m') = '".$search_dmonth."'";
}
else if ($search_dyear > 0)
{
	$sql.= " AND e.datec BETWEEN '".$db->idate(dol_get_first_day($search_dyear, 1, false))."' AND '".$db->idate(dol_get_last_day($search_dyear, 12, false))."'";
}

if ($search_smonth > 0)
{
    if ($search_syear > 0 && empty($search_sday))
        $sql.= " AND e.date_start BETWEEN '".$db->idate(dol_get_first_day($search_syear, $search_smonth, false))."' AND '".$db->idate(dol_get_last_day($search_syear, $search_smonth, false))."'";
    else if ($search_syear > 0 && ! empty($search_sday))
        $sql.= " AND e.date_start BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_smonth, $search_sday, $search_syear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_smonth, $search_sday, $search_syear))."'";
    else
        $sql.= " AND date_format(e.date_start, '%m') = '".$search_smonth."'";
}
else if ($search_syear > 0)
{
    $sql.= " AND e.date_start BETWEEN '".$db->idate(dol_get_first_day($search_syear, 1, false))."' AND '".$db->idate(dol_get_last_day($search_syear, 12, false))."'";
}

if ($search_emonth > 0)
{
    if ($search_eyear > 0 && empty($search_eday))
        $sql.= " AND e.date_end BETWEEN '".$db->idate(dol_get_first_day($search_eyear, $search_emonth, false))."' AND '".$db->idate(dol_get_last_day($search_eyear, $search_emonth, false))."'";
    else if ($search_eyear > 0 && ! empty($search_eday))
        $sql.= " AND e.date_end BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_emonth, $search_eday, $search_eyear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_emonth, $search_eday, $search_eyear))."'";
    else
        $sql.= " AND date_format(e.date_end, '%m') = '".$search_emonth."'";
}
else if ($search_eyear > 0)
{
    $sql.= " AND e.date_end BETWEEN '".$db->idate(dol_get_first_day($search_eyear, 1, false))."' AND '".$db->idate(dol_get_last_day($search_eyear, 12, false))."'";
}

if ($search_title) $sql .= natural_search('e.title', $search_title);
if ($search_description) $sql .= natural_search('e.description', $search_description);

if ($search_active >= 0) $sql.= " AND e.active = " .$search_active;

if ($search_user_author_id > 0) $sql.= " AND e.user_author_id = " .$search_user_author_id;

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= $db->order($sortfield,$sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);

	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sql.= $db->plimit($limit + 1,$offset);
//print $sql;

$resql = $db->query($sql);
if ($resql)
{
	$title = $langs->trans('ListOfDoliTours');

	$num = $db->num_rows($resql);

	$arrayofselected=is_array($toselect)?$toselect:array();

	if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
	{
		$obj = $db->fetch_object($resql);
		$id = $obj->rowid;
		
		$url = dol_buildpath('/dolitour/card.php', 1).'?id='.$id;

		header("Location: ".$url);
		exit;
	}

	llxHeader('',$title,$help_url);

	$param='';

	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
	if ($sall)					$param.='&sall='.urlencode($sall);

	if ($search_dday)      		$param.='&search_dday='.urlencode($search_dday);
	if ($search_dmonth)      		$param.='&search_dmonth='.urlencode($search_dmonth);
	if ($search_dyear)       		$param.='&search_dyear='.urlencode($search_dyear);

    if ($search_eday)      		$param.='&search_eday='.urlencode($search_eday);
    if ($search_emonth)      		$param.='&search_emonth='.urlencode($search_emonth);
    if ($search_eyear)       		$param.='&search_eyear='.urlencode($search_eyear);

    if ($search_sday)      		$param.='&search_sday='.urlencode($search_sday);
    if ($search_smonth)      		$param.='&search_smonth='.urlencode($search_smonth);
    if ($search_syear)       		$param.='&search_syear='.urlencode($search_syear);


    if ($search_ref)      		$param.='&search_ref='.urlencode($search_ref);
    if ($search_rank)      		$param.='&search_rank='.urlencode($search_rank);

	if ($search_title) 	$param.='&search_title='.urlencode($search_title);
	if ($search_description) 	$param.='&search_description='.urlencode($search_description);

    if ($search_active >= 0) 		$param.='&search_active='.urlencode($search_active);

    if ($search_user_author_id > 0) 		$param.='&search_user_author_id='.urlencode($search_user_author_id);

	if ($optioncss != '')       $param.='&optioncss='.urlencode($optioncss);

	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions =  array();
	
	if ($user->rights->dolitour->supprimer) $arrayofmassactions['predelete']=$langs->trans("MassActionDelete");
	if (in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions=array();
	$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

	$newcardbutton='';
	if ($contextpage == 'dolitourlist' && $user->rights->dolitour->creer)
	{
		$newcardbutton='<a class="butActionNew" href="'.dol_buildpath('/dolitour/card.php?action=create', 2).'"><span class="valignmiddle">'.$langs->trans('NewDoliTour').'</span>';
		$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
		$newcardbutton.= '</a>';
	}

	// Lines of title fields
	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';


	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'dolitour@dolitour', 0, $newcardbutton, '', $limit);

    $topicmail = "SendDoliTourRef";
    $modelmail = "dolitour_send";
    $objecttmp = new DoliTour($db);
    $trackid = 'onb'.$object->id;
    include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($sall)
	{
		foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall).'</div>';
	}

	$moreforfilter='';

	// If the user can view other users
	if ($user->rights->user->user->lire)
	{
		$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('CreatedByUsers'). ': ';
		$moreforfilter.=$form->select_dolusers($search_user_author_id, 'search_user_author_id', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
	 	$moreforfilter.='</div>';
	}

	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter = $hookmanager->resPrint;

	if (! empty($moreforfilter))
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
	$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
	$selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	print '<tr class="liste_titre_filter">';
	
	// Ref
	if (! empty($arrayfields['e.ref']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
		print '</td>';
	}

	// Rank
	if (! empty($arrayfields['e.rank']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_rank" value="'.$search_rank.'">';
		print '</td>';
	}

	if (! empty($arrayfields['e.title']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="40" type="text" name="search_title" value="'.$search_title.'">';
		print '</td>';
	}

	if (! empty($arrayfields['e.description']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="40" type="text" name="search_description" value="'.$search_description.'">';
		print '</td>';
	}

	if (! empty($arrayfields['e.elementtoselect']['checked']))
	{
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}

	if (! empty($arrayfields['e.context']['checked']))
	{
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}

	if (! empty($arrayfields['e.side']['checked']))
	{
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}

	if (! empty($arrayfields['e.align']['checked']))
	{
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}

    if (! empty($arrayfields['e.date_start']['checked']))
    {
        print '<td class="liste_titre nowraponall" align="left">';
        if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_sday" value="'.$search_sday.'">';
        print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_smonth" value="'.$search_smonth.'">';
        $formother->select_year($search_syear?$search_syear:-1,'search_syear',1, 20, 5);
        print '</td>';
    }

    if (! empty($arrayfields['e.date_end']['checked']))
    {
        print '<td class="liste_titre nowraponall" align="left">';
        if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_eday" value="'.$search_eday.'">';
        print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_emonth" value="'.$search_emonth.'">';
        $formother->select_year($search_eyear?$search_eyear:-1,'search_eyear',1, 20, 5);
        print '</td>';
    }

    // Actif
    if (! empty($arrayfields['e.active']['checked']))
    {
        print '<td class="liste_titre">';
        print $form->selectyesno('search_active', GETPOST('search_active'), 1, false, 1);
        print '</td>';
    }

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields);
	$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Date de saisie
	if (! empty($arrayfields['e.datec']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="left">';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_dday" value="'.$search_dday.'">';
		print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_dmonth" value="'.$search_dmonth.'">';
		$formother->select_year($search_dyear?$search_dyear:-1,'search_dyear',1, 20, 5);
		print '</td>';
	}

	// Date modification
	if (! empty($arrayfields['e.tms']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}

	// Action column
	print '<td class="liste_titre" align="middle" colspan="2">';
	$searchpicto=$form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print "</tr>\n";

	// Fields title
	print '<tr class="liste_titre">';

	if (! empty($arrayfields['e.ref']['checked']))            print_liste_field_titre($arrayfields['e.ref']['label'],$_SERVER["PHP_SELF"],'e.ref','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.rank']['checked']))            print_liste_field_titre($arrayfields['e.rank']['label'],$_SERVER["PHP_SELF"],'e.rank','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.title']['checked']))            print_liste_field_titre($arrayfields['e.title']['label'],$_SERVER["PHP_SELF"],'e.title','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.description']['checked']))            print_liste_field_titre($arrayfields['e.description']['label'],$_SERVER["PHP_SELF"],'e.description','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.elementtoselect']['checked']))            print_liste_field_titre($arrayfields['e.elementtoselect']['label'],$_SERVER["PHP_SELF"],'e.elementtoselect','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.context']['checked']))            print_liste_field_titre($arrayfields['e.context']['label'],$_SERVER["PHP_SELF"],'e.context','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.side']['checked']))            print_liste_field_titre($arrayfields['e.side']['label'],$_SERVER["PHP_SELF"],'e.side','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.align']['checked']))            print_liste_field_titre($arrayfields['e.align']['label'],$_SERVER["PHP_SELF"],'e.align','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.fk_user_group']['checked']))            print_liste_field_titre($arrayfields['e.fk_user_group']['label'],$_SERVER["PHP_SELF"],'e.fk_user_group','',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['e.date_start']['checked']))            print_liste_field_titre($arrayfields['e.date_start']['label'],$_SERVER["PHP_SELF"],'e.date_start','',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['e.date_end']['checked']))            print_liste_field_titre($arrayfields['e.date_end']['label'],$_SERVER["PHP_SELF"],'e.date_end','',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['e.active']['checked']))        print_liste_field_titre($arrayfields['e.active']['label'],$_SERVER["PHP_SELF"],'e.active','',$param,'',$sortfield,$sortorder);

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
	$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	if (! empty($arrayfields['e.datec']['checked']))     print_liste_field_titre($arrayfields['e.datec']['label'],$_SERVER["PHP_SELF"],'e.datec','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.tms']['checked']))       print_liste_field_titre($arrayfields['e.tms']['label'],$_SERVER["PHP_SELF"],"e.tms","",$param,'align="left" class="nowrap"',$sortfield,$sortorder);

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'',$param,'align="center"',$sortfield,$sortorder,'maxwidthsearch ');
	print_liste_field_titre('', $_SERVER["PHP_SELF"],"",'',$param,'align="center"',$sortfield,$sortorder,'maxwidthsearch ');

	print '</tr>'."\n";

	$productstat_cache=array();

	$generic_dolitour = new DoliTour($db);
	$generic_user = new User($db);


	$i=0;
	$totalarray=array();
	$totalarray['nbfield']=0;
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);


		$generic_dolitour->id = $obj->rowid;
		$generic_dolitour->ref = $obj->ref;
		$generic_dolitour->rank = $obj->rank;
		$generic_dolitour->datec = $db->jdate($obj->datec);

		print '<tr class="oddeven">';

		// Ref
		if (! empty($arrayfields['e.ref']['checked']))
		{
			print '<td class="nowrap">';

			print $generic_dolitour->getNomUrl(1);

			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Rank
		if (! empty($arrayfields['e.rank']['checked']))
		{
			print '<td class="nowrap">';

			print $obj->rank;

			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Titre
		if (! empty($arrayfields['e.title']['checked']))
		{
			print '<td align="left">';
			print $obj->title;
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

        // Description
        if (! empty($arrayfields['e.description']['checked']))
        {
            print '<td align="left">';
            print $obj->description;
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }

        // elementtoselect
        if (! empty($arrayfields['e.elementtoselect']['checked']))
        {
            print '<td align="left">';
            print $obj->elementtoselect;
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }

        // Context
        if (! empty($arrayfields['e.context']['checked']))
        {
            print '<td align="left">';
            print $obj->context;
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }

        // Side
        if (! empty($arrayfields['e.side']['checked']))
        {
            print '<td align="left">';
            print $obj->side;
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }

        // Align
        if (! empty($arrayfields['e.align']['checked']))
        {
            print '<td align="left">';
            print $obj->align;
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }

        // fk_user_group
        if (! empty($arrayfields['e.fk_user_group']['checked']))
        {
            print '<td align="left">';
            print $obj->fk_user_group;
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
		

        //
        if (! empty($arrayfields['e.date_start']['checked']))
        {
            print '<td align="left">';
            print dol_print_date($db->jdate($obj->date_start), 'day');
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }

        //
        if (! empty($arrayfields['e.date_end']['checked']))
        {
            print '<td align="left">';
            print dol_print_date($db->jdate($obj->date_end), 'day');
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }

        //
        if (! empty($arrayfields['e.active']['checked']))
        {
            print '<td align="left">';
            print yn($obj->active);
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		// 
		if (! empty($arrayfields['e.datec']['checked']))
		{
			print '<td align="left">';
			print dol_print_date($db->jdate($obj->datec), 'day');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Date modification
		if (! empty($arrayfields['e.tms']['checked']))
		{
			print '<td align="left" class="nowrap">';
			print dol_print_date($db->jdate($obj->tms), 'dayhour', 'tzuser');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Action column
		print '<td class="nowrap" align="center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected=0;
			if (in_array($obj->rowid, $arrayofselected)) $selected=1;
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
		}
		print '</td>';
		if (! $i) $totalarray['nbfield']++;

		print '<td class="linecolmove tdlineupdown center">';
		if ($i > 0) {
			print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?id='.$obj->rowid.'&amp;action=up">';
			print img_up('default', 0, 'imgupforline');
			print '</a>';
		}
		if ($i < $num - 1) {
			print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?id='.$obj->rowid.'&amp;action=down">';
			print img_down('default', 0, 'imgdownforline');
			print '</a>';
		}
		print '</td>';
		if (! $i) $totalarray['nbfield']++;

		print "</tr>\n";

		$i++;
	}

	$db->free($resql);

	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>'."\n";
	print '</div>';

	print '</form>'."\n";

}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
