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
 *  \file       htdocs/couleurdevis/class/actions_couleurdevis.class.php
 *  \ingroup    couleurdevis
 *  \brief      File of class to manage actions on propal
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/dolitour/class/dolitour.class.php';

class ActionsDoliTour
{ 
	public function addHtmlHeader($parameters, &$object, &$action, $hookmanager)
    {
    global $conf, $db;

    $output = '';

//Add js and css into page
$output .= '<script src="https://cdn.jsdelivr.net/npm/driver.js@latest/dist/driver.js.iife.js"></script>';
$output .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@latest/dist/driver.css">';

// Load and prepare each onboard
$dolitour = new Onboarding($db);
$items = $dolitour->liste_array();

if(getDolGlobalInt('ONBOARDING_SHOW_ME_THE_CONTEXT')==1){
    echo '<pre>';
     echo 'Onboarding → Le contexte est le suivant : ';
     print_r($parameters['context']);
    echo '</pre>';
}

if(getDolGlobalString('ONBOARDING_OVERLAY_COLOR')){
    $overlaycolor=getDolGlobalString('ONBOARDING_OVERLAY_COLOR');
}else{
    $overlaycolor='#ECECEC';
}

if(getDolGlobalInt('ONBOARDING_SHOW_PROGRESS')){
    $showprogress='true';
}else{
    $showprogress='false';
}

if(getDolGlobalInt('ONBOARDING_ALLOW_CLOSE')){
    $allow_close='true';
}else{
    $allow_close='false';
}

$output .= '<script type="text/javascript">' . "\n";
$output .= '    $(document).ready(function () {' . "\n";
$output .= '        const driver = window.driver.js.driver;' . "\n";
$output .= '        const driverObj = driver({' . "\n";
$output .= '            showProgress: '.$showprogress.',' . "\n";
$output .= '            allowClose: '.$allow_close.',' . "\n";
$output .= '            overlayColor: "'.$overlaycolor.'",' . "\n";

// On click to close the button / update a field to avoid showing the user the window again // TODO
$output .= '            onCloseClick: () => {' . "\n";
$output .= '                console.log("Close Button Clicked");' . "\n";
$output .= '                $.ajax({' . "\n";
$output .= '                    url: "' . dol_buildpath('/dolitour/ajax.php', 1) . '",' . "\n";
$output .= '                    method: "POST",' . "\n";
$output .= '                    data: { action: "driver_closed" }' . "\n";
$output .= '                });' . "\n";
$output .= '                driverObj.destroy();' . "\n";
$output .= '            },' . "\n";

//Preparing the dolitour stages
$output .= '            steps: [' . "\n";
        
        foreach ($items as $item) {
            //check if the context is ok
            if ($parameters['context'] == $item->context) {
                    if(empty($item->side)){
                        $side='left';
                    }else{
                        $side=$item->side;
                    }

                    if(empty($item->align)){
                        $align='start';
                    }else{
                        $align=$item->align;
                    }
                $output .= '{ element: "'.$item->elementtoselect.'", popover: { title: "'.dol_escape_js($item->title).'", description: "'.dol_escape_js($item->description).'", side: "'.$side.'", align: "'.$align.'" } },' . "\n";
            }
        }
$output .= '            ]' . "\n";

$output .= '        });' . "\n";
$output .= '        driverObj.drive();' . "\n";
$output .= '    });' . "\n";
$output .= '</script>' . "\n";

    print $output;
    }
}


