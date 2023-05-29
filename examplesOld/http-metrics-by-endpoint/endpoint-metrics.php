<?php declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

use ArtilleryPhp\Artillery;

$artillery = Artillery::new('https://artillery.io')
	->addPhase(['duration' => 20, 'arrivalRate' => 1])
	->setPlugin('metrics-by-endpoint');

$scenario = Artillery::scenario()
	->addRequest(Artillery::request('get', '/'))
	->addRequest(Artillery::request('get', '/docs'))
	->addRequest(Artillery::request('get', '/404'));

$artillery->addScenario($scenario)->build();