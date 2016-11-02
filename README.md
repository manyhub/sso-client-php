# Manyhub SSO-Connect 

Ce dépôt permet à n'importe quel partenaire de Manymore de pouvoir s'authentifier via le SSO.

Ce composant est totalement autonome et peut s'utiliser dans n'importe quel projet écrit en PHP.


#### Dépendance

Ce composant nécessite PHP 5.6 au minimum ainsi que les librairies suivantes :
 - guzzlehttp/guzzle (pour la communication HTTP)
 - monolog/monolog (pour la gestion des logs)
 - symfony/console (dans le cas où on veut tester en mode console)
 - paragonie/random_compat (pour la génération aléatoire du nonce oAuth)
 
 
Ces librairies s'installent via Composer (https://getcomposer.com) :
 `` php composer.phar install ``
 
#### Utilisation 

En mode console à des fins de tests :

`` php bin/console manyhub:oauth:try <key> <secret> <login>``

Ces 3 paramètres sont obligatoires :
 - key : le consumer_key qui vous a été attribué
 - secret : le secret_key qui vous a été attribué
 - login : le login de l'utilisateur à auto-logguer
 
 
D'autres paramètres sont optionnelles mais néanmoins disponibles :
 * --env=[prod/staging] : par défaut "prod" mais pour tester sur l'environnement de recette, il faut utiliser "staging"
 * --callback : par défaut "https://manymore.fr" mais vous pouvez utiliser n'importe quelle URL ici
 * --application=[prisme/nexus/risk] : par défaut "prisme" mais en fonction du type d'utilisateur, l'application choisie ne sera pas la même
 * --crypto : par défaut "HMAC-SHA1" qui est le seul utilisé actuellement
 * --crypto_version : par défaut "1.0' qui est la seule version utilisée actuellement
 
-------------

Pour l'intégration dans vos scripts PHP, voici un exemple :
```php
# monscript.php

include <vendor_dir>/autoload.php

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
header('Location: '.$result['redirect_url']);
exit();

```

