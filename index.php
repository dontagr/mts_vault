<?php

use App\cmd\AddKey;
use App\Cmd\EnvList;
use App\cmd\RemoveKey;
use App\Cmd\RenameKey;
use Symfony\Component\Console\Application;

require_once 'vendor/autoload.php';

(new App\helper\DotEnvEnvironment)->load(__DIR__);

$application = new Application();

$application->add(new EnvList());
$application->add(new RenameKey());
$application->add(new AddKey());
$application->add(new RemoveKey());

$application->run();

