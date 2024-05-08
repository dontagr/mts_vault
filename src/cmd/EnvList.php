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

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initialize($input, $output);

        foreach ($this->presecret as $presecret) {
            $this->logger->info(sprintf('Выбрана зона разработки [%s]', $presecret));

            $this->getEnvs($presecret);
        }

        return Command::SUCCESS;
    }
}