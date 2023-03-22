<?php 

require_once('jws/JWS.php');

class phpFreja {

    private $production;
    private $serviceUrl;
    private $resourceUrl;
    private $certificate;
    private $password;
    private $currentAuth;
    private $jwsCert;

    public function __construct($certificate,$password,$production=false){ 
        $this->production = $production;
        if ($production) {
            $this->serviceUrl = 'https://services.prod.frejaeid.com';
            $this->resourceUrl = 'https://resources.prod.frejaeid.com';
            
            if (!is_readable(__DIR__."/freja_prod.pem"))
                throw new Exception('JWS Certificate file could not be found ('.__DIR__.'/freja_test.pem)');
            else
                $this->jwsCert = file_get_contents(__DIR__."/freja_prod.pem");
        } else {
            $this->serviceUrl = 'https://services.test.frejaeid.com';
            $this->resourceUrl = 'https://resources.test.frejaeid.com';

            if (!is_readable(__DIR__."/freja_test.pem"))
                throw new Exception('JWS Certificate file could not be found ('.__DIR__.'/freja_test.pem)');
            else
                $this->jwsCert = file_get_contents(__DIR__."/freja_test.pem");
        }
            
        if (!is_readable($certificate))
            throw new Exception('Certificate file could not be found');
        
        $this->certificate = $certificate; 
        $this->password = $password;
    }
    
    public function createAuthQRCode($existingCode=NULL) {
        
        if ($this->IsNullOrEmptyString($existingCode)) {
            $response = $this->initAuthentication();
            if (!$response->success)
                return $response;
            $existingCode = $response->authRef; 
        }

        $resultObject = $this->createSuccessObject();
        $resultObject->url = $this->resourceUrl . "/qrcode/generate?qrcodedata=frejaeid%3A%2F%2FbindUserToTransaction%3Fdimension%3D4x%3FtransactionReference%3D" . $existingCode;
        $resultObject->authRef = $existingCode;
        
        return $resultObject;
    }

    public function cancelAuthentication($authRef) {
        $query = new \stdClass(); $query->authRef = $authRef;
        
        $apiPost = array(
            "cancelAuthRequest" => base64_encode(json_encode($query))
        );
        
        $apiPostQuery = http_build_query($apiPost);
        $result = $this->apiRequest('/authentication/1.0/cancel',$apiPostQuery);
        
        if (!$result->success)
            return $this->createErrorObject($result->code,$result->data);
        
        return $this->createSuccessObject();
    }
    
    public function checkAuthentication($authRef) {
        $query = new \stdClass(); $query->authRef = $authRef;
        
        $apiPost = array(
            "getOneAuthResultRequest" => base64_encode(json_encode($query))
        );
        
        $apiPostQuery = http_build_query($apiPost);
        $result = $this->apiRequest('/authentication/1.0/getOneResult',$apiPostQuery);
       
        if (!$result->success)
            return $this->createErrorObject($result->code,$result->data);

        return $this->createSuccessObject($result->data);
    }
            
    public function initAuthentication($userType="N/A",$userInfo="N/A",$authLevel="BASIC") {        

    
        $emailAttribute = new \stdClass(); $emailAttribute->attribute = "EMAIL_ADDRESS";
        $userAttribute = new \stdClass(); $userAttribute->attribute = "RELYING_PARTY_USER_ID";
        $basicAttribute = new \stdClass(); $basicAttribute->attribute = "BASIC_USER_INFO";
        $dobAttribute = new \stdClass(); $dobAttribute->attribute = "DATE_OF_BIRTH";
        $ssnAttribute = new \stdClass(); $ssnAttribute->attribute = "SSN";

        $query = new \stdClass(); $query->attributesToReturn = array ( $emailAttribute );
        array_push($query->attributesToReturn, $userAttribute );

        switch ($userType) {
            case "N/A":
                $query->userInfoType = "INFERRED";
                $query->userInfo = "N/A";
                break;
            case "PHONE":
                $query->userInfoType = "PHONE";
                $query->userInfo = $userInfo;
                break;
            case "EMAIL":
                $query->userInfoType = "EMAIL";
                $query->userInfo = $userInfo;
                break;
            case "SSN":
                $query->userInfoType = "SSN";
                $ssnUserinfo = new \stdClass();
                $ssnUserinfo->country = "SE";
                $ssnUserinfo->ssn = $userInfo;                    
                $query->userInfo = base64_encode(json_encode($ssnUserinfo));
                break;                      
            default:
                throw new Exception('User type not N/A, EMAIL or PHONE');
                break;
        }
        switch ($authLevel) {
            case "BASIC":
                $query->minRegistrationLevel = "BASIC";
                break;
            case "EXTENDED":
                $query->minRegistrationLevel = "EXTENDED";
                array_push($query->attributesToReturn, $basicAttribute);
                array_push($query->attributesToReturn, $dobAttribute);
                array_push($query->attributesToReturn, $ssnAttribute);
                break;
            case "PLUS":
                $query->minRegistrationLevel = "PLUS";
                array_push($query->attributesToReturn, $basicAttribute);
                array_push($query->attributesToReturn, $dobAttribute);
                array_push($query->attributesToReturn, $ssnAttribute);
                break;
            default:
                throw new Exception('User type not BASIC, EXTENDED or PLUS');
                break;
        }

        $apiPost = array(
            "initAuthRequest" => base64_encode(json_encode($query))
        );
        $apiPostQuery = http_build_query($apiPost);
        $result = $this->apiRequest('/authentication/1.0/initAuthentication',$apiPostQuery);
        
        if (!$result->success)
            return $this->createErrorObject($result->code,$result->data);

        if (!isset($result->data->authRef))
            return $this->createErrorObject(400,"Missing authRef from API response.");
               
        return $this->createSuccessObject($result->data);;
    }
    
    public function initSignatureRequest($userType,$userInfo,$agreementText,$agreementTitle,$authLevel="BASIC",$timeoutMinutes=2,$confidential=false,$pushTitle=NULL,$pushMessage=NULL,$binaryData=NULL) {        
    
        $emailAttribute = new \stdClass(); $emailAttribute->attribute = "EMAIL_ADDRESS";
        $userAttribute = new \stdClass(); $userAttribute->attribute = "RELYING_PARTY_USER_ID";
        $basicAttribute = new \stdClass(); $basicAttribute->attribute = "BASIC_USER_INFO";
        $dobAttribute = new \stdClass(); $dobAttribute->attribute = "DATE_OF_BIRTH";
        $ssnAttribute = new \stdClass(); $ssnAttribute->attribute = "SSN";
        
        $query = new \stdClass(); $query->attributesToReturn = array ( $emailAttribute );
        array_push($query->attributesToReturn, $userAttribute );
       
        if ($this->IsNullOrEmptyString($agreementText) or $this->IsNullOrEmptyString($agreementTitle))
            throw new Exception('Agreement text and title must be specified');

        switch ($userType) {
            case "PHONE":
                $query->userInfoType = "PHONE";
                $query->userInfo = $userInfo;
                break;
            case "EMAIL":
                $query->userInfoType = "EMAIL";
                $query->userInfo = $userInfo;
                break;
            case "SSN":
                $query->userInfoType = "SSN";
                $ssnUserinfo = new \stdClass();
                $ssnUserinfo->country = "SE";
                $ssnUserinfo->ssn = $userInfo;                    
                $query->userInfo = base64_encode(json_encode($ssnUserinfo));
                break;                    
            default:
                throw new Exception('User type not EMAIL or PHONE');
                break;
        }
        switch ($authLevel) {
            case "BASIC":
                $query->minRegistrationLevel = "BASIC";
                break;
            case "EXTENDED":
                $query->minRegistrationLevel = "EXTENDED";
                array_push($query->attributesToReturn, $basicAttribute);
                array_push($query->attributesToReturn, $dobAttribute);
                array_push($query->attributesToReturn, $ssnAttribute);
                break;
            case "PLUS":
                $query->minRegistrationLevel = "PLUS";
                array_push($query->attributesToReturn, $basicAttribute);
                array_push($query->attributesToReturn, $dobAttribute);
                array_push($query->attributesToReturn, $ssnAttribute);
                break;
            default:
                throw new Exception('User type not BASIC, EXTENDED or PLUS');
                break;
        }
        
        $query->title = $agreementTitle;
        $query->confidential = $confidential;
        $query->expiry = (time() + ($timeoutMinutes * 60))*1000;
        
        if (!$this->IsNullOrEmptyString($pushTitle)) {               
            $pushNotification = new \stdClass(); $pushNotification->title = $pushTitle;
            $pushNotification->text = $pushMessage;
            $query->pushNotification = $pushNotification;
        }
        
        $dataToSign = new \stdClass(); $dataToSign->text = base64_encode($agreementText);
        if ($this->IsNullOrEmptyString($binaryData)) {
            $query->dataToSign = $dataToSign;
            $query->dataToSignType = "SIMPLE_UTF8_TEXT";
            $query->signatureType = "SIMPLE";
        } else {
            $dataToSign->binaryData = base64_encode($binaryData);
            $query->dataToSign = $dataToSign;
            $query->dataToSignType = "EXTENDED_UTF8_TEXT";
            $query->signatureType = "EXTENDED";
        }
        
        $apiPost = array(
            "initSignRequest" => base64_encode(json_encode($query))
        );
        $apiPostQuery = http_build_query($apiPost);
        $result = $this->apiRequest('/sign/1.0/initSignature',$apiPostQuery);

        if (!$result->success)
            return $this->createErrorObject($result->code,$result->data);

        if (!isset($result->data->signRef))
            return $this->createErrorObject(400,"Missing signRef from API response.");
               
        return $this->createSuccessObject($result->data);;
    }        
    
    public function checkSignatureRequest($signRef) {
        $query = new \stdClass(); $query->signRef = $signRef;
        
        $apiPost = array(
            "getOneSignResultRequest" => base64_encode(json_encode($query))
        );
        
        $apiPostQuery = http_build_query($apiPost);
        $result = $this->apiRequest('/sign/1.0/getOneResult',$apiPostQuery);
        
        if (!$result->success)
            return $this->createErrorObject($result->code,$result->data);
        
        if ($result->data->status!='APPROVED')
            return $this->createSuccessObject($result->data);
        
        $jws = new \Gamegos\JWS\JWS();
        try
        {
            $result->data->details = json_decode(json_encode($jws->verify($result->data->details, $this->jwsCert))); //
            $result->data->jwsMessage = "The signed information is valid";
            $result->data->jwsVerified = true;
        }
        catch (Exception $e)
        {
            try
            {
                $result->data->details = json_decode(json_encode($jws->decode($result->data->details)));
                $result->data->jwsMessage = $e->getMessage();
                $result->data->jwsVerified = false;
            }
            catch (Exception $e)
            {
                return $this->createErrorObject("400","JWS decoding of the remote data failed");
            }
        }
        
        $headers = $result->data->details->headers;
        $payload = $result->data->details->payload;
               
        $userTicket = explode(".", $result->data->details->payload->signatureData->userSignature);
        $userHeader = json_decode(base64_decode($userTicket[0]));
        $userPayload = base64_decode($userTicket[1]);
        $userSignature = $userTicket[2];
        
        $result->data->details->payload->signatureData = new \stdClass();
        $result->data->details->payload->signatureData->kid = $userHeader->kid;
        $result->data->details->payload->signatureData->alg = $userHeader->alg;
        $result->data->details->payload->signatureData->content = $userPayload;
        $result->data->details->payload->userInfo = json_decode($result->data->details->payload->userInfo);

        $result->data->details = new \stdClass();
        $result->data->details = $payload;
        $result->data->details->x5t = $headers->x5t;
        $result->data->details->alg = $headers->alg;
        
        return $this->createSuccessObject($result->data);        
    }   

    public function cancelSignatureRequest($signRef) {
        $query = new \stdClass(); $query->signRef = $signRef;
        
        $apiPost = array(
            "cancelSignRequest" => base64_encode(json_encode($query))
        );
        
        $apiPostQuery = http_build_query($apiPost);
        $result = $this->apiRequest('/sign/1.0/cancel',$apiPostQuery);
        if (!$result->success)
            return $this->createErrorObject($result->code,$result->data);
        
        return $this->createSuccessObject();
    }    
    
    private function apiRequest($apiUrl,$apiPostQuery){

        $curl = curl_init();

        $apiHeader = array();
        $apiHeader[] = 'Content-length: ' . strlen($apiPostQuery);
        $apiHeader[] = 'Content-type: application/json';

        // cURL Options
        $options = array(
            CURLOPT_URL                 => $this->serviceUrl . $apiUrl,
            CURLOPT_RETURNTRANSFER      => true,
            CURLOPT_HEADER              => false,
            CURLINFO_HEADER_OUT         => false,
            CURLOPT_HTTPGET             => false,
            CURLOPT_POST                => true,
            CURLOPT_FOLLOWLOCATION      => false,
            CURLOPT_SSL_VERIFYHOST      => false, // true in production will not work due to private certs at freja
            CURLOPT_SSL_VERIFYPEER      => false, // true in production will not work due to private certs at freja
            CURLOPT_TIMEOUT             => 30,
            CURLOPT_MAXREDIRS           => 2,
            CURLOPT_HTTPHEADER          => $apiHeader,
            CURLOPT_USERAGENT           => 'phpFreja/1.0',
            CURLOPT_POSTFIELDS          => $apiPostQuery,
            CURLOPT_SSLCERTTYPE         => 'P12',
            CURLOPT_SSLCERT             => $this->certificate,
            CURLOPT_KEYPASSWD           => $this->password
        );

        curl_setopt_array($curl, $options);
        $http_output = curl_exec($curl);
        $http_info = curl_getinfo($curl);
        
        if (curl_errno($curl)) {
                $response->success = false;
                $response->code = 500;            
                $response->data = curl_error($curl);
                return $response;
        }      
        
        $response = new \stdClass();
        switch($http_info["http_code"]) {
            case 200:
                $remoteResponse = json_decode($http_output);
                $response->success = true;
                $response->code = 200;
                $response->data = $remoteResponse;
                break;
            case 204:
                $response->success = true;
                $response->code = 200;
                $response->data = "";
                break;
            case 404:
            case 410:
                $response->success = false;
                $response->code = 404;
                $response->data = "Remote API reported the resource to be not found.";
                break;
            case 400:
                $response->success = false;
                $response->code = 400;            
                $response->data = "Remote API reported it cannot parse the request.";
                break;
            case 422:
                $remoteResponse = json_decode($http_output);
                $response->success = false;
                $response->code = 400;            
                $response->data = "Remote API reported processing errors: ".$remoteResponse->message;
                break;            
            case 500:
                $response->success = false;
                $response->code = 500;            
                $response->data = "Remote API reported a internal error.";
                break;
            default:
                $response->success = false;
                $response->code = 500;
                $response->data = "A unknown status was reported: ".$remoteResponse->code;
                $response->http_data = $http_output;
                break;
        }

        return $response;
    }

    private function IsNullOrEmptyString($input){
            return (!isset($input) || trim($input)==='');
    }
    
    private function createErrorObject($error_code,$error_message){
        $resultObject = new \stdClass();
        $resultObject->success = false;
        $resultObject->code = $error_code;
        $resultObject->message = $error_message;
        return $resultObject;
    }
    
    private function createSuccessObject($dataObject) {
        if (!isset($dataObject)) {
            $dataObject = new \stdClass();
        }
        $dataObject->success = true;
        return $dataObject;
    }
   
    
}

?>