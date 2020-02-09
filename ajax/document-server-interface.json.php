<?php


if (!defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))        define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');


include __DIR__ . '/../loadmain.inc.php';
include __DIR__ . '/../class/OOfficeAjaxTool.class.php';
include __DIR__ . '/../class/OOfficeConnector.class.php';


// Load translation files required by the page
$langs->loadLangs(array("oofficeconnector@oofficeconnector"));




if (isset($_GET["type"]) && !empty($_GET["type"])) { //Checks if type value exists
    $response_array;
    @header( 'Content-Type: application/json; charset==utf-8');
    @header( 'X-Robots-Tag: noindex' );
    @header( 'X-Content-Type-Options: nosniff' );

    OOfficeAjaxTool::nocache_headers();

    OOfficeConnector::sendlog(serialize($_GET));

    $type = GETPOST("type");

    switch($type) { //Switch case for value of type
        case "track":
            $response_array = _track();
            $response_array['status'] = isset($response_array['error']) ? 'error' : 'success';
            die (json_encode($response_array));
        default:
            $response_array['status'] = 'error';
            $response_array['error'] = '404 Method not found';
            die(json_encode($response_array));
    }
}


function _track() {
    global $db;

    $OOffice = new OOfficeConnector($db);

    $trackerStatus = array(
        0 => 'NotFound', //  no document with the key identifier could be found
        1 => 'Editing', // document is being edited, TODO : feature : store who is editing in database to show in dolibarr tootltip on file
        2 => 'MustSave', // document is ready for saving
        3 => 'Corrupted', // document saving error has occurred,
        4 => 'Closed', // document is closed with no changes, TODO : feature : clean who is editing in database
        6 => 'EditButSave', // document is being edited, but the current document state is saved,
        7 => 'ErrorSave' // error has occurred while force saving the document.
    );


    $fileName   = GETPOST("filename");
    $userid     = GETPOST("userid", 'int');
    $file       = GETPOST("file");
    $modulepart = GETPOST('modulepart', 'alpha');

    // TODO : upload check query with token FOR SECURITY

    /*
        $curentUser = new User($db);
        $res = $curentUser->fetch($userid);
        if($res>0){
            $result["error"] = 'Error no valid user given';
            return $result;
        }*/


    $OOffice->sendlog("Track START ".date("Y-m-d H:i:s"));
    $OOffice->sendlog("_GET params: " . serialize( $_GET ));


    $result["error"] = 0;

    if (($body_stream = file_get_contents('php://input'))===FALSE) {
        $result["error"] = "Bad Request";
        return $result;
    }

    $data = json_decode($body_stream, TRUE); //json_decode - PHP 5 >= 5.2.0

    if ($data === NULL) {
        $result["error"] = "Bad Response";
        return $result;
    }

    $OOffice->sendlog("InputStream data: " . serialize($data));

    $status = $trackerStatus[$data["status"]];

    $OOffice->sendlog("Track status : " . $status );

    switch ($status) {
        case "MustSave":
        case "Corrupted":
        case "EditButSave":

            $downloadUri = html_entity_decode($data["url"]);

            $curExt = strtolower('.' . pathinfo($fileName, PATHINFO_EXTENSION));
            $downloadExt = strtolower('.' . pathinfo($downloadUri, PATHINFO_EXTENSION));

            $OOffice->sendlog("downloadUri: " . $downloadUri);


            if ($downloadExt != $curExt) {

                $key = $OOffice->getDocEditorKey($downloadUri);

                try {
                    $OOffice->sendlog("Try Convert " . $downloadUri . " from " . $downloadExt . " to " . $curExt);

                    $convertedUri = '';
                    $percent = $OOffice->GetConvertedUri($downloadUri, $downloadExt, $curExt, $key, FALSE, $convertedUri);
                    if(!empty($convertedUri)){
                        $downloadUri = $convertedUri;
                        $OOffice->sendlog("Converted download uri ".$convertedUri, 0);
                    }
                    else{
                        $OOffice->sendlog("Convert error ", 5);
                    }

                    $OOffice->sendlog("END Try Convert ");
                } catch (Exception $e) {
                    $OOffice->sendlog("Convert after save ".$e->getMessage(), 5);
                    $result["error"] = "error: " . $e->getMessage();
                    return $result;
                }
            }



            // TODO use OOfficeDocuments class to factor this part

            $storagePath = '';

            if($modulepart === 'documentstemplates')
            {
                $storagePath = DOL_DATA_ROOT . '/'.$file;
            }

            if(empty($storagePath)){
                $result["error"] = 'Upload failed : invalid file';
                $result["status"] = 0;
                break;
            }


            $saved = 1;

            if (($new_data = file_get_contents($downloadUri)) === FALSE) {
                $saved = 0;
            } else {
                if(file_put_contents($storagePath, $new_data, LOCK_EX) !== false)
                {
                    $result["c"] = "saved";
                    $result["status"] = $saved;
                    break;
                }
                else{
                    $result["error"] = 'save failed '.$storagePath;
                    $result["status"] = 0;
                    break;
                }
            }

            $result["c"] = "saved";
            $result["status"] = $saved;
            break;
    }

    $OOffice->sendlog("Track result: " . serialize($result));
    $OOffice->sendlog("Track END");
    return $result;
}