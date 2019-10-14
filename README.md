# phpFrejaeid

Simple PHP wrapper to talk to [Freja eID](https://frejaeid.com/en/developers-section/) for use both in test and production.

- Supports validation of the JWS but requires external library for that part.
- Supports both directed and inferred authentication, for use with qr-code and app.
- Supports authentication and signature api but not the assertion service.
- Not developed, supported or endorsed by Verisec.

## Example

### Init connection to API (test)
```PHP
$testAuth = new frejaeID(getcwd().'/testCertificate.pfx','SuperSecretPassword',false);
```

### Init, monitor and cancel authentication request
```PHP
$authResponse = $testAuth->initAuthRequest('EMAIL','youremail@yourserver.com');
$authStatus = $testAuth->checkAuthRequest($authResponse->authRef);
$testAuth->cancelAuthRequest($authResponse->authRef);
```

### Init, monitor and cancel signature request
```PHP
$signResponse = $testSign->initSignatureRequest('EMAIL','youremail@yourserver.com','Testsign','This is the agreement text');
$signStatus = $testSign->checkSignatureRequest($signResponse->signRef);
$testSign->cancelSignatureRequest($authResponse->signRef);
```
