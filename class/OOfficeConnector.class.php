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
     * @var int $documentServerTmeout
     */
    public $documentServerTmeout = 120000;


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
     * Constructor
     *
     *   @param DoliDB $db
     *   Database handler
     */
    function __construct($db) {
        global $langs;

        $langs->loadLangs("oofficeconnector@oofficeconnector");

        $this->db = $db;
        $this->error = 0;
        $this->errors = array();

        // LOAD CONF
        $this->documentServerUrl        = $this->global->ONLYOFFICE_DOC_SERV_URL;
        $this->documentServerSecureKey  = $this->global->ONLYOFFICE_DOC_SERV_SECURE_KEY;

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


    /**
     * TODO : a partir d'ici
     */

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
    public function SendRequestToConvertService($document_uri, $from_extension, $to_extension, $document_revision_id, $is_async) {
        if (empty($from_extension))
        {
            $path_parts = pathinfo($document_uri);
            $from_extension = $path_parts['extension'];
        }

        $title = basename($document_uri);
        if (empty($title)) {
            $title = guid();
        }

        if (empty($document_revision_id)) {
            $document_revision_id = $document_uri;
        }

        $document_revision_id = GenerateRevisionId($document_revision_id);

        $urlToConverter = $GLOBALS['DOC_SERV_CONVERTER_URL'];

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
            'timeout' => $GLOBALS['DOC_SERV_TIMEOUT'],
            'header'=> "Content-type: application/json\r\n" .
                "Accept: application/json\r\n",
            'content' => $data
        )
        );

        if (substr($urlToConverter, 0, strlen("https")) === "https") {
            $opts['ssl'] = array( 'verify_peer'   => FALSE );
        }

        $context  = stream_context_create($opts);
        $response_data = file_get_contents($urlToConverter, FALSE, $context);

        return $response_data;
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
        $responceFromConvertService = SendRequestToConvertService($document_uri, $from_extension, $to_extension, $document_revision_id, $is_async);

        $errorElement = $responceFromConvertService->Error;
        if ($errorElement != NULL && $errorElement != "") ProcessConvServResponceError($errorElement);

        $isEndConvert = $responceFromConvertService->EndConvert;
        $percent = $responceFromConvertService->Percent . "";

        if ($isEndConvert != NULL && strtolower($isEndConvert) == "true")
        {
            $converted_document_uri = $responceFromConvertService->FileUrl;
            $percent = 100;
        }
        else if ($percent >= 100)
            $percent = 99;

        return $percent;
    }


    /**
     * Processing document received from the editing service.
     *
     * @param string $document_response     The result from editing service
     * @param string $response_uri          Uri to the converted document
     *
     * @return The percentage of completion of conversion
     */
    function GetResponseUri($document_response, &$response_uri) {
        $response_uri = "";
        $resultPercent = 0;

        if (!$document_response) {
            $errs = "Invalid answer format";
        }

        $errorElement = $document_response->Error;
        if ($errorElement != NULL && $errorElement != "") ProcessConvServResponceError($document_response->Error);

        $endConvert = $document_response->EndConvert;
        if ($endConvert != NULL && $endConvert == "") throw new Exception("Invalid answer format");

        if ($endConvert != NULL && strtolower($endConvert) == true)
        {
            $fileUrl = $document_response->FileUrl;
            if ($fileUrl == NULL || $fileUrl == "") throw new Exception("Invalid answer format");

            $response_uri = $fileUrl;
            $resultPercent = 100;
        }
        else
        {
            $percent = $document_response->Percent;

            if ($percent != NULL && $percent != "")
                $resultPercent = $percent;
            if ($resultPercent >= 100)
                $resultPercent = 99;
        }

        return $resultPercent;
    }


    /**
     * @return string|string[]|null
     */
    function getClientIp() {
        $ipaddress =
            getenv('HTTP_CLIENT_IP')?:
                getenv('HTTP_X_FORWARDED_FOR')?:
                    getenv('HTTP_X_FORWARDED')?:
                        getenv('HTTP_FORWARDED_FOR')?:
                            getenv('HTTP_FORWARDED')?:
                                getenv('REMOTE_ADDR')?:
                                    'Storage';

        $ipaddress = preg_replace("/[^0-9a-zA-Z.=]/", "_", $ipaddress);

        return $ipaddress;
    }

}
