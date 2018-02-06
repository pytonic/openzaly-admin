<?php

require 'Zaly/Application.php';

$application =  \Zaly\Application::init();

$configs  = $application->getConfig();
$hostName = $configs['base']['host_name'];
$version  = $configs['base']['static_file_version'];

putenv("HOST_NAME=$hostName");
putenv("STATIC_VERSION=$version");

$application->run();
