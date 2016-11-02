<?php

namespace Manyhub\SSO\Client\Component\Signer;

/**
 * Cette classe permet de générer le hash de signature pour une authentification via oAuth.
 */
class Signer
{

    /** @var array */
    private $signers;

    public function __construct()
    {
        $this->signers = [
            'HMAC-SHA1' => function ($data, $secret) {
                return hash_hmac('SHA1', $data, $secret);
            }
        ];
    }

    /**
     * Lance la procédure de hash en fonction des paramètres passés.
     *
     * @param string $crypto
     * @param array $params
     * @param string $secret
     * @return string
     */
    public function sign($crypto, array $params, $secret)
    {
        if (!isset($this->signers[$crypto])) {
            throw new \InvalidArgumentException("Aucune signature ne correponds");
        }

        $oAuthParams = $params['oauth'];
        $requestParams = $params['request'];


        ksort($oAuthParams);

        $array = [];
        foreach ($oAuthParams as $key =>$value) {
            $array[] = rawurlencode($key) . '=' . rawurlencode($value);
        }

        $data = strtoupper($requestParams['method'])
            . '&' . rawurlencode($requestParams['url'])
            . '&' . rawurlencode(implode('&', $array))
        ;

        return $this->signers[$crypto]($data, $secret);
    }

}