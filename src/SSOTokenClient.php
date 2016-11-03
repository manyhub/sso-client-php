<?php

namespace Manyhub\SSO\Client\Component;

use GuzzleHttp\ClientInterface;
use Manyhub\SSO\Client\Component\Signer\Signer;
use Psr\Log\LoggerInterface;

/**
 * Cette classe permet de faire la transaction complète d'une authentification oAuth (signature / appel http
 * et génération de l'URL de redirection pour le client final)
 */
class SSOTokenClient
{
    /** @var LoggerInterface */
    private $logger;

    /** @var ClientInterface */
    private $client;

    /** @var Signer */
    private $signer;

    public function __construct(ClientInterface $client, Signer $signer)
    {
        $this->client = $client;
        $this->signer = $signer;
    }

    /**
     * Permet d'attacher un logger à cette classe pour écouter les évènements qui s'y produisent
     * (debug/info/warning/etc...)
     *
     * @param LoggerInterface $logger
     */
    public function attachLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Détache un logger si il a été attaché.
     */
    public function detachLogger()
    {
        unset($this->logger);
    }

    /**
     * Fait une tentative d'authentification sur le SSO.
     * Dans le cas où la tentative échoue, une exception surviendra.
     * Dans le cas où la tentative a été un succès, un tableau de retour avec l'URL de redirection sera disponible.
     *
     * @param string $method
     * @param string $url
     * @param string $key
     * @param string $secret
     * @param string $crypto
     * @param string $version
     * @param string $callback
     * @param string $login
     * @param string $application
     * @return array
     */
    public function tryAuth($method, $url, $key, $secret, $crypto, $version, $callback, $login, $application)
    {

        $oAuthParams = $this->generateOAuthParams($key, $crypto, $version, $callback);
        $signatureParams = $this->generateSignatureParams($oAuthParams, $method, $url);

        $signature = $this->generateSignature($crypto, $signatureParams, $secret);

        $oAuthHeader = $this->generateOAuthHeader($oAuthParams, $signature);
        $result = $this->callSSO($method, $url, $oAuthHeader);

        return $this->parseResult($result, $url, $login, $application);
    }

    /**
     * Génére le tableau de paramètres pour oAuth avec génération du nonce.
     *
     * @param string $key
     * @param string $crypto
     * @param string $version
     * @param string $callback
     * @return array
     */
    private function generateOAuthParams($key, $crypto, $version, $callback)
    {
        $params = [
            'oauth_nonce' => bin2hex(random_bytes(16)), // generate random string
            'oauth_timestamp' => time(), // heure actuelle en millisecondes
            'oauth_callback' => $callback,
            'oauth_signature_method' => $crypto,
            'oauth_consumer_key' => $key,
            'oauth_version' => $version,
        ];

        return $params;
    }

    /**
     * Génère le tableau de paramètre nécessaire pour la signature.
     *
     * @param array $oAuthParams
     * @param string $method
     * @param string $url
     * @return array
     */
    private function generateSignatureParams(array $oAuthParams, $method, $url)
    {
        $signatureParams = [
            'oauth' => $oAuthParams,
            'request' => [
                'method' => $method,
                'url' => $url,
            ]
        ];

        if (isset($this->logger)) {
            $this->logger->debug("OAuth Parameters generated", $signatureParams);
        }

        return $signatureParams;
    }

    /**
     * Génère le hash de la signature.
     *
     * @param string $crypto
     * @param array $signatureParams
     * @param string $secret
     * @return mixed
     */
    private function generateSignature($crypto, array $signatureParams, $secret)
    {
        $signature = $this->signer->sign($crypto, $signatureParams, $secret);
        if (isset($this->logger)) {
            $this->logger->debug("Signature generated > ".$signature);
        }

        return $signature;
    }

    /**
     * Génère la chaine d'entête d'oAuth
     *
     * @param array $params
     * @param string $signature
     * @return string
     */
    private function generateOAuthHeader(array $params, $signature)
    {
        $paramsString = array(sprintf('oauth_signature="%s"', $signature));

        foreach ($params as $key => $value) {
            $paramsString[] = $key . '="' . $value . '"';
        }

        return sprintf('OAuth %s', implode(',', $paramsString));
    }


    /**
     * Appel le SSO avec les paramètres d'oAuth et retourne le JSON de réponse.
     *
     * @param string $method
     * @param string $url
     * @param string $oAuthHeader
     * @return \stdClass
     */
    private function callSSO($method, $url, $oAuthHeader)
    {
        $response = $this->client->request($method, $url, [
            'headers' => [
                'User-Agent'   => 'manyhub-sso-client/1.0',
                'Accept'       => 'application/json',
                'Authorization' => $oAuthHeader
            ],
        ]);

        $content = $response->getBody()->getContents();
        if (isset($this->logger)) {
            $this->logger->info("Received from server : ".$content);
        }

        return json_decode($content);
    }

    /**
     * Parse le retour de la réponse du SSO et génère le tableau de sortie contenant l'adresse de redirection
     *
     * @param \stdClass $result
     * @param string $url
     * @param string $login
     * @param string $application
     * @return array
     */
    private function parseResult(\stdClass $result, $url, $login, $application)
    {
        $parsedUrl = parse_url($url);

        $output = [
            'redirect_url' => $parsedUrl['scheme'].'://'.$parsedUrl['host'].'/preauth/'.rawurlencode($result->oauth_token).'/'.$login.'/'.$application
        ];

        return $output;
    }

}
