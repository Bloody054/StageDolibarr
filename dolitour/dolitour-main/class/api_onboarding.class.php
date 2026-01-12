<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
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

 use Luracast\Restler\RestException;

 dol_include_once("/dolitour/class/dolitour.class.php");

/**
 * API class for dolitours
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class DoliTourApi extends DolibarrApi
{

    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array();

    /**
     * Constructor
     */
    function __construct()
    {
		  global $db, $conf;
		  $this->db = $db;
    }

    /**
     * Get properties of a dolitour object
     *
     * Return an array with dolitour dolitours
     *
     * @param 	int 	$id ID of dolitour
     * @return 	array|mixed data without useless dolitour
     *
     * @throws 	RestException
     */
	function get($id)
	{
		global $conf, $langs;
		
		if(! DolibarrApiAccess::$user->rights->dolitour->lire) {
			throw new RestException(401, $langs->transnoentities('AccessNotAllowed'));
		}

        $dolitour = new DoliTour($this->db);
		$result = $dolitour->fetch($id);
		if (! $result) {
			throw new RestException(404, $langs->transnoentities('DoliTourNotFound'));
		}

		return $this->_cleanObjectDatas($dolitour);
    }
    
    /**
     * Create dolitour object
     *
     * @param   array   $request_data   Request data
     * @return  int     ID of dolitour
     */
    /*function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->dolitour->creer) {
			throw new RestException(401, "Insuffisant rights");
		}

        $data = (object) $request_data;

        
        $dolitour = new DoliTour($this->db);

        if ($dolitour->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating dolitour", array_merge(array($dolitour->error), $dolitour->errors));
        }

        return $this->get($dolitour->id);
    }*/

	/**
	 * Update dolitour
	 *
	 * @param int   $id             Id of dolitour to update
	 * @param array $request_data   Datas
	 * @return int
	 */
    /*function put($id, $request_data = null)
    {
		if (!DolibarrApiAccess::$user->rights->dolitour->modifier) {
			throw new RestException(401, "Insuffisant rights");
		}
		
        $dolitour = new DoliTour($this->db);
		$result = $dolitour->fetch($id);
		if (! $result) {
			throw new RestException(404, 'DoliTour not found');
		}

		foreach ($request_data as $field => $value)
		{
			if ($field == 'id') continue;
			// The status must be updated using setstatus() because it
			// is not handled by the update() method.
			$dolitour->$field = $value;
		}

		// If there is no error, update() returns the number of affected
		// rows so if the update is a no op, the return value is zezo.
		if ($dolitour->update(DolibarrApiAccess::$user) >= 0)
		{
			return $this->get($id);
		}
		else
		{
			throw new RestException(500, $this->dolitour->error);
		}
	}*/
	
	/**
	 * Disable dolitour
	 *
	 * @param   int     $id DoliTour ID
	 * @return  array
	 */
    /*function delete($id)
    {
		if (!DolibarrApiAccess::$user->rights->dolitour->supprimer) {
			throw new RestException(401, "Insuffisant rights");
		}
		
        $dolitour = new DoliTour($this->db);
		$result = $dolitour->fetch($id);
		if (! $result) {
			throw new RestException(404, 'DoliTour not found');
		}

		if ($dolitour->disable(DolibarrApiAccess::$user) >= 0)
		{
			return $this->get($id);
		}
		else
		{
			throw new RestException(500, $this->dolitour->error);
		}	
	}
	*/
	/**
	 * List DoliTours
	 *
	 * Get a list of dolitours
	 *
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 * @param string   	$user_ids   DoliTour ids filter field. Example: '1' or '1,2,3'          {@pattern /^[0-9,]*$/i}
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @return  array               Array of DoliTours objects
	 */
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $dolitour_ids = 0, $sqlfilters = '')
    {
	    global $db, $conf;

	    $obj_ret = array();

		if(! DolibarrApiAccess::$user->rights->dolitour->lire) {
			throw new RestException(401, "Insuffisant rights");
	    }

	    // case of external user, $societe param is ignored and replaced by user's socid
	    //$socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $societe;

	    $sql = "SELECT t.rowid";
	    $sql.= " FROM ".MAIN_DB_PREFIX."dolitour as t";
	    $sql.= ' WHERE t.entity IN ('.getEntity('dolitour').') AND t.active = 1';
	    //if ($dolitour_ids) $sql.=" AND t.rowid IN (".$dolitour_ids.")";
		// Add sql filters
		/*
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
	        $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }*/

	    $sql.= $db->order($sortfield, $sortorder);
	    if ($limit)	{
	        if ($page < 0)
	        {
	            $page = 0;
	        }
	        $offset = $limit * $page;

	        $sql.= $db->plimit($limit + 1, $offset);
	    }

	    $result = $db->query($sql);

	    $now = dol_now();

	    if ($result)
	    {
	        $num = $db->num_rows($result);
	        $min = min($num, ($limit <= 0 ? $num : $limit));
	        while ($i < $min)
	        {
				$obj = $db->fetch_object($result);
				
	            $dolitour_static = new DoliTour($db);
	            if($dolitour_static->fetch($obj->rowid)) {

					$dir = $conf->dolitour->dir_output.'/'.dol_sanitizeFileName($dolitour_static->ref);
					$file = $dolitour_static->photo;

					$thumbnail = $dir."/".$file;
					$thumbnail_small = $dir."/".getImageFileNameForSize($file, '_small');
					$thumbnail_mini = $dir."/".getImageFileNameForSize($file, '_mini');

					if (file_exists($thumbnail)) {
						$dolitour_static->photo_rawdata = base64_encode(file_get_contents($thumbnail));
					} else {
						$dolitour_static->photo_rawdata = null;
					}

					if ($now >= $dolitour_static->date_start) {
					    if (!empty($dolitour_static->date_end)) {
					        if ($now <= $dolitour_static->date_end) {
                                $obj_ret[] = $this->_cleanObjectDatas($dolitour_static);
                            }
                        } else {
                            $obj_ret[] = $this->_cleanObjectDatas($dolitour_static);
                        }
                    }
                }
	            $i++;
	        }
	    }
	    else {
			throw new RestException(503, $langs->transnoentities('ErrorWhileRetrievingDoliTourList'));
		}
		/*
	    if( ! count($obj_ret)) {
	        throw new RestException(404, 'No DoliTour found');
	    }*/
	    return $obj_ret;
	}


    /**
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return    array    Array of cleaned object properties
     */
    function _cleanObjectDatas($object)
    {
		$ret = new stdClass();
		$ret->id = intval($object->id);
		$ret->title = $object->title;
		$ret->description = $object->description;
        $ret->date_start = intval($object->date_start);
        $ret->date_end = intval($object->date_end);
        $ret->datec = intval($object->datec);
		$ret->photo_rawdata = $object->photo_rawdata;
		$ret->rank = intval($object->rank);

        return $ret;
    }
}
