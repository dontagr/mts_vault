<?php

namespace App\cmd;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Log\Logger;
use Violuke\Vault\ServiceFactory;
use Violuke\Vault\Services\Data;

class BaseCommand extends Command
{
    protected bool $debugMode;
    protected array $presecret;
    protected LoggerInterface $logger;

    protected function configure(): void
    {
        $this
            ->addOption('debugMode', 'd', InputOption::VALUE_NONE, 'debug mode')
            ->addOption('presecret', 'p', InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY, 'pre secret like "dev" or "ppd"', [$_ENV['VAULT_DEV_SPACE'], $_ENV['VAULT_PPD_SPACE']])
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->debugMode = $input->getOption('debugMode');
        $this->presecret = $input->getOption('presecret');
        $this->logger = new Logger($this->debugMode ? LogLevel::DEBUG : LogLevel::INFO, 'php://stdout');
    }

    protected function getEnvs(string $zone): array
    {
        $result = [];
        exec(sprintf(
            'consul kv get -keys "variables/%s/shopdev_front/" 2> %s/../../logs/errors.log',
            $zone,
            __DIR__
        ), $result);
        if (!$result) {
            throw new RuntimeException("Значения сред получить не удалось");
        }

        $envs = [];
        foreach ($result as $val) {
            $envs[] = preg_replace('/^.*\/([a-z0-9]+)\/$/i', '$1', $val);
        }
        $this->logger->info(sprintf('Всего %s окружения envs: [%s]', count($envs), implode(', ', $envs)));

        return $envs;
    }

    protected function getVaultData(): Data
    {
        $client = new client(['base_uri' => $_ENV['VAULT_URI'], 'headers' => ['X-Vault-Token' => $_ENV['VAULT_TOKEN']]]);

        return (new ServiceFactory([], $this->logger, $client))->get('data');
    }

    protected function getStringFromArgument(string $key, InputInterface $input, bool $required = true): string
    {
        $str = $input->getArgument($key);
        if ($required && !$str) {
            throw new RuntimeException(sprintf('Не передали обязательный аргумент %s', $key));
        }

        return $str;
    }

    protected function getValues(string $presecret, string $secret, string $env, Data $vaultData): array
    {
        $this->logger->info(sprintf('Получаю данные для env=%s', $env));
        $response = $vaultData->get($this->getVaultPathName($presecret, $secret, $env));

        return json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR)['data']['data'] ?? [];
    }

    protected function writeValues(string $presecret, string $secret, string $env, Data $vaultData, $values): bool
    {
        $response = $vaultData->write($this->getVaultPathName($presecret, $secret, $env), ['data' => $values]);

        return $response->getStatusCode() === 200;
    }

    protected function getVaultPathName(string $presecret, string $secret, string $env): string
    {
        return sprintf('%s/data/%s/%s', $presecret, $secret, $env);
    }
}