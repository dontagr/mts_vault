<?php

namespace App\cmd;

use JsonException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Violuke\Vault\Services\Data;

class AddKey extends BaseCommand
{
    private string $secret;
    private string $keyName;
    private string $value;
    private array $envs;
    private Data $vaultData;
    private bool $needCreate;

    protected function configure(): void
    {
        $this
            ->setName('vault:add-key')
            ->setDescription('Добавляет ключ в выбранный секрет по всем средам или выборочно при передачи -envs')
            ->addArgument('secret', InputArgument::REQUIRED, 'secret')
            ->addArgument('keyName', InputArgument::REQUIRED, 'key name')
            ->addArgument('value', InputArgument::REQUIRED, 'value')
            ->addOption('create', 'c', InputOption::VALUE_NONE, 'опционально для случаев когда надо создать новый секрет')
            ->addOption('envs', 'e', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'envs')
        ;

        parent::configure();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->secret = $this->getStringFromArgument('secret', $input);
        $this->keyName = $this->getStringFromArgument('keyName', $input);
        $this->value = $this->getStringFromArgument('value', $input);
        $this->envs = $input->getOption('envs');
        $this->needCreate = $input->getOption('create');
        $this->vaultData = $this->getVaultData();
    }

    /**
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initialize($input, $output);

        if (!$this->envs) {
            $this->envs = $this->getEnvs();
        }

        foreach ($this->envs as $env) {
            $values = $this->needCreate ? [] : $this->getValues($this->secret, $env, $this->vaultData);
            if (!$values) {
                $this->logger->info('Данных не найдено');
            }

            $this->addKey($values, $this->keyName, $this->value, $env);
            if ($this->debugMode) {
                $this->logger->info(sprintf('Сформированно значение %s', json_encode($values, JSON_THROW_ON_ERROR)));
                continue;
            }

            $status = $this->writeValues($this->secret, $env, $this->vaultData, $values);
            if ($status) {
                $this->logger->info('Запись завершина.');
            } else {
                $this->logger->error('Запись завершилась ошибкой.');
            }
        }

        return Command::SUCCESS;
    }

    protected function addKey(array &$data, string $keyName, string $value, string $env): bool
    {
        $data[$keyName] = preg_replace('/{alpha}/iu', $env, $value);

        return true;
    }
}