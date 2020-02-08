<?php

if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');

include __DIR__ . '/../loadmain.inc.php';



// Load translation files required by the page
$langs->loadLangs(array("oofficeconnector@oofficeconnector"));

$action=GETPOST('action', 'alpha');

// Securite acces client
if (! $user->rights->oofficeconnector->template->read) accessforbidden();
$socid=GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0)
{
    $action = '';
    $socid = $user->socid;
}


// LIST AVAILABLES DOCUMENTS TEMPLATES FOLDERS
/*$availableDocumentsTemplatesFolders=array();

$sql = "SELECT name, entity, value FROM ".MAIN_DB_PREFIX."const WHERE name LIKE '%_ADDON_PDF_ODT_PATH' AND entity IN (0,".$conf->entity.");";
$resql = $db->query($sql);
if ($resql) {

    $num = $db->num_rows($resql);

    while ($obj = $db->fetch_object($resql)) {
        $pos = strpos($obj->name, '_ADDON_PDF_ODT_PATH');
        if(!empty($pos)){
            $availableDocumentsTemplatesFolders[substr('abcdef', 0, $pos)] =  str_replace("DOL_DATA_ROOT", DOL_DATA_ROOT, $obj->value);
        }
    }
}*/

$dir = DOL_DATA_ROOT . "/doctemplates";
$rootDir = DOL_DATA_ROOT . "/";
$relativeDir = str_replace($rootDir, '', $dir);
// Run the recursive function 

$response = ajaxScan($dir, $rootDir);


// This function scans the files folder recursively, and builds a large array

function ajaxScan($dir, $rootDir = ''){

	$files = array();

	if(empty($rootDir)){
        $rootDir = $dir;
    }

	// Is there actually such a folder/file?

	if(file_exists($dir)){

		foreach(scandir($dir) as $file) {

            $item = ajaxScanItemInfo($dir, $file, $rootDir);

            if(!is_array($item)) {
				continue; // Ignore hidden files
			}
            else{
                $files[] = $item;
            }
		}
	}

	return $files;
}


function ajaxScanItemInfo($dir, $file, $rootDir = ''){

    // Is there actually such a folder/file?
    if(!$file || $file[0] == '.') {
        return false; // Ignore hidden files
    }

    $relativeDir = str_replace($rootDir, '', $dir);

    if(is_dir($dir . '/' . $file)) {
        // The path is a folder
        return array(
            "name" => $file,
            "type" => "folder",
            "path" => $relativeDir . '/' . $file,
            "items" => ajaxScan($dir . '/' . $file, $rootDir) // Recursively get the contents of the folder
        );
    }
    else {
        // It is a file
        return  array(
            "name" => $file,
            "type" => "file",
            "path" => $relativeDir . '/' . $file,
            "size" => filesize($dir . '/' . $file), // Gets the size of this file
            "fileUrl" => dol_buildpath('oofficeconnector/document-view.php',1) . '?modulepart=documentstemplates&file='.urlencode($relativeDir . '/' . $file)
        );
    }
}

// Output the directory listing as JSON
header('Content-type: application/json');
header('Cache-Control: no-cache');

echo json_encode(array(
	"name" => $relativeDir,
	"type" => "folder",
	"path" => $relativeDir,
	"items" => $response
));
