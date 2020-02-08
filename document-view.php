<?php


if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', 1);

include __DIR__ . '/loadmain.inc.php';



// Load translation files required by the page
$langs->loadLangs(array("oofficeconnector@oofficeconnector"));

$modulepart=GETPOST('modulepart', 'alpha');
$file=GETPOST('file', 'alpha');
$file=urldecode($file);
$attachment=GETPOST('attachment', 'int');

// $goBackUrl is used by ONLYOFFICE ON close BTN
$goBackUrl = GETPOST('attachment', 'int');
$goBackUrl=urldecode($goBackUrl);

$filename = basename($file);


// TODO factor for file-server.php too
// Securite acces client
$accessforbidden = true;
if($modulepart === 'documentstemplates'
    && (!empty($user->rights->oofficeconnector->template->read) || !empty($user->rights->oofficeconnector->template->write))
){
    $rootPath = DOL_DATA_ROOT.'/';
    $accessforbidden = false;
}

if($accessforbidden) accessforbidden();

// TODO : testing file exists
// TODO : Check file access a good file ex no ../ or ./ in relative path
$filePath = $rootPath.$file;


require_once  __DIR__ . '/class/OOfficeConnector.class.php';

$OOffice = new OOfficeConnector($db);


$type = 'desktop'; // ($_GET["type"] == "mobile" ? "mobile" : ($_GET["type"] == "embedded" ? "embedded" : ($_GET["type"] == "desktop" ? "desktop" : "")))


$permissionsEdit = in_array(strtolower('.' . pathinfo($filename, PATHINFO_EXTENSION)), $OOffice->documentServerEdited) ? "true" : "false";

$editorConfigMode = 'view' ; //$GLOBALS['MODE'] != 'view' && in_array(strtolower('.' . pathinfo($filename, PATHINFO_EXTENSION)), $GLOBALS['DOC_SERV_EDITED']) && $_GET["action"] != "view" ? "edit" : "view";

$title = dol_htmlentities($filename);
include __DIR__ . '/tpl/header.tpl.php';

$downloadFileUrl = $OOffice->externalDolibarrUrlCall.'/file-server.php?modulepart='.$modulepart.'&file='.urlencode($file);



$params = [

    "width"=> "100%",
    "height"=>"100%",
    "document" => [
        // Defines the type of the file for the source viewed or edited document.
        "fileType" => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),

        // Defines the unique document identifier used for document recognition by the service.
        // In case the known key is sent the document will be taken from the cache.
        // Every time the document is edited and saved, the key must be generated anew.
        // The document url can be used as the key but without the special characters and the length is limited to 20 symbols.
        "key" =>  $OOffice->getDocEditorKey($filePath),

        // Defines the desired file name for the viewed or edited document which will also be used as file name when the document is downloaded.
        "title" => 'new.docx',//$filename,

        // Defines the absolute URL where the source viewed or edited document is stored.
        // !important : This Url must be accessible from ONLYOFFICE document serveur (if you use Docker remember it can access to localhost or 127.0.0.1 you must use a real IP )
        "url" => $downloadFileUrl,
    ],
    "documentType" => $OOffice->getDocumentType($filename),
    "editorConfig" => [
        //"callbackUrl" => getCallbackUrl($filename) ,
        "lang" => "fr",
        "mode" => (empty($callback) ? "view" : "edit"),
        "user" => [
            "id" => $user->id,
            "name" => $user->getFullName($langs)
        ]
    ],
    "type" => $type
];

if(!empty($goBackUrl)){
    $params["editorConfig"]["customization"]["goback"]["url"] = $goBackUrl;
}

$token = \Firebase\JWT\JWT::encode($params, $GLOBALS['MACHINEKEY']);
$params['token'] = $token ;
$params = json_encode($params);


?>
<script type="text/javascript">

    var docEditor;
    var fileName = "<?php echo $filename ?>";
    var fileType = "<?php echo strtolower(pathinfo($filename, PATHINFO_EXTENSION)) ?>";

    var innerAlert = function (message) {
        if (console && console.log)
            console.log(message);
    };

    var onAppReady = function () {
        innerAlert("Document editor ready");
    };

    var onDocumentStateChange = function (event) {
        var title = document.title.replace(/\*$/g, "");
        document.title = title + (event.data ? "*" : "");
    };

    var onRequestEditRights = function () {
        location.href = location.href.replace(RegExp("action=view\&?", "i"), "");
    };

    var onError = function (event) {
        if (event)
            innerAlert(event.data);
    };

    var onOutdatedVersion = function (event) {
        location.reload(true);
    };

    var сonnectEditor = function () {


        var user = [{id:"<?php print $user->id; ?>","name":"<?php print $user->getFullName($langs); ?>"}];
        var type = "<?php echo $type ?>";
        if (type == "") {
            type = new RegExp("<?php echo $OOffice->mobileDeviceRegex ?>", "i").test(window.navigator.userAgent) ? "mobile" : "desktop";
        }

        docEditor = new DocsAPI.DocEditor("iframeEditor", <?php echo $params; ?>);
    };

    if (window.addEventListener) {
        window.addEventListener("load", сonnectEditor);
    } else if (window.attachEvent) {
        window.attachEvent("load", сonnectEditor);
    }

</script>

<form id="ooffice-form">
    <div id="iframeEditor">
    </div>
</form>


<?php

include __DIR__ . '/tpl/footer.tpl.php';

$OOffice->setEventErrors();

// End of page
llxFooter();
$db->close();