<?php

require_once __DIR__ . '/../vendor/jtw/php-jwt-master/src/JWT.php';
require_once __DIR__ . '/OOfficeAjaxTool.class.php';

class OOfficeConnector {


    public $maxFileSize =  5242880;

    /**
     * @var $mode 'view' || 'edit'
     */
    public $mode;

    /**
     * @var array $extsSpreadSheet
     */
    public $extsSpreadSheet = array(
        ".xls", ".xlsx", ".xlsm",
        ".xlt", ".xltx", ".xltm",
        ".ods", ".fods", ".ots",
        ".csv"
    );

    /**
     * @var array $extsPresentation
     */
    public $extsPresentation = array(
        ".pps", ".ppsx", ".ppsm",
        ".ppt", ".pptx", ".pptm",
        ".pot", ".potx", ".potm",
        ".odp", ".fodp", ".otp"
    );

    /**
     * @var array $extsPresentation
     */
    public $extsDocument = array(
        ".doc", ".docx", ".docm",
        ".dot", ".dotx", ".dotm",
        ".odt", ".fodt", ".ott", ".rtf", ".txt",
        ".html", ".htm", ".mht",
        ".pdf", ".djvu", ".fb2", ".epub", ".xps"
    );

    /**
     * List of Documents in view mod only
     * @var array $documentServerViewed
     */
    public $documentServerViewed = array(".pdf", ".djvu", ".xps");

    /**
     * List of Documents allowed for edit mode
     * @var array $documentServerEdited
     */
    public $documentServerEdited = array(".docx", ".xlsx", ".csv", ".pptx", ".txt");

    /**
     * List of Documents allowed for conversion
     * @var array $documentServerConvert
     */
    public $documentServerConvert = array(".docm", ".doc", ".dotx", ".dotm", ".dot", ".odt", ".fodt", ".ott", ".xlsm", ".xls", ".xltx", ".xltm", ".xlt", ".ods", ".fods", ".ots", ".pptm", ".ppt", ".ppsx", ".ppsm", ".pps", ".potx", ".potm", ".pot", ".odp", ".fodp", ".otp", ".rtf", ".mht", ".html", ".htm", ".epub");

    /**
     * @var int $documentServerTimeout
     */
    public $documentServerTimeout = 120000;


    /**
     * Url of ONLYOFFICE document server
     * @var string $documentServerUrl
     */
    public $documentServerUrl;

    /**
     * @var string $documentServerConverterRelativeUrlPath
     */
    protected $documentServerConverterRelativeUrlPath = "ConvertService.ashx";

    /**
     * Document conversion service is a part of ONLYOFFICE Document Server.
     * It lets the user convert files from one format into another to open them later in document editors or for their export.
     * @var string $documentServerConverterUrl
     */
    public $documentServerConverterUrl;

    /**
     * @var string $documentServerApiRelativeUrlPath
     */
    protected $documentServerApiRelativeUrlPath = "web-apps/apps/api/documents/api.js";

    /**
     * @var string $documentServerApiUrl
     */
    public $documentServerApiUrl;

    /**
     * @var string $documentServerPreloaderRelativeUrlPath
     */
    protected $documentServerPreloaderRelativeUrlPath = "web-apps/apps/api/documents/cache-scripts.html";

    /**
     * @var string $documentServerPreloaderUrl
     */
    public $documentServerPreloaderUrl;

    /**
     * @var string $callBackUrlPath
     */
    protected $callBackUrlPath = "oofficeconnector/ajax/callback.json.php";

    /**
     * The ONLYOFFICE document editing service informs the Dolibarr about the status of the document editing
     * using the callbackUrl from JavaScript API. The document editing service use the POST request with the information in body.
     *
     * used in ONLYOFFICE js Callback handler
     *
     * @var string $callBackUrl
     */
    public $callBackUrl;

    /**
     * @var string $documentServerSecureKey;
     */
    public $documentServerSecureKey;

    /**
     * @var string $mobileDeviceRegex;
     */
    public $mobileDeviceRegex = "android|avantgo|playbook|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od|ad)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\\/|plucker|pocket|psp|symbian|treo|up\\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino";

    /**
     * Dolibarr url for ONLYOOFICE
     * @var string $externalDolibarrUrlCall;
     */
    public $externalDolibarrUrlCall;

    /**
     * Constructor
     *
     *   @param DoliDB $db
     *   Database handler
     */
    function __construct($db) {
        global $langs, $conf;

        $langs->loadLangs(array("oofficeconnector@oofficeconnector"));

        $this->db = $db;
        $this->error = 0;
        $this->errors = array();

        // LOAD CONF
        $this->documentServerUrl        = $conf->global->ONLYOFFICE_DOC_SERV_URL;
        $this->documentServerSecureKey  = $conf->global->ONLYOFFICE_DOC_SERV_SECURE_KEY;

        $this->externalDolibarrUrlCall  = $conf->global->OOFFICE_DOLIBARR_URL_CALL;
        if(!empty($this->externalDolibarrUrlCall)){
            if (filter_var($this->externalDolibarrUrlCall, FILTER_VALIDATE_URL)) {

                // Add / to URL to prevent errors
                if(substr($this->externalDolibarrUrlCall, -1) != '/'){
                    $this->externalDolibarrUrlCall = $this->externalDolibarrUrlCall.'/';
                }

                $stdDolibarURL = dol_buildpath('oofficeconnector',2);

                $this->externalDolibarrUrlCall = str_replace(DOL_MAIN_URL_ROOT.'/', $this->externalDolibarrUrlCall, $stdDolibarURL);

            }else {
                $this->error('ConfDolibarrCallUrlNotValid');
            }
        }else{
            $this->error('ConfDolibarrCallUrlNotDefined');
        }


        // Check ONLYOFFICE document server URL and generate all needed urls
        $this->generateDocumentServerUrls();

        // set callback url (to receive documents on save)
        $this->callBackUrl = dol_buildpath($this->callBackUrlPath, 2); // !important type 2 for external call

        if($this->error){
            return false;
        }

        return true;
    }


    /**
     * Build all ONLYOFFICE URLs from this class conf
     */
    public function generateDocumentServerUrls(){
        if(!empty($this->documentServerUrl)){
            if (filter_var($this->documentServerUrl, FILTER_VALIDATE_URL)) {

                // Add / to URL to prevent errors
                if(substr($this->documentServerUrl, -1) != '/'){
                    $this->documentServerUrl.'/';
                }

                // Construct all URLS to ONLYOFFICE documents server
                $this->documentServerConverterUrl   = $this->documentServerUrl . $this->documentServerConverterRelativeUrlPath;
                $this->documentServerApiUrl         = $this->documentServerUrl . $this->documentServerApiRelativeUrlPath;
                $this->documentServerPreloaderUrl   = $this->documentServerUrl . $this->documentServerPreloaderRelativeUrlPath;
            }
            else {
                $this->error('ConfDocServUrlNotValid');
            }
        }
        else{
            $this->error('ConfDocServUrlNotDefined');
        }
    }

    /**
     * @param $errorCode
     */
    public function error($errorCode){
        global $langs;
        $this->error++;
        $this->errors[] = $langs->trans($errorCode);
    }

    /**
     * convert errors to set event messages errors
     */
    public function setEventErrors(){
        if(!empty($this->errors)){
            setEventMessage($this->errors, 'errors');
        }
    }

    /**
     * Translation key to a supported form.
     *
     * @param string $expected_key  Expected key
     *
     * @return Supported key
     */
    static public function GenerateRevisionId($expected_key) {
        if (strlen($expected_key) > 20) $expected_key = crc32( $expected_key);
        $key = preg_replace("[^0-9-.a-zA-Z_=]", "_", $expected_key);
        $key = substr($key, 0, min(array(strlen($key), 20)));
        return $key;
    }




    function getInternalExtension($filename) {
        $ext = strtolower('.' . pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $this->extsDocument)) return ".docx";
        if (in_array($ext, $this->extsSpreadSheet)) return ".xlsx";
        if (in_array($ext, $this->extsPresentation)) return ".pptx";
        return "";
    }

    function getDocumentType($filename) {
        $ext = strtolower('.' . pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $this->extsDocument)) return "text";
        if (in_array($ext, $this->extsSpreadSheet)) return "spreadsheet";
        if (in_array($ext, $this->extsPresentation)) return "presentation";
        return "";
    }

    function getScheme() {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    }

    /**
     * Defines the unique document identifier used for document recognition by the service.
     * In case the known key is sent the document will be taken from the cache.
     * Every time the document is edited and saved, the key must be generated anew.
     * The document url can be used as the key but without the special characters and the length is limited to 20 symbols.
     * @param $file
     * @return Supported
     */
    static public function getDocEditorKey($file) {
        global $conf;
        if (is_file($file)) {
            $stat = filemtime($file);
        } else {
            $stat = md5($file);
        }

        $key = md5($file) . $stat;
        if(!empty($conf->global->OOFFICE_DOCUMENT_SALT)){
            $key.= $conf->global->OOFFICE_DOCUMENT_SALT;
        }
        return self::GenerateRevisionId($key);
    }

    /**
     * @param $msg
     * @param int $level 0 - 3 simple log infos, 4-7 errors
     * @param string $logFileName
     */
    static public function sendlog($msg, $level = 0, $logFileName = "ooffice.log") {

        global $conf;

        if(empty($conf->global->OOFFICE_ACTIVE_LOG)){
            return;
        }

        if(empty($conf->global->OOFFICE_ACTIVE_LOG)){
            $conf->global->OOFFICE_ACTIVE_LOG = 0;
        }


        if($level >= 0 && $level < 4){
            $errorPrefix = 'LOG    ';
        }
        elseif($level==4){
            $errorPrefix = 'WARNING';
        }
        else{
            $errorPrefix = 'ERROR  ';
        }

        $bt = debug_backtrace();
        $caller = array_shift($bt);

        $file = str_replace(DOL_DOCUMENT_ROOT , "", $caller['file']);

        $msg = $errorPrefix . ' Line ' .$caller['line']. ' from '.$file.' - '.date('Y-m-d H:i:s').' : ' . $msg;

        if(intval($conf->global->OOFFICE_ACTIVE_LOG_LEVEL) <= $level)
        {
            $logsFolder = DOL_DATA_ROOT . "/";
            if (!file_exists($logsFolder)) {
                mkdir($logsFolder);
            }

            // limit size of log file to 1Mo
            if (file_exists($logsFolder . $logFileName) && filesize($logsFolder . $logFileName) / pow(1024, 2) > 1) {
                file_put_contents($logsFolder . $logFileName, $msg . PHP_EOL);
            }
            else{
                file_put_contents($logsFolder . $logFileName, $msg . PHP_EOL, FILE_APPEND);
            }
        }
    }

    public function getFileExts() {
        return array_merge($this->documentServerViewed, $this->documentServerEdited, $this->documentServerConvert);
    }


    /**
     * The method is to convert the file to the required format
     *
     * Example:
     * string convertedDocumentUri;
     * GetConvertedUri("http://helpcenter.onlyoffice.com/content/GettingStarted.pdf", ".pdf", ".docx", "http://helpcenter.onlyoffice.com/content/GettingStarted.pdf", false, out convertedDocumentUri);
     *
     * @param string $document_uri            Uri for the document to convert
     * @param string $from_extension          Document extension
     * @param string $to_extension            Extension to which to convert
     * @param string $document_revision_id    Key for caching on service
     * @param bool   $is_async                Perform conversions asynchronously
     * @param string $converted_document_uri  Uri to the converted document
     *
     * @return The percentage of completion of conversion
     */
    function GetConvertedUri($document_uri, $from_extension, $to_extension, $document_revision_id, $is_async, &$converted_document_uri) {


        $converted_document_uri = "";
        $responceFromConvertService = $this->SendRequestToConvertService($document_uri, $from_extension, $to_extension, $document_revision_id, $is_async);

        $errorElement = intval($responceFromConvertService->error);

        $this->sendlog(json_encode($responceFromConvertService), 0);

        if ($errorElement<0){
            $errorMessage = self::ProcessConvServResponceError($errorElement);
            $this->sendlog($errorMessage, 5);
            throw new Exception($errorMessage);
        }

        $isEndConvert = $responceFromConvertService->endConvert;
        $percent = $responceFromConvertService->percent . "";

        if (!empty($isEndConvert))
        {
            $converted_document_uri = $responceFromConvertService->fileUrl;
            $percent = 100;
        }
        else if ($percent >= 100)
            $percent = 99;


        return $percent;
    }


    /**
     * Request for conversion to a service
     *
     * @param string $document_uri            Uri for the document to convert
     * @param string $from_extension          Document extension
     * @param string $to_extension            Extension to which to convert
     * @param string $document_revision_id    Key for caching on service
     * @param bool   $is_async                Perform conversions asynchronously
     *
     * @return Document request result of conversion
     */
    function SendRequestToConvertService($document_uri, $from_extension, $to_extension, $document_revision_id, $is_async) {
        if (empty($from_extension))
        {
            $path_parts = pathinfo($document_uri);
            $from_extension = $path_parts['extension'];
        }

        $title = basename($document_uri);
        if (empty($title)) {
            $title = self::guid();
        }

        if (empty($document_revision_id)) {
            $document_revision_id = $document_uri;
        }

        $document_revision_id = $this->GenerateRevisionId($document_revision_id);

        $urlToConverter = $this->documentServerConverterUrl;

        $data = json_encode(
            array(
                "async" => $is_async,
                "url" => $document_uri,
                "outputtype" => trim($to_extension,'.'),
                "filetype" => trim($from_extension, '.'),
                "title" => $title,
                "key" => $document_revision_id
            )
        );

        $opts = array('http' => array(
            'method'  => 'POST',
            'timeout' => $this->documentServerTimeout,
            'header'=> "Content-type: application/json\r\n" .
                "Accept: application/json\r\n",
            'content' => $data
        )
        );

        if (substr($urlToConverter, 0, strlen("https")) === "https") {
            $opts['ssl'] = array( 'verify_peer'   => FALSE );
        }

        $context  = stream_context_create($opts);
        $response_data = json_decode(file_get_contents($urlToConverter, FALSE, $context));

        return $response_data;
    }

    static public function guid() {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);// "}"
            return $uuid;
        }
    }


    /**
     * Generate an error code table
     *
     * @param string $errorCode   Error code
     *
     * @return null
     */
    static public function ProcessConvServResponceError($errorCode) {
        $errorMessageTemplate = "Error occurred in the document service: ";
        $errorMessage = '';

        switch (intval($errorCode))
        {
            case -8:
                $errorMessage = $errorMessageTemplate . "Error document VKey";
                break;
            case -7:
                $errorMessage = $errorMessageTemplate . "Error document request";
                break;
            case -6:
                $errorMessage = $errorMessageTemplate . "Error database";
                break;
            case -5:
                $errorMessage = $errorMessageTemplate . "Error unexpected guid";
                break;
            case -4:
                $errorMessage = $errorMessageTemplate . "Error download error";
                break;
            case -3:
                $errorMessage = $errorMessageTemplate . "Error convertation error";
                break;
            case -2:
                $errorMessage = $errorMessageTemplate . "Error convertation timeout";
                break;
            case -1:
                $errorMessage = $errorMessageTemplate . "Error convertation unknown";
                break;
            case 0:
                break;
            default:
                $errorMessage = $errorMessageTemplate . "ErrorCode = " . $errorCode;
                break;
        }

        return $errorMessage;
    }

}
