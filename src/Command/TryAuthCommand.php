<?php

namespace Manyhub\SSO\Client\Component\Command;

use GuzzleHttp\Client;
use Manyhub\SSO\Client\Component\Signer\Signer;
use Manyhub\SSO\Client\Component\SSOTokenClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cette commande permet de tester le SSO en ligne de commande.
 */
class TryAuthCommand extends Command
{

    const URL_PROD = 'https://connect.manymore.fr/oauth/v1/requestToken';
    const URL_STAGING = 'https://connect-recette.manymore.fr/oauth/v1/requestToken';

    /** {@inheritdoc} */
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('manyhub:oauth:try')

            ->addArgument('key', InputArgument::REQUIRED, 'Your consumer key')
            ->addArgument('secret', InputArgument::REQUIRED, 'Your secret key')
            ->addArgument('login', InputArgument::REQUIRED, 'The login you want to auto-login')

            ->addOption('crypto', null, InputOption::VALUE_OPTIONAL, 'Crypto method', 'HMAC-SHA1')
            ->addOption('callback', null, InputOption::VALUE_OPTIONAL, 'Callback', 'https://www.manymore.fr')
            ->addOption('env', null, InputOption::VALUE_OPTIONAL, 'Set the environment [prod/staging]', 'prod')
            ->addOption('application', null, InputOption::VALUE_OPTIONAL, 'Application to land', 'prisme')
            ->addOption('crypto_version', null, InputOption::VALUE_OPTIONAL, 'Version', '1.0')

            // the short description shown while running "php bin/console list"
            ->setDescription('Try to authenticate')

        ;
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $logger = new ConsoleLogger($output);
        $transport = new Client();
        $signer = new Signer();

        $client = new SSOTokenClient($transport, $signer);
        $client->attachLogger($logger);

        $url = $this->getUrl($input->getOption('env'));

        $result = $client->tryAuth(
            'get',
            $url,
            $input->getArgument('key'),
            $input->getArgument('secret'),
            $input->getOption('crypto'),
            $input->getOption('crypto_version'),
            $input->getOption('callback'),
            $input->getArgument('login'),
            $input->getOption('application')
        );

        $output->writeln("Successfull pre-authentification !");
        $output->writeln("Redirect user to ".$result['redirect_url']);
    }

    /**
     * Retourne l'URL du SSO à appeler en fonction de l'environnement choisi
     *
     * @param string $env
     * @return string
     * @throws \Exception
     */
    private function getUrl($env)
    {
        switch ($env) {
            case 'prod':
                return self::URL_PROD;
            case 'staging':
                return self::URL_STAGING;
            default:
                throw new \Exception('Environment not found : '.$env);
        }

    }
}