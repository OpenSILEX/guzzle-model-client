<?php
//**********************************************************************************************
//                               WSModel.php
//
// Author(s): Morgane VIDAL
// Copyright © - INRA - 2017
// Creation date: august 2017
// Contact: morgane.vidal@inra.fr, anne.tireau@inra.fr, pascal.neveu@inra.fr
// Last modification date:  November, 2017
// Subject: Basic class with basic REST web service functions
// Update : Arnaud CHARLEROY => add namespace
//***********************************************************************************************

namespace openSILEX\guzzleClientPHP;

include_once 'config.php';

use GuzzleHttp\Client;

/**
 * Basic class with basic REST web service functions.
 * It encapsulate Guzzle.
 * We assume that the web service uses a token system, with user session token in
 * the headers
 * @see http://docs.guzzlephp.org/en/stable/
 * @author Morgane Vidal <morgane.vidal@inra.fr>
 */
abstract class WSModel {
    
    /**
     * service url
     * e.g. "http://localhost/webservice/rest/"
     * @var String
     */
    protected $basePath;
    
    /**
     * service name
     * e.g. "experiment"
     * @var String
     */
    protected $serviceName;
    
    /**
     * Guzzle client to send requests
     * @var GuzzleHttp\Client
     */
    protected $client;
    
    /**
     * Initialise the fields correspondint to the path, service names and create
     * Guzzle client
     * @see inra\wsclient\config.php
     * @param String $basePath the path to the web service. e.g. http://localhost/webservice/rest/
     * @param String $serviceName the service name. e.g. experiment
     * @param string $accept web service return type. Default : RESPONSE_CONTENT_TYPE
     * @param string $contentType web service content type. Default : REQUEST_CONTENT_TYPE
     */
    public function __construct($basePath, $serviceName, $accept = RESPONSE_CONTENT_TYPE, $contentType = REQUEST_CONTENT_TYPE) {
        $this->basePath = $basePath;
        $this->serviceName = $serviceName;
        $this->client = new Client([
                                'base_uri' => $this->basePath,
                                'headers' => [
                                    'Accept' => $accept,
                                    'Content-Type' => $contentType,
                                    'Authorization' => "Bearer "
                                ]
                            ]);
    }
    
    public function getBasePath() {
        return $this->basePath;
    }
    
    /**
     * Allows to handle the errors. May be specified by the subclasses.
     * @param string $errorCode the code error
     * @param string $errorBody the error message
     * @return the error message to print
     */
    protected function errorMessage($errorCode, $errorBody) {
        if ($errorCode === 401 && isset($errorBody->{'metadata'}->{'status'}[0]->{'exception'}->{'details'})) {
            $errorDetails = $errorBody->{'metadata'}->{'status'}[0]->{'exception'}->{'details'};
            if ($errorDetails === "Invalid token") {
                $toReturn["token"] = "Invalid token";
                return $toReturn;
            }
        }
    }
    
    /**
     * Send a get request to the web service
     * @param String $sessionToken the user session token
     * @param String $subService the "sub service" called. e.g. /{uri}
     * @param Array  $params key => value with the data to send to the get, in the url
     * e.g.
     * [
     *  "page" => "0",
     *  "pageSize" => "1000",
     *  "uri" => "http://uri/of/my/entity"
     * ]
     * @return string if error the error message
     *                else the json of the web service result
     */
    public function get($sessionToken, $subService, $params = null, $bodyToSend = null) {
        //Prepare the query with the body
        $requestParamsPath = "";
        $body = json_encode($bodyToSend, $options = JSON_UNESCAPED_SLASHES);
        
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                if ($value !== null && $value !== "") {
                    ($requestParamsPath == "") ?
                        $requestParamsPath .= "?" . $key . "=" . urlencode($value)
                            : $requestParamsPath .= "&" . $key . "=" . urlencode($value);
                }
            }
        }
        
        //Send the request
        try {
            $requestRes = $this->client->request(
                    'GET',
                    $this->serviceName . $subService . $requestParamsPath,
                    [
                        'headers' => [
                            'Authorization' => "Bearer " . $sessionToken
                        ],
                        'body' => $body
            ]
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) { //Erreurs de type 401
            return $this->errorMessage(
                $e->getResponse()->getStatusCode(),
                                       json_decode($e->getResponse()->getBody())
            );
        } catch (\GuzzleHttp\Exception\ConnectException $e) { //Erreurs de connexion au serveur
            return WEB_SERVICE_CONNECTION_ERROR_MESSAGE;
        } catch (\GuzzleHttp\Exception $e) {
            return "Other exception : " . $e->getResponse()->getBody();
        }
        
        return json_decode($requestRes->getBody());
    }
   
    /**
     * Send a post request to the web service
     * @param String $sessionToken the user session token
     * @param String $subService the "sub service" called. e.g. /{uri}.
     *                           "" if no sub service
     * @param Array  $params key => value which contains the data to send to the post.
     * e.g.
     * [
     *  [
     *      "uri": "http://phenome-fppn.fr/phis_field/ao1",
     *      "resourceURI": "http://phenome-fppn.fr/phis_field/resource1",
     *      "geographicLocation": "POLYGON((0 0, 10 0, 10 10, 0 10, 0 0))",
     *      "typeAO": "micro plot",
     *      "specificInformations": "{}"
     *  ]
     * ]
     * @return mixed the message body returned by the web service (unencoded json)
     */
    public function post($sessionToken, $subService, $params) {
        //Generate the post body with the params
        $body = json_encode($params, $options = JSON_UNESCAPED_SLASHES);
        //Send request
        try {
            $requestRes = $this->client->request(
                    'POST',
                    $this->serviceName . $subService,
                    [
                        'headers' => [
                                'Authorization' => "Bearer " . $sessionToken
                            ],
                        'body' => $body
                    ]
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) { //Erreurs
            return $this->errorMessage(
   
                $e->getResponse()->getStatusCode(),
                                       json_decode($e->getResponse()->getBody())
   
            );
        } catch (\GuzzleHttp\Exception\ConnectException $e) { //Erreurs de connexion au serveur
            return WEB_SERVICE_CONNECTION_ERROR_MESSAGE;
        } catch (\GuzzleHttp\Exception $e) {
            return "Other exception : " . $e->getResponse()->getBody();
        }
        
        return json_decode($requestRes->getBody());
    }
    
    /**
     * Send a put request to the web service
     * @param String $sessionToken the user session token
     * @param String $subService the "sub service" called. e.g. /{uri}.
     *                           "" if no sub service
     * @param Array  $params key => value which contains the data to send to the post.
     * e.g.
     * [
     *  [
     *      "uri": "http://phenome-fppn.fr/phis_field/ao1",
     *      "resourceURI": "http://phenome-fppn.fr/phis_field/resource1",
     *      "geographicLocation": "POLYGON((0 0, 10 0, 10 10, 0 10, 0 0))",
     *      "typeAO": "micro plot",
     *      "specificInformations": "{}"
     *  ]
     * ]
     * @return mixed the message body returned by the web service (unencoded json)
     */
    public function put($sessionToken, $subService, $params) {
        //Generate the post body with the params
        $body = json_encode($params, $options = JSON_UNESCAPED_SLASHES);
        //Send request
        try {
            $requestRes = $this->client->request(
                    'PUT',
                    $this->serviceName . $subService,
                    [
                        'headers' => [
                                'Authorization' => "Bearer " . $sessionToken
                            ],
                        'body' => $body
                    ]
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) { //Erreurs
            return $this->errorMessage(
   
                $e->getResponse()->getStatusCode(),
                                       json_decode($e->getResponse()->getBody())
   
            );
        } catch (\GuzzleHttp\Exception\ConnectException $e) { //Erreurs de connexion au serveur
            return WEB_SERVICE_CONNECTION_ERROR_MESSAGE;
        } catch (\GuzzleHttp\Exception $e) {
            return "Other exception : " . $e->getResponse()->getBody();
        }
        
        return json_decode($requestRes->getBody());
    }
}
