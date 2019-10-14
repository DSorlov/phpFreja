<?php 
    class frejaeID {
 
        private $production;
        private $serviceUrl;
        private $certificate;
        private $password;
        private $currentAuth;
        private $jwtCert;
  
        public function __construct($certificate,$password,$production=false,$jwtCert=null){ 
            $this->production = $production;
            if ($production)
                $this->serviceUrl = 'https://services.prod.frejaeid.com';
            else
                $this->serviceUrl = 'https://services.test.frejaeid.com';
                
            if (!is_readable($certificate))
                throw new Exception('Certificate file could not be found');
            
            $this->certificate = $certificate; 
            $this->password = $password;
            
            if (!$this->IsNullOrEmptyString($jwtCert))
                if (!is_readable($jwtCert)) 
                    throw new Exception('JWT Certificate file could not be found');
                else
                    $this->jwtCert = $jwtCert;
        }
        
	private function IsNullOrEmptyString($input){
    		return (!isset($input) || trim($input)==='');
	}

        public function cancelAuthentication($authRef) {
            $query = new \stdClass(); $query->authRef = $authRef;
            
            $apiPost = array(
                "cancelAuthRequest" => base64_encode(json_encode($query))
            );
            
            $apiPostQuery = http_build_query($apiPost);
            $result = $this->apiRequest('/authentication/1.0/cancel',$apiPostQuery);
            return $result;
        }
        
        public function checkAuthentication($authRef) {
            $query = new \stdClass(); $query->authRef = $authRef;
            
            $apiPost = array(
                "getOneAuthResultRequest" => base64_encode(json_encode($query))
            );
            
            $apiPostQuery = http_build_query($apiPost);
            $result = $this->apiRequest('/authentication/1.0/getOneResult',$apiPostQuery);
            return $result;
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

            return $result;
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
            

echo var_dump($query);
            $apiPost = array(
                "initSignRequest" => base64_encode(json_encode($query))
            );
            $apiPostQuery = http_build_query($apiPost);
            $result = $this->apiRequest('/sign/1.0/initSignature',$apiPostQuery);

            return $result;
        }        
        
        public function checkSignatureRequest($signRef) {
            $query = new \stdClass(); $query->signRef = $signRef;
            
            $apiPost = array(
                "getOneSignResultRequest" => base64_encode(json_encode($query))
            );
            
            $apiPostQuery = http_build_query($apiPost);
            $result = $this->apiRequest('/sign/1.0/getOneResult',$apiPostQuery);
            return $result;
        }   

        public function cancelSignatureRequest($signRef) {
            $query = new \stdClass(); $query->signRef = $signRef;
            
            $apiPost = array(
                "cancelSignRequest" => base64_encode(json_encode($query))
            );
            
            $apiPostQuery = http_build_query($apiPost);
            $result = $this->apiRequest('/sign/1.0/cancel',$apiPostQuery);
            return $result;
        }    
        
        public function verifyJWT($jwsString) {
            if (!class_exists('Namshi\JOSE\SimpleJWS')) {
                $jws = SimpleJWS::load($jwsString);
                $public_key = openssl_pkey_get_public($this->jwtCert);
                
                if ($jws->isValid($public_key))
                    return true;
                else
                    return false;
            }
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
                CURLOPT_HEADER              => false, // true to show header information
                CURLINFO_HEADER_OUT         => false,
                CURLOPT_HTTPGET             => false,
                CURLOPT_POST                => true,
                CURLOPT_FOLLOWLOCATION      => false,
                CURLOPT_FOLLOWLOCATION      => true,
                CURLOPT_SSL_VERIFYHOST      => false, // true in production
                CURLOPT_SSL_VERIFYPEER      => false, // true in production
                CURLOPT_TIMEOUT             => 30,
                CURLOPT_MAXREDIRS           => 2,
                CURLOPT_HTTPHEADER          => $apiHeader,
                CURLOPT_USERAGENT           => 'phpFrejaeid/0.1',
                CURLOPT_POSTFIELDS          => $apiPostQuery,
                CURLOPT_SSLCERTTYPE         => 'P12',
                CURLOPT_SSLCERT             => $this->certificate,
                CURLOPT_KEYPASSWD           => $this->password
            );

            curl_setopt_array($curl, $options);
            $output = curl_exec($curl);
            $info =curl_errno($curl)>0 ? array("curl_error_".curl_errno($curl)=>curl_error($curl)) : curl_getinfo($curl);                       
            $json = json_decode($output);
            
            return $json;
        }
        
        
    }
   
?>
