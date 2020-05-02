# phpFreja

Simple PHP wrapper to talk to [Freja eID](https://frejaeid.com/en/developers-section/) [REST API](https://frejaeid.com/rest-api/Freja%20eID%20Relying%20Party%20Developers'%20Documentation.html) for use both in test and production enviroment.

- Supports validation of the JWS but requires external library for that part (thanks to [php-jws](https://github.com/Gamegos/php-jws)).
- Supports both directed and inferred authentication, for use with qr-code and app.
- Supports authentication and signature api but not the assertion service.
- Well behaved functions that do not throw (atleast not by design) but always return objects for simpler handling.
- Not developed, supported or endorsed by Verisec.

To setup your test enviroment, and then basic agreement (free!!) read this information [Freja eID](https://org.frejaeid.com/en/developers-section/)

## Example

### Init connection to API (test)
```PHP
require_once('freja.php');

$frejaAPI = new phpFreja('testCertificate.pfx','SuperSecretPassword',false);
```
### Create URL for QR-Code
```PHP
$qrInfo = $frejaAPI->createAuthQRCode();

if ($qrInfo->success)
    $imageUrl = $qrInfo->url;
```

### Init, monitor and cancel authentication request
```PHP
$authResponse = $frejaAPI->initAuthentication('EMAIL','youremail@yourserver.com');

if ($authResponse->success)
    $authStatus = $frejaAPI->checkAuthRequest($authResponse->authRef);
    
$frejaAPI->cancelAuthentication($authResponse->authRef);
```

### Init, monitor and cancel signature request
```PHP
$signResponse = $frejaAPI->initSignatureRequest('EMAIL','youremail@yourserver.com','Testsign','This is the agreement text');

if ($signResponse->success)
    $signStatus = $frejaAPI->checkSignatureRequest($signResponse->signRef);

$frejaAPI->cancelSignatureRequest($authResponse->signRef);
```
