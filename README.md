# Manyhub SSO-Connect 

Ce dépôt permet à n'importe quel partenaire de Manymore de pouvoir s'authentifier via le SSO.

Ce composant est totalement autonome et peut s'utiliser dans n'importe quel projet écrit en PHP.

L'installation de ce composant se fait via [composer](https://getcomposer.org) :
 `` php composer.phar require manyhub/sso-client-php ^0.1 ``
 
#### Dépendances

Ce composant nécessite PHP 5.6 au minimum ainsi que les bibliothèques suivantes :
 - guzzlehttp/guzzle (pour la communication HTTP)
 - monolog/monolog (pour la gestion des logs)
 - symfony/console (dans le cas où on veut tester en mode console)
 - paragonie/random_compat (pour la génération aléatoire du nonce oAuth)
  
#### Utilisation 

En mode console à des fins de tests :

`` php bin/console manyhub:oauth:try <key> <secret> <login>``

Ces 3 paramètres sont obligatoires :
 - key : le consumer_key qui vous a été attribué
 - secret : le secret_key qui vous a été attribué
 - login : le login de l'utilisateur à auto-logguer
 
 
D'autres paramètres sont optionnels mais néanmoins disponibles :
 * --env=[prod/staging] : par défaut "prod" mais pour tester sur l'environnement de recette, il faut utiliser "staging"
 * --callback : par défaut "https://manymore.fr" mais vous pouvez utiliser n'importe quelle URL ici
 * --application=[prisme/nexus/risk] : par défaut "prisme" mais en fonction du type d'utilisateur, l'application choisie ne sera pas la même
 * --crypto : par défaut "HMAC-SHA1" qui est le seul utilisé actuellement
 * --crypto_version : par défaut "1.0' qui est la seule version utilisée actuellement
 
-------------

Pour l'intégration dans vos scripts PHP, voici un exemple :
```php
# monscript.php

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use Manyhub\SSO\Client\Component\Signer\Signer;
use Manyhub\SSO\Client\Component\SSOTokenClient;

$client = new SSOTokenClient(new Client(), new Signer());
$result = $client->tryAuth(
       'get',
       $urlSSO,
       $key,
       $secret,
       'HMAC_SHA1',
       '1.0',
       $callback,
       $login,
       $application
);

// Tout se trouve dans $result['redirect_url'] :
// echo $result['redirect_url'];
header('Location: ' . $result['redirect_url']);
exit();

```

