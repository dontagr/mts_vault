<?php

namespace App\cmd;

use JsonException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Violuke\Vault\Services\Data;

class RemoveKey extends BaseCommand
{
    private string $secret;
    private string $keyName;
    private array $envs;
    private Data $vaultData;
    private bool $needGetEnvs;

    protected function configure(): void
    {
        $this
            ->setName('vault:remove-key')
            ->setDescription('Удаляет ключ в выбранном секрете по всем средам или выборочно при передачи -envs -presecret')
            ->addArgument('secret', InputArgument::REQUIRED, 'secret')
            ->addArgument('keyName', InputArgument::REQUIRED, 'key name')
            ->addOption('envs', 'e', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'envs')
        ;

        parent::configure();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->secret = $this->getStringFromArgument('secret', $input);
        $this->keyName = $this->getStringFromArgument('keyName', $input);
        $this->envs = $input->getOption('envs');
        $this->vaultData = $this->getVaultData();
        $this->needGetEnvs = !$this->envs;
    }

    /**
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initialize($input, $output);

        foreach ($this->presecret as $presecret) {
            $this->logger->info(sprintf('Выбрана зона разработки [%s]', $presecret));

            if ($this->needGetEnvs) {
                $this->envs = $this->getEnvs($presecret);
            }

            foreach ($this->envs as $env) {
                $values = $this->getValues($presecret, $this->secret, $env, $this->vaultData);
                if (!$values) {
                    $this->logger->info('Данных не найдено');
                }

                $this->removeKey($values, $this->keyName);
                if ($this->debugMode) {
                    $this->logger->info(sprintf('Сформированно значение %s', json_encode($values, JSON_THROW_ON_ERROR)));
                    continue;
                }

                $status = $this->writeValues($presecret, $this->secret, $env, $this->vaultData, $values);
                if ($status) {
                    $this->logger->info('Запись завершина.');
                } else {
                    $this->logger->error('Запись завершилась ошибкой.');
                }
            }
        }

        return Command::SUCCESS;
    }

    protected function removeKey(array &$data, string $keyName): bool
    {
        if (isset($data[$keyName])) {
            unset($data[$keyName]);
        }

        return true;
    }
}