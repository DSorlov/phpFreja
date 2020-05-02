<?php
 
require_once('freja.php');

$frejaApi = new phpFreja('certificate.pfx','CertificatePassword',false);

$result = $frejaApi->initAuthentication();

echo var_dump($result);

?>