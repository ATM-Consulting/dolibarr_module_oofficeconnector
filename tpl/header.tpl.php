<?php

$publicUrl = dol_buildpath('oofficeconnector/', 1);

?>
<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<title><?php echo empty($title)?$title:''; ?> - <?php echo !empty($conf->global->MAIN_INFO_SOCIETE_NOM)?$conf->global->MAIN_INFO_SOCIETE_NOM:''; ?></title>

		<link rel="stylesheet" href="<?php print $publicUrl; ?>css/document-view.css">

		<!-- Plugin Notify -->
		<script src="<?php print $publicUrl; ?>vendor/noty/noty.min.js"></script>
		<link rel="stylesheet" type="text/css" href="<?php print $publicUrl; ?>vendor/noty/noty.css"/>
		<link rel="stylesheet" type="text/css" href="<?php print $publicUrl; ?>vendor/noty/themes/metroui.css"/>


        <!-- ONLYOFFICE JS API CALL -->
        <script src="<?php print $OOffice->documentServerApiUrl; ?>"></script>

	</head>
	<body>
