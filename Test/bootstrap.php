<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

use Magento\Framework\Code\Generator\Io;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\TestFramework\Unit\Autoloader\FactoryGenerator;
use Magento\Framework\TestFramework\Unit\Autoloader\GeneratedClassesAutoloader;

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/magento/framework/Phrase/__.php';

$generatedClassesAutoloader = new GeneratedClassesAutoloader(
    [new FactoryGenerator()],
    new Io(new File(), __DIR__ . '/../build/generated')
);
spl_autoload_register([$generatedClassesAutoloader, 'load']);
