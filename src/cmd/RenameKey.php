<?php

namespace App\cmd;

use JsonException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Violuke\Vault\Services\Data;

class RenameKey extends BaseCommand
{
    private string $secret;
    private string $oldKeyName;
    private string $newKeyName;
    private array $envs;
    private Data $vaultData;

    protected function configure(): void
    {
        $this
            ->setName('vault:rename-key')
            ->setDescription('Меняет название ключа в выбранном секрете по всем средам или выборочно при передачи -envs')
            ->addArgument('secret', InputArgument::REQUIRED, 'secret')
            ->addArgument('oldKeyName', InputArgument::REQUIRED, 'old key name')
            ->addArgument('newKeyName', InputArgument::REQUIRED, 'new key name')
            ->addOption('envs', 'e', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'envs')
        ;

        parent::configure();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->secret = $this->getStringFromArgument('secret', $input);
        $this->oldKeyName = $this->getStringFromArgument('oldKeyName', $input);
        $this->newKeyName = $this->getStringFromArgument('newKeyName', $input);
        $this->envs = $input->getOption('envs');
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
            $values = $this->getValues($this->secret, $env, $this->vaultData);
            if (!$values) {
                $this->logger->info('Данных не найдено');
                continue;
            }

            if (!$this->renameKey($values, $this->oldKeyName, $this->newKeyName)) {
                $this->logger->info('Не одно из заданий не удалось применить.');
                continue;
            }

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

    protected function renameKey(array &$data, string $oldKeyName, string $newKeyName): bool
    {
        if (!isset($data[$oldKeyName])) {
            $this->logger->info(sprintf('переименование не возможно поскольку ключ = {%s} не найден', $oldKeyName));

            return false;
        }

        $data[$newKeyName] = $data[$oldKeyName];
        unset($data[$oldKeyName]);

        return true;
    }
}