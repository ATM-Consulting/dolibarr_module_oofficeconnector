<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       oofficeconnector/oofficeconnectorindex.php
 *	\ingroup    oofficeconnector
 *	\brief      Home page of oofficeconnector top menu
 */

include __DIR__ . '/loadmain.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("oofficeconnector@oofficeconnector"));

$action=GETPOST('action', 'alpha');

// Securite acces client
if (! $user->rights->oofficeconnector->document->read) accessforbidden();
$socid=GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0)
{
	$action = '';
	$socid = $user->socid;
}

$max=5;
$now=dol_now();


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

$arrayofjs = array('oofficeconnector/js/file-explorer.js.php');
$arrayofcss = array('oofficeconnector/css/file-explorer.css');

llxHeader("", $langs->trans("OOfficeConnectorTemplateArea"), $help_url = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs, $arrayofcss);


print load_fiche_titre($langs->trans("OOfficeConnectorTemplateArea"), '', 'oofficeconnector.png@oofficeconnector');

print '<div class="filemanager">

		<div class="breadcrumbs"></div>
		
		<div class="search">
			<input type="search" placeholder="'.$langs->trans("FindAFilePlaceholder").'" />
		</div>

		<ul class="data"></ul>

		<div class="nothingfound">
			<div class="nofiles"></div>
			<span>No files here.</span>
		</div>

	</div>';

// End of page
llxFooter();
$db->close();
