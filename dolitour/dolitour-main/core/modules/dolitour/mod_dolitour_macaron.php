<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * or see http://www.gnu.org/
 */

/**
 *    	\file       htdocs/core/modules/dolitour/mod_dolitour_marbre.php
 *		\ingroup    dolitour
 *		\brief      File of class to manage commercial proposal numbering rules Marbre
 */

dol_include_once("/dolitour/core/modules/dolitour/modules_dolitour.php");

/**
 *	Class to manage customer order numbering rules Marbre
 */
class mod_dolitour_macaron extends ModeleNumRefDoliTours
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $prefix='ON';
	var $error='';
	var $nom = "Macaron";

	/**
	 *  Renvoi la description du modele de numerotation
	 *
	 *  @return     string      Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("dolitour@dolitour");
		return $langs->trans("SimpleNumRefModelDesc",$this->prefix);
	}

	/**
	 *  Renvoi un exemple de numerotation
	 *
	 *  @return     string      Example
	 */
	function getExample()
	{
		return $this->prefix."0501-0001";
	}

	/**
	 *  Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 *  de conflits qui empechera cette numerotation de fonctionner.
	 *
	 *  @return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		global $langs,$conf,$db;

		$langs->load("dolitour@dolitour");

		// Check invoice num
		$tayymm=''; $max='';

		$posindice=8;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."dolitour";
		$sql.= " WHERE ref LIKE '".$this->prefix."____-%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			if ($row) { $tayymm = substr($row[0],0,6); $max=$row[0]; }
		}
		if ($tayymm && ! preg_match('/'.$this->prefixinvoice.'[0-9][0-9][0-9][0-9]/i',$tayymm))
		{
			$langs->load("errors");
			$this->error=$langs->trans('ErrorNumRefModel',$max);
			return false;
		}

		return true;
	}

	/**
	 * Return next value not used or last value used
	 *
	 * @param	Societe		$objsoc		Object third party
	 * @param   DoliTour		$dolitour	Object slice
     * @param   string		$mode       'next' for next value or 'last' for last value
	 * @return  string       			Value
	 */
	function getNextValue($objsoc,$dolitour,$mode='next')
	{
		global $db;

		$prefix=$this->prefix;

		// D'abord on recupere la valeur max
		$posindice=8;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."dolitour";
		$sql.= " WHERE ref LIKE '".$prefix."____-%'";
		$sql.= " AND entity IN (".getEntity('dolitour').")";

		$resql=$db->query($sql);
		dol_syslog(get_class($this)."::getNextValue", LOG_DEBUG);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj) $max = intval($obj->max);
			else $max=0;
		}
		else
		{
			return -1;
		}

		if ($mode == 'last')
		{
    		if ($max >= (pow(10, 4) - 1)) $num=$max;	// If counter > 9999, we do not format on 4 chars, we take number as it is
    		else $num = sprintf("%04s",$max);

            $ref='';
            $sql = "SELECT ref";
            $sql.= " FROM ".MAIN_DB_PREFIX."dolitour";
            $sql.= " WHERE ref LIKE '".$prefix."____-".$num."'";
            $sql.= " AND entity IN (".getEntity('dolitour').")";

            dol_syslog(get_class($this)."::getNextValue", LOG_DEBUG);
            $resql=$db->query($sql);
            if ($resql)
            {
                $obj = $db->fetch_object($resql);
                if ($obj) $ref = $obj->ref;
            }
            else dol_print_error($db);

            return $ref;
		}
		else if ($mode == 'next')
		{
    		$date= dol_now();	// This is invoice date (not creation date)
    		$yymm = strftime("%y%m",$date);

    		if ($max >= (pow(10, 4) - 1)) $num=$max+1;	// If counter > 9999, we do not format on 4 chars, we take number as it is
    		else $num = sprintf("%04s",$max+1);

    		dol_syslog(get_class($this)."::getNextValue return ".$prefix.$yymm."-".$num);
    		return $prefix.$yymm."-".$num;
		}
		else dol_print_error('','Bad parameter for getNextValue');
	}

	/**
	 * Return next free value
	 *
     * @param	Societe		$objsoc     	Object third party
     * @param	string		$objforref		Object for number to search
     * @param   string		$mode       	'next' for next value or 'last' for last value
     * @return  string      				Next free value
	 */
	function getNumRef($objsoc,$objforref,$mode='next')
	{
		return $this->getNextValue($objsoc,$objforref,$mode);
	}

}
