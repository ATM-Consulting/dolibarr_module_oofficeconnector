<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020 John BOTELLA
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    oofficeconnector/admin/setup.php
 * \ingroup oofficeconnector
 * \brief   OOfficeConnector setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/oofficeconnector.lib.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "oofficeconnector@oofficeconnector"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$arrayofparameters = array(
	'OOFFICECONNECTOR_MYPARAM1'=>array('css'=>'minwidth200', 'enabled'=>1),
	'OOFFICECONNECTOR_MYPARAM2'=>array('css'=>'minwidth500', 'enabled'=>1)
);



/*
 * Actions
 */

if ((float) DOL_VERSION >= 6)
{
	include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';
}



/*
 * View
 */

$page_name = "OOfficeConnectorSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_oofficeconnector@oofficeconnector');

// Configuration header
$head = oofficeconnectorAdminPrepareHead();
dol_fiche_head($head, 'settings', '', -1, "oofficeconnector@oofficeconnector");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("OOfficeConnectorSetupPage").'</span><br><br>';

// Work in progresse message
print '<div class="error" >ATTENTION : ce module est en cours de développement, il ne doit pas être déployé en production ou sur un serveur public au risque d\'exposer votre server à des hackers</div>';

/*
 *  Numbering module
 */

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setModuleOptions">';


print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

//_printOnOff('ONLYOFFICE_', $langs->trans('ggetgt'));

if(empty($conf->global->OOFFICE_DOLIBARR_URL_CALL))
{
    dolibarr_set_const($db, 'OOFFICE_DOLIBARR_URL_CALL', DOL_MAIN_URL_ROOT);
}
$metas = array(
    'placeholder' => DOL_MAIN_URL_ROOT
);
_printInputFormPart('OOFFICE_DOLIBARR_URL_CALL', $langs->trans('OOfficeDolibarrUrl'), '', $metas, '', $langs->trans('OOfficeDolibarrUrlHelp'));



$params = array();
if (! empty($conf->use_javascript_ajax)){
    $params['append'] = '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
    $params['append'].= "\n".'<script type="text/javascript">';
    $params['append'].= '
    $(document).ready(function () {
		$("#generate_token").click(function() {
		    $.get( "'.DOL_URL_ROOT.'/core/ajax/security.php", {
			    action: \'getrandompassword\',
			    generic: true
            },
			function(token) {
			    $("#ooffice-token").val(token);
            });
        });
    });
    ';
    $params['append'].= '</script>';
}



$metas = array(
    'required' => true,
    'placeholder' => "75gerg76gergg3reg4erghr351",
    'id'          => 'ooffice-token'
);
_printInputFormPart('OOFFICE_TOKEN', $langs->trans('OOfficeToken'), '', $metas, '', $langs->trans('OOfficeTokenHelp'), $params);

if(empty($conf->global->OOFFICE_DOCUMENT_SALT)) {
    dolibarr_set_const($db, 'OOFFICE_DOCUMENT_SALT', time());
}
$metas = array(
    'required' => true,
    'placeholder' => time()
);
_printInputFormPart('OOFFICE_DOCUMENT_SALT', $langs->trans('OnlyOfficeDocSalt'), '', $metas, '', $langs->trans('OnlyOfficeDocSaltHelp'));

_printOnOff('OOFFICE_ACTIVE_LOG');


print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ParameterForONLYOFFICE").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";


$metas = array(
    'required' => true,
    'placeholder' => "https://documentserver/"
);
_printInputFormPart('ONLYOFFICE_DOC_SERV_URL', $langs->trans('OnlyOfficeDocServerUrl'), '', $metas);


$metas = array(
    'required' => true,
    'placeholder' => "grezrg864ezrg3reg4erghr351"
);
_printInputFormPart('ONLYOFFICE_DOC_SERV_SECURE_KEY', $langs->trans('OnlyOfficeDocServerSecureKey'), '', $metas, '' , $langs->trans('OnlyOfficeDocServerSecureKeyHelp'));



print '</table>';
print '</div>';

print '<br>';

_updateBtn();

print '</form>';

dol_fiche_end();

// End of page
llxFooter();
$db->close();

/**
 * Print an update button
 *
 * @return void
 */
function _updateBtn()
{
    global $langs;
    print '<div class="center">';
    print '<button type="submit" class="button" >'.$langs->trans("Save").'</button>';
    print '</div>';
}

/**
 * Print a On/Off button
 *
 * @param string $confkey the conf key
 * @param bool   $title   Title of conf
 * @param string $desc    Description
 *
 * @return void
 */
function _printOnOff($confkey, $title = false, $desc = '')
{
    global $langs;

    print '<tr class="oddeven">';
    print '<td>'.($title?$title:$langs->trans($confkey));
    if (!empty($desc)) {
        print '<br><small>'.$langs->trans($desc).'</small>';
    }
    print '</td>';
    print '<td class="center" width="20">&nbsp;</td>';
    print '<td class="right" width="300">';
    print ajax_constantonoff($confkey);
    print '</td></tr>';
}


/**
 * Print a form part
 *
 * @param string $confkey the conf key
 * @param bool   $title   Title of conf
 * @param string $desc    Description of
 * @param array  $metas   html meta
 * @param string $type    type of input textarea or input
 * @param bool   $help    help description
 *
 * @return void
 */
function _printInputFormPart($confkey, $title = false, $desc = '', $metas = array(), $type = 'input', $help = false, $params = array())
{
    global $langs, $conf, $db, $inputCount;

    $inputCount = empty($inputCount)?1:($inputCount+1);
    $form=new Form($db);

    $defaultMetas = array(
        'name' => 'value'.$inputCount,
        'style' => 'min-width: 400px;'
    );

    if ($type!='textarea') {
        $defaultMetas['type']   = 'text';
        $defaultMetas['value']  = $conf->global->{$confkey};
    }


    $metas = array_merge($defaultMetas, $metas);
    $metascompil = '';
    foreach ($metas as $key => $values) {
        if($key== 'required'){
            $metascompil .= ' '.$key.'" ';
        }
        else{
            $metascompil .= ' '.$key.'="'.$values.'" ';
        }
    }

    print '<tr class="oddeven">';
    print '<td>';

    if (!empty($help)) {
        print $form->textwithtooltip(($title?$title:$langs->trans($confkey)), $langs->trans($help), 2, 1, img_help(1, ''));
    } else {
        print $title?$title:$langs->trans($confkey);
    }

    if (!empty($desc)) {
        print '<br><small>'.$langs->trans($desc).'</small>';
    }

    print '</td>';
    print '<td class="center" >&nbsp;</td>';
    print '<td class="right" >';
    print '<input type="hidden" name="param'.$inputCount.'" value="'.$confkey.'">';

    if ($type=='textarea') {
        print '<textarea '.$metascompil.'  >'.dol_htmlentities($conf->global->{$confkey}).'</textarea>';
    } else {
        print '<input '.$metascompil.'  />';
    }

    if(!empty($params['append'])){
        print $params['append'];
    }

    print '</td></tr>';
}
