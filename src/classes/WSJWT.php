<?php

//**********************************************************************************************
//                                       WSJWT.php
//
// Author(s): Arnaud Charleroy
// PHIS-SILEX version 1.0
// Copyright © - INRA - 2018
// Creation date: october 2017
// Contact: arnaud.charleroy@inra.fr, anne.tireau@inra.fr, pascal.neveu@inra.fr
// Last modification date:  March, 2018
// Subject: Represent a JSON Web Token for phis
//***********************************************************************************************

namespace openSILEX\guzzleClientPHP\classes;

/**
 * It is a library to encode and decode JSON Web Tokens (JWT) in PHP, conforming to RFC 7519 (https://tools.ietf.org/html/rfc7519).
 * @link https://github.com/firebase/php-jwt
 */
use \Firebase\JWT\JWT;

/**
 * Represents a JSON Web Token for the phis web service authentication
 * @link https://jwt.io/introduction/ a description of JSON Web Token
 * @author Arnaud Charleroy <arnaud.charleroy@inra.fr>
 * @since 1.0.0
 */
abstract class WSJWT
{

    /**
     * The payload contains the claims. Claims are statements about an entity (typically, the user) and additional metadata.
     * There are three types of claims: registered, public, and private claims.
     * Applications using JWTs should define which
     * specific claims they use and when they are required or optional.  All
     * the names are short because a core goal of JWTs is for the
     * representation to be compact.
     * @link https://tools.ietf.org/html/rfc7519#section-4.1
     * @example  $payload = array(
     *  "iss" => "Issuer name", //  Ex: "iss" => "Phis",
     *  "sub" => "subject URI", // subject of the JWT  Ex: sub" => "arnaud.charleroy@inra.fr"
     *  "iat" => $date->getTimestamp(), // JWT creation date
     *  "exp" => date->modify('+20 minutes')->getTimestamp(); // JWT expiration date
     *   );
     * @var array
     */
    protected $payload;

    /**
     * Supported algorithms are 'HS256', 'HS384', 'HS512' and 'RS256'
     * @var string Hashing algorithm used
     */
    protected $algorithm;

    /**
     * @example {app_directory}/rsa_keys/Alfis-JWT-private-key.pem"
     * @var string  Path to the private key
     */
    protected $private_key_path;

    /**
     * Represents the JWT instance
     * @var string A signed JSON Web Token
     */
    protected $jwt;

    public function __construct($payload, $algorithm, $private_key_path)
    {
        $this->payload = $payload;
        $this->algorithm = $algorithm;
        $this->private_key_path = $private_key_path;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function getAlgorithm()
    {
        return $this->algorithm;
    }

    public function getPrivate_key_path()
    {
        return $this->private_key_path;
    }

    public function setPayload($payload)
    {
        $this->payload = $payload;
    }

    public function setAlgorithm($algorithm)
    {
        $this->algorithm = $algorithm;
    }

    public function setPrivate_key_path($private_key_path)
    {
        $this->private_key_path = $private_key_path;
    }

    /**
     * @param bool $debug true to print an error occured
     * @return string|null signed JSON Web Token or null if an error occured
     */
    public function build($debug = false)
    {
        try {
            $this->jwt = JWT::encode($this->payload, file_get_contents($this->private_key_path), $this->algorithm);
        } catch (Exception $ex) {
            if ($debug) {
                echo $ex->getMessage();
            }
        }
        return $this->jwt;
    }

    /**
     * Override __toString method
     * @return string signed JSON Web Token
     */
    public function __toString()
    {
        return $this->build();
    }
}
