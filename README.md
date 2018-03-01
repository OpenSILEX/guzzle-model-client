# guzzle-model-client
PHP wrapper for PHP Guzzle library

Setup
-----

The recommended way to install guzzle-model-client is through  [`Composer`](http://getcomposer.org). Just create a ``composer.json`` file and run the ``php composer.phar install`` command to install it:
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/openSILEX/guzzle-model-client"
        }
    ],
    "require": {
        "openSILEX/guzzle-model-client": "dev-master"
    }
}
```



## WSJWT (Web service JSON Web Token)

### Key pair authentication

A valid public need to be create key, if you already have a valid public key go to the next step **WSJWT implementation class example**

#### Create a key pair

To understand key pair concept : https://www.ssh.com/ssh/public-key-authentication

Create a private key (used by the server => web service):
```bash
openssl genpkey -algorithm RSA -out private_key.pem -pkeyopt rsa_keygen_bits:2048
```
Extract the public key from the private key (used by the client => webservice client):
```bash
openssl rsa -pubout -in private_key.pem -outform DER -out public_key.der
```

### WSJWT implementation class example

```php
<?php
use openSILEX\guzzleClientPHP\classes\WSJWT;


//**********************************************************************************************
//                                       WSJWTTestAPI.php 
//
// Author(s): Arnaud CHARLEROY
// SILEX version 1.0
// Copyright © - INRA - 2018
// Creation date: March 2018
// Contact: arnaud.charleroy@inra.fr, anne.tireau@inra.fr, pascal.neveu@inra.fr
// Last modification date:  March, 2018
// Subject: An implementation of WSJWT for a web service
//***********************************************************************************************


class WSJWTTestAPI extends WSJWT {

    function __construct($payload, $private_key_path = null, $algorithm = null) {
        parent::__construct($payload, $algorithm, $private_key_path);

        if (!is_null($algorithm)) {
            $this->algorithm = $algorithm;
        } else {
            $this->algorithm = ENCRYPTION_KEY_ALGORITHM; // example : 'RS256' 
        }

        if (!is_null($private_key_path)) {
            $this->private_key_path = $private_key_path;
        } else {
            $this->private_key_path = PRIVATE_KEY_PATH; // example : "{app_directory}/rsa_keys/Alfis-JWT-private-key.pem"
        }
    }
}
```

### WSModel token implementation class need to use WSJWTTestAPI class example

```php
<?php
//**********************************************************************************************
//                                       WSTokenModel.php 
//
// Author(s): Morgane VIDAL, Arnaud CHARLEROY
// PHIS-SILEX version 1.0
// Copyright © - INRA - 2018
// Creation date: February 2017
// Contact: morgane.vidal@inra.fr, anne.tireau@inra.fr, pascal.neveu@inra.fr
// Last modification date:  March, 2018
// Subject: access to the web service to manage connection tokens
*//***********************************************************************************************

use openSILEX\guzzleClientPHP\WSModel;

class WSTokenModel extends WSModel {

    /**
     * see Breeding API autorization constants
     */
    CONST GRANT_TYPE_JWT = "jwt";
    CONST GRANT_TYPE_PWD = "password";

    /**
     * @action initiate webservice connection
     */
    public function __construct() {$
        // WS_WS_URL_URL web service URL address
        parent::__construct(WS_URL, "brapi/v1/token");
    }

    /**
     * Retreive user session toke 
     * @param string $username username 
     * @param string $password user password
     * @param string $client_id optional text
     * @return string|null token string if parameters are valid and null if not
     */
    public function getToken($username, $password = null,  $client_id = null, $grant_type = "password" ) {
        $bodyRequest["grant_type"] = $grant_type;
        $bodyRequest["username"] = $username;

        // password is null if jwt is used => jwt 
        // this means that the user is already logged in the web application and password is already checked
        if (!is_null($password)) {
            $bodyRequest["password"] = $password;
        }
        // if client_id store jwt string
        if ($client_id !== null && $client_id !== "") {
            if($client_id instanceof \openSILEX\guzzleClientPHP\classes\WSJWT){
                $bodyRequest["client_id"] = $client_id->build();
            }else{
                $bodyRequest["client_id"] = $client_id;
            }
            
        }

        $bodyToSend = $bodyRequest;
        $requestRes = $this->post("", "", $bodyToSend);

        if (isset($requestRes->{'access_token'})) {
            return $requestRes->{'access_token'};
        } else {
            return null;
        }
    }

}
```

### Usage

```php
<?php
// need both implementations of previous classes
require_once 'WSTokenModel.php';
require_once 'WSJWTTestAPI.php';

// user is already logged in the web app for jwt example (note : it's possible to use password authentication type) 
// first call to webservice

// user identifier
$userId = 'arnaud.charleroy@inra.fr';

// create JWT dates
$date = new DateTime();
// creation date
$issued_at = $date->getTimestamp();
$date->modify('+20 minutes');
// expiration date
$expiration_time = $date->getTimestamp();
    
// create payload
$payload = array(
    "iss" => "testApp", // must be autorized and manage in Webservice authentication service
    "sub" => userId,
    "iat" => $issued_at,
    "exp" => $expiration_time
);

// create jwt
$jwt = new WSJWTTestAPI($payload);

// create a ws client for token service
$ws = new WSTokenModel();
// var_dump($jwt->build());

// send jwt et get web service token
$wsToken = $ws->getToken($userId, null, $jwt, WSTokenModel::GRANT_TYPE_JWT);

var_dump(wsToken);
?>
```
