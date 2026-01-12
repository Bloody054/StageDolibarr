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
 *  \file       htdocs/dolitour/class/dolitour.class.php
 *  \ingroup    dolitour
 *  \brief      File of class to manage slices
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';

include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';


/**
 * Class to manage products or services
 */
class DoliTour extends CommonObject
{
	public $element='dolitour';
	public $table_element='dolitour';
	public $fk_element='fk_dolitour';
	public $picto = 'dolitour@dolitour';
	public $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'rowid';

	
	/**
     * Gestion id
     * @var int
     */
	public $id = 0;

	/**
	 * Reference
	 * @var string
	 */
	public $ref;

	/**
	 * Title
	 * @var string
	 */
	public $title;

	/**
	 * Description
	 * @var string
	 */
	public $description;

	/**
	 * element
	 * @var string
	 */
	public $elementtoselect;

	/**
	 * context
	 * @var string
	 */
	public $context;

	/**
	 * side
	 * @var string
	 */
	public $side;

	/**
	 * align
	 * @var string
	 */
	public $align;

	/**
	 * Rank 
	 * @var int
	 */
	public $rank = 0;

    /**
     * Start date
     * @var int
     */
    public $date_start;

    /**
     * End date
     * @var int
     */
    public $date_end;

	/**
	 * Creation date
	 * @var int
	 */
	public $datec;

	/**
	 * Author id
	 * @var int
	 */
	public $user_author_id = 0;

	/**
     * Entity
     * @var int
     */
	public $entity;

	/**
     * Active
     * @var int
     */
	public $active = 1;

	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $langs;

		$this->db = $db;
	}

	/**
	 *	Insert dolitour into database
	 *
	 *	@param	User	$user     		User making insert
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 * 
	 *	@return int			     		Id of gestion if OK, < 0 if KO
	 */
	function create($user, $notrigger=0)
	{
		global $conf, $langs, $mysoc;

        $error=0;

		dol_syslog(get_class($this)."::create", LOG_DEBUG);

		$this->db->begin();

		$this->datec = dol_now();
		$this->entity = $conf->entity;
		$this->user_author_id = $user->id;
		$this->ref = $this->getNextNumRef($mysoc);					
		$this->rank = 1;

		$now = dol_now();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."dolitour (";
		$sql.= " ref";
		$sql.= " , title";
		$sql.= " , description";
		$sql.= " , elementtoselect";
		$sql.= " , context";
		$sql.= " , side";
		$sql.= " , align";
		$sql.= " , rank";
        $sql.= " , date_start";
        $sql.= " , date_end";
        $sql.= " , datec";
        $sql.= " , fk_user_group";
		$sql.= " , user_author_id";
		$sql.= " , entity";
		$sql.= " , tms";
		$sql.= ") VALUES (";
		$sql.= " ".(!empty($this->ref) ? "'".$this->db->escape($this->ref)."'" : "null");
		$sql.= ", ".(!empty($this->title) ? "'".$this->db->escape($this->title)."'" : "null");
		$sql.= ", ".(!empty($this->description) ? "'".$this->db->escape($this->description)."'" : "null");
		$sql.= ", ".(!empty($this->elementtoselect) ? "'".$this->db->escape($this->elementtoselect)."'" : "null");
		$sql.= ", ".(!empty($this->context) ? "'".$this->db->escape($this->context)."'" : "null");
		$sql.= ", ".(!empty($this->side) ? "'".$this->db->escape($this->side)."'" : "null");
		$sql.= ", ".(!empty($this->align) ? "'".$this->db->escape($this->align)."'" : "null");
        $sql.= ", ".(!empty($this->rank) ? $this->rank : "0");
        $sql.= ", ".(!empty($this->date_start) ? "'".$this->db->idate($this->date_start)."'" : "null");
        $sql.= ", ".(!empty($this->date_end) ? "'".$this->db->idate($this->date_end)."'" : "null");
		$sql.= ", ".(!empty($this->datec) ? "'".$this->db->idate($this->datec)."'" : "null");
		$sql.= ", ".(!empty($this->fk_user_group) ? $this->fk_user_group : "0");
		$sql.= ", ".(!empty($this->user_author_id) ? $this->user_author_id : "0");
		$sql.= ", ".(!empty($this->entity) ? $this->entity : "0");
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ")";

		dol_syslog(get_class($this)."::Create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ( $result )
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."dolitour");

			if ($id > 0)
			{
				$this->id = $id;

				$sql = "UPDATE ".MAIN_DB_PREFIX."dolitour s SET s.rank = s.rank + 1 WHERE s.rowid <> ".$this->id." AND s.entity IN (".getEntity('dolitour').")";
				$this->db->query($sql);
			}
			else
			{
				$error++;
				$this->error='ErrorFailedToGetInsertedId';
			}
		}
		else
		{
			$error++;
			$this->error=$this->db->lasterror();
		}

		if (! $error)
		{
			$result = $this->insertExtraFields();
			if ($result < 0) $error++;
		}

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Call trigger
	            $result = $this->call_trigger('ONBOARDING_CREATE',$user);
	            if ($result < 0) $error++;
	            // End call triggers
			}
		}

		if (! $error)
		{
			$this->db->commit();
			return $this->id;
		}
		else
		{
			$this->db->rollback();
			return -$error;
		}

	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    	Load the third party of object, from id $this->socid or $this->fk_soc, into this->thirdparty
	 *
	 *		@param		int		$force_thirdparty_id	Force thirdparty id
	 *		@return		int								<0 if KO, >0 if OK
	 */
	public function fetch_thirdparty($force_thirdparty_id = 0)
	{
        // phpcs:enable
		global $conf;

		return 1;
	}		

	/**
	 *	Update a record into database.
	 *
	 *	@param  User	$user       Object user making update
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *	@return int         		1 if OK, -1 if ref already exists, -2 if other error
	 */
	function update($user, $notrigger=0)
	{
		global $langs, $conf, $hookmanager;

		$error=0;


		// Clean parameters
		$id = $this->id;

		// Check parameters
		if (empty($id))
		{
			$this->error = "Object must be fetched before calling update";
			return -1;
		}


		$this->db->begin();
		
		$sql = "UPDATE ".MAIN_DB_PREFIX."dolitour";
		$sql.= " SET ref = ".(!empty($this->ref) ? "'".$this->db->escape($this->ref)."'" : "null");
		$sql.= ", title = ".(!empty($this->title) ? "'".$this->db->escape($this->title)."'" : "null");
		$sql.= ", description = ".(!empty($this->description) ? "'".$this->db->escape($this->description)."'" : "null");
		$sql.= ", elementtoselect = ".(!empty($this->elementtoselect) ? "'".$this->db->escape($this->elementtoselect)."'" : "null");
		$sql.= ", context = ".(!empty($this->context) ? "'".$this->db->escape($this->context)."'" : "null");
		$sql.= ", side = ".(!empty($this->side) ? "'".$this->db->escape($this->side)."'" : "null");
		$sql.= ", align = ".(!empty($this->align) ? "'".$this->db->escape($this->align)."'" : "null");
		$sql.= ", rank = ".(!empty($this->rank) ? $this->rank : 0);
        $sql.= ", date_start = ".(!empty($this->date_start) ? "'".$this->db->idate($this->date_start)."'" : "null");
        $sql.= ", date_end = ".(!empty($this->date_end) ? "'".$this->db->idate($this->date_end)."'" : "null");
        $sql.= ", fk_user_group = ".(!empty($this->fk_user_group) ? "'".$this->db->escape($this->fk_user_group)."'" : "null");
        $sql.= ", tms = '".$this->db->idate(dol_now())."'";
		$sql.= " WHERE rowid = " . $id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql)
		{
			if (! $notrigger)
			{
				// Call trigger
				$result = $this->call_trigger('ONBOARDING_MODIFY',$user);
				if ($result < 0) $error++;
				// End call triggers
			}

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$langs->trans("Error")." : ".$this->db->error()." - ".$sql;
			$this->errors[]=$this->error;
			$this->db->rollback();

			return -1;				
		}
	}

	/**
	 *  Load a slice in memory from database
	 *
	 *  @param	int		$id      			Id of slide
	 *  @return int     					<0 if KO, 0 if not found, >0 if OK
	 */
	function fetch($id, $ref='')
	{
		global $langs, $conf;

		dol_syslog(get_class($this)."::fetch id=".$id);


		// Check parameters
		if (!$id && !$ref)
		{
			$this->error='ErrorWrongParameters';
			//dol_print_error(get_class($this)."::fetch ".$this->error);
			return -1;
		}

		$sql = "SELECT s.rowid, s.ref, s.title, s.description, s.rank, s.elementtoselect, s.context, s.side, s.align, s.date_start, s.date_end, s.datec, s.user_author_id, s.entity, s.active";
		$sql.= " FROM ".MAIN_DB_PREFIX."dolitour s";
		if ($id) $sql.= " WHERE s.rowid=".$id;
		else $sql.= " WHERE s.entity IN (".getEntity('dolitour').")"; // Dont't use entity if you use rowid

		if ($ref)     $sql.= " AND s.ref='".$this->db->escape($ref)."'";

		$resql = $this->db->query($sql);
		if ( $resql )
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$obj = $this->db->fetch_object($resql);

				$this->id				= $obj->rowid;
				$this->title 			= $obj->title;
				$this->description 		= $obj->description;
				$this->elementtoselect 			= $obj->elementtoselect;
				$this->context 			= $obj->context;
				$this->side 			= $obj->side;
				$this->align 			= $obj->align;
				$this->rank 			= $obj->rank;
                $this->date_start 		= $this->db->jdate($obj->date_start);
                $this->date_end 		= $this->db->jdate($obj->date_end);

				$this->user_author_id 		= $obj->user_author_id;
				$this->ref 				= $obj->ref;
				$this->datec 			= $this->db->jdate($obj->datec);
				$this->entity			= $obj->entity;
				$this->active			= $obj->active;
				
				$this->fetch_optionals();

				$this->db->free($resql);

				return 1;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Delete a gestion from database (if not used)
	 *
	 *	@param      User	$user       
	 *  @param  	int		$notrigger	    0=launch triggers after, 1=disable triggers
	 * 	@return		int					< 0 if KO, 0 = Not possible, > 0 if OK
	 */
	function delete(User $user, $notrigger=0)
	{
		global $conf, $langs;

		$error=0;

		// Clean parameters
		$id = $this->id;

		// Check parameters
		if (empty($id))
		{
			$this->error = "Object must be fetched before calling delete";
			return -1;
		}
		
		$this->db->begin();

		$sqlz = "DELETE FROM ".MAIN_DB_PREFIX."dolitour";
		$sqlz.= " WHERE rowid = ".$id;
		dol_syslog(get_class($this).'::delete', LOG_DEBUG);
		$resultz = $this->db->query($sqlz);

		if ( ! $resultz )
		{
			$error++;
			$this->errors[] = $this->db->lasterror();
		}		

		if (! $error)
		{
			$dir = $conf->dolitour->dir_output . '/' . dol_sanitizeFileName($this->ref);
			if (@is_dir($dir))
			{
				dol_delete_dir_recursive($dir);
			}

			if (! $notrigger)
			{
	            // Call trigger
	            $result = $this->call_trigger('ONBOARDING_DELETE',$user);
	            if ($result < 0) $error++;
	            // End call triggers
			}
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -$error;
		}

	}

	/**
	 *  Delete a gestion from database (if not used)
	 *
	 *	@param      User	$user       
	 *  @param  	int		$notrigger	    0=launch triggers after, 1=disable triggers
	 * 	@return		int					< 0 if KO, 0 = Not possible, > 0 if OK
	 */
	function disable(User $user, $notrigger=0)
	{
		global $conf, $langs;

		$error=0;

		// Clean parameters
		$id = $this->id;

		// Check parameters
		if (empty($id))
		{
			$this->error = "Object must be fetched before calling delete";
			return -1;
		}
		
		$this->db->begin();

		$sqlz = "UPDATE ".MAIN_DB_PREFIX."dolitour SET active = 0";
		$sqlz.= " WHERE rowid = ".$id;
		dol_syslog(get_class($this).'::disable', LOG_DEBUG);
		$resultz = $this->db->query($sqlz);

		if ( ! $resultz )
		{
			$error++;
			$this->errors[] = $this->db->lasterror();
		}		

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Call trigger
	            $result = $this->call_trigger('ONBOARDING_DISABLE',$user);
	            if ($result < 0) $error++;
	            // End call triggers
			}
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -$error;
		}

	}

     /**
     *      \brief Return next reference of confirmation not already used (or last reference)
     *      @param	   soc  		           objet company
     *      @param     mode                    'next' for next value or 'last' for last value
     *      @return    string                  free ref or last ref
     */
    function getNextNumRef($soc, $mode = 'next')
    {
        global $conf, $langs;

        $langs->load("dolitour@dolitour");

        // Clean parameters (if not defined or using deprecated value)
        if (empty($conf->global->ONBOARDING_ADDON)){
            $conf->global->ONBOARDING_ADDON = 'mod_dolitour_macaron';
        }else if ($conf->global->ONBOARDING_ADDON == 'macaron'){
            $conf->global->ONBOARDING_ADDON = 'mod_dolitour_macaron';
        }else if ($conf->global->ONBOARDING_ADDON == 'cookie'){
            $conf->global->ONBOARDING_ADDON = 'mod_dolitour_cookie';
        }

        $included = false;

        $classname = $conf->global->ONBOARDING_ADDON;
        $file = $classname.'.php';

        // Include file with class
        $dir = '/dolitour/core/modules/dolitour/';
        $included = dol_include_once($dir.$file);

        if (! $included)
        {
            $this->error = $langs->trans('FailedToIncludeNumberingFile');
            return -1;
        }

        $obj = new $classname();

        $numref = "";
        $numref = $obj->getNumRef($soc, $this, $mode);

        if ($numref != "")
        {
            return $numref;
        }
        else
        {
            return -1;
        }
	}

	/**
	 *	Charge les dolitours d'ordre info dans l'objet commande
	 *
	 *	@param  int		$id       Id of order
	 *	@return	void
	 */
	function info($id)
	{
		$sql = 'SELECT s.rowid, s.datec as datec, s.tms as datem,';
		$sql.= ' s.user_author_id as fk_user_author';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'dolitour as s';
		$sql.= ' WHERE s.rowid = '.$id;
		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation   = $cuser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
			}

			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

    /**
     *	Return clicable link of object (with eventually picto)
     *
     *	@param      int			$withpicto                Add picto into link
     *	@param      int			$max          	          Max length to show
     *	@param      int			$short			          ???
     *  @param	    int   	    $notooltip		          1=Disable tooltip
     *	@return     string          			          String with URL
     */
    function getNomUrl($withpicto=0, $option='', $max=0, $short=0, $notooltip=0)
    {
        global $conf, $langs, $user;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $result='';

        $url = dol_buildpath('/dolitour/card.php', 1).'?id='.$this->id;

        if ($short) return $url;

        $picto = 'dolitour@dolitour';
        $label = '';

		if ($user->rights->dolitour->lire) {
			$label = '<u>'.$langs->trans("ShowDoliTour").'</u>';
			$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		}

		$linkclose='';
		if (empty($notooltip) && $user->rights->dolitour->lire)
		{
		    if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
		    {
		        $label=$langs->trans("ShowDoliTour");
		        $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
		    }
		    $linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
		    $linkclose.=' class="classfortooltip"';
		}

        $linkstart = '<a href="'.$url.'"';
        $linkstart.= $linkclose.'>';
        $linkend = '</a>';

        if ($withpicto) $result .= ($linkstart.img_object(($notooltip?'':$label), $picto, ($notooltip?'':'class="classfortooltip"'), 0, 0, $notooltip?0:1).$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
		$result .= $linkstart .$this->ref. $linkend;
		
        return $result;
	}
	
    /**
     *	Return status label of DoliTour
     *
     *	@param      int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *	@return     string      		Libelle
     */
    function getLibStatut($mode)
    {
        return '';
	}
	
	/**
	 *  Return list of dolitours
	 *
	 *  @return     int             		-1 if KO, array with result if OK
	 */
	function liste_array($sortfield='s.rank', $sortorder='ASC')
	{
		global $user;

		$dolitours = array();
		$now = dol_now();
		$now_sql = $this->db->idate($now);
		$sql = "SELECT s.rowid as id, s.ref, s.datec, s.title, s.description, s.context, s.elementtoselect, s.date_start, s.date_end, s.rank, s.fk_user_group, s.user_author_id, s.entity, s.active ";
		$sql.= " FROM ".MAIN_DB_PREFIX."dolitour as s";
		$sql.= " WHERE s.entity IN (".getEntity('dolitour').")";
		$sql.= " AND s.active=1";
		$sql .= " AND (s.date_start IS NULL OR s.date_start <= '" . $now_sql . "')";
		$sql .= " AND (s.date_end IS NULL OR s.date_end >= '" . $now_sql . "')";
		$sql.= $this->db->order($sortfield,$sortorder);

		$result=$this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			if ($num)
			{
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);

					$datec = $this->db->jdate($obj->datec);
					$dolitours[$obj->id] = $obj;

					$i++;
				}
			}
			return $dolitours;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}
}
