<?php

namespace App\cmd;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnvList extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('consul:list')
            ->setDescription('Метод для получения списка сред из консула')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $envs = $this->getEnvs();

        return Command::SUCCESS;
    }
}