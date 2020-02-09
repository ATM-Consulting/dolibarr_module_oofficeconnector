<?php

if (!defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))        define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');

include __DIR__ . '/loadmain.inc.php';

// Check module is active
if(empty($conf->oofficeconnector->enabled)){
    accessforbidden();
}

require_once  __DIR__ . '/class/OOfficeConnector.class.php';

$OOffice = new OOfficeConnector($db);

// Load translation files required by the page
$langs->loadLangs(array("oofficeconnector@oofficeconnector"));

$modulepart=GETPOST('modulepart', 'alpha');
$file=GETPOST('file', 'alpha');
$attachment=GETPOST('attachment', 'int');

$filename = basename($file);

// TODO factor for document-view.php too
if($modulepart === 'documentstemplates'){
    $rootPath = DOL_DATA_ROOT.'/';
}

//$OOffice->getDocumentInfo($modulepart,$file,$user,$params = array(), $conf->entity);


// TODO : testing file exists
// TODO : Check file access a good file ex no ../ or ./ in relative path
// TODO ! IMPORTANT check security key before export file !!!!!!!!!!!!!!!!!!!!!!
$filePath = $rootPath.$file;

if (file_exists($filePath)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($filePath).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}
else{
    print 'file not exist '.$filePath;
}