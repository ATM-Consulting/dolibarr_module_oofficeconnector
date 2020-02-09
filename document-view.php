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
$goBackUrl = GETPOST('gobackurl');
$goBackUrl=urldecode($goBackUrl);

$filename = basename($file);

// Check module is active
if(empty($conf->oofficeconnector->enabled)){
    accessforbidden();
}

// TODO factor for file-server.php too
// TODO use OOfficeDocuments class to factor this part
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

$editorConfigMode =  "view";

if(!empty($user->rights->oofficeconnector->template->write))
{
    $editorConfigMode =  "edit";
}

$title = dol_htmlentities($filename);
include __DIR__ . '/tpl/header.tpl.php';

$downloadFileUrl = $OOffice->externalDolibarrUrlCall.'/file-server.php?modulepart='.$modulepart.'&file='.urlencode($file);

// TODO Add Security to this url
$saveFileUrl     = $OOffice->externalDolibarrUrlCall.'/ajax/document-server-interface.json.php?type=track&modulepart='.$modulepart.'&file='.urlencode($file).'&filename='.$filename ;



// Show https://api.onlyoffice.com/editors/config/editor/customization

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
        "title" => $filename,

        // Defines the absolute URL where the source viewed or edited document is stored.
        // !important : This Url must be accessible from ONLYOFFICE document serveur (if you use Docker remember it can access to localhost or 127.0.0.1 you must use a real IP )
        "url" => $downloadFileUrl,
    ],
    "documentType" => $OOffice->getDocumentType($filename),
    "editorConfig" => [
        "lang" => "fr", // TODO : use user language
        "mode" => $editorConfigMode,
        "user" => [
            "id" => $user->id,
            "name" => $user->getFullName($langs)
        ],
        "callbackUrl" => $saveFileUrl,

        "customization" => [
            "forcesave"=> true,


            "chat"=> true,
            "commentAuthorOnly"=> false,
            "comments"=> true,
            "compactHeader"=> false,
            "compactToolbar"=> false,
            "showReviewChanges"=> false,
            "toolbarNoTabs"=> false,
            "zoom"=> 100,
            // Next conf are not available in community edition

            // Contains the information which will be displayed int the editor About section and visible to all the editor users. The object has the following parameters:
            "customer"=> [
                "address"=> "My City, 123a-45",
                "info"=> "Some additional information",
                "logo"=> "https://example.com/logo-big.png",
                "mail"=> "john@example.com",
                "name"=> "John Smith and Co.",
                "www"=> "example.com"
            ],
            // Changes the image file at the top left corner of the Editor header. The recommended image height is 20 pixels. The object has the following parameters:
            "logo"=> [
                    "image"=> [
                    "https://example.com/logo.png", // path to the image file used to show in common work mode (i.e. in view and edit modes for all editors). The image must have the following size: 172x40,
                    "imageEmbedded"=>  "https://example.com/logo_em.png", // path to the image file used to show in the embedded mode (see the config section to find out how to define the embedded document type). The image must have the following size: 248x40,
                    "url"=>  "https://www.onlyoffice.com" // the absolute URL which will be used when someone clicks the logo image (can be used to go to your web site, etc.). Leave as an empty string or null to make the logo not clickable,
                ]
            ],

            /*
             // Defines settings for the Feedback & Support menu button. Can be either boolean (simply displays or hides the Feedback & Support menu button) or object. In case of object type the following parameters are available:
             "feedback"=> [
                "url"=> "https://example.com",
                "visible"=>  true
            ],*/
        ],
    ],
    "type" => $type
];
$goBackUrl = "http://atm-consulting.fr";
if(!empty($goBackUrl)){
    $params["editorConfig"]["customization"]["goback"]["url"] = $goBackUrl;
    $params["editorConfig"]["customization"]["goback"]["text"] = $langs->trans('GobackToDolibarr');
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