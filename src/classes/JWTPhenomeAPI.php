<?php
//**********************************************************************************************
//                                       JWTPhenomeAPI.php 
//
// Author(s): Arnaud CHARLEROY
// PHIS-SILEX version 1.0
// Copyright © - INRA - 2017
// Creation date: october 2017
// Contact: arnaud.charleroy@inra.fr, anne.tireau@inra.fr, pascal.neveu@inra.fr
// Last modification date:  November, 2017
// Subject: Represent a JWT for phenomeapi
//***********************************************************************************************
namespace openSILEX\guzzleClientPHP\classes;

use \Firebase\JWT\JWT;

/**
 * Represents a JWT for the phis web service
 * @author Arnaud CHARLEROY
 */
abstract class JWTPhenomeAPI {

    protected $payload;
    protected $algorithm;
    protected $private_key_path;
    protected $jwt;

    function __construct($payload, $algorithm, $private_key_path) {
        $this->payload = $payload;
        $this->algorithm = $algorithm;
        $this->private_key_path = $private_key_path;
    }

    function getPayload() {
        return $this->payload;
    }

    function getAlgorithm() {
        return $this->algorithm;
    }

    function getPrivate_key_path() {
        return $this->private_key_path;
    }

    function setPayload($payload) {
        $this->payload = $payload;
    }

    function setAlgorithm($algorithm) {
        $this->algorithm = $algorithm;
    }

    function setPrivate_key_path($private_key_path) {
        $this->private_key_path = $private_key_path;
    }

    function build() {
        try {
            $this->jwt = JWT::encode($this->payload, file_get_contents($this->private_key_path), $this->algorithm);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
        return $this->jwt;
    }
    
    public function __toString() {
        return $this->build();
    }

}
