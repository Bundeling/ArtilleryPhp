<?php declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use ArtilleryPhp\Artillery;

$artillery = Artillery::new()
	->setProcessor('./helpers.js')
	->addPhase(['duration' => 2, 'arrivalRate' => 1])
	->setVariable('test', 0)
	->addScenario(
		Artillery::scenario()
		    ->addLog('{{ test }}')
            ->addFunction('testVar')
		    ->addLog('{{ test }}')
	)->run();
