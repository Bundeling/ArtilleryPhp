<?php declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

use ArtilleryPhp\Artillery;

$artillery = Artillery::new('http://www.artillery.io')
	->setProcessor('./helpers.js')
	->addPhase(['arrivalCount' => 10, 'duration' => 1])
	->setBefore(Artillery::scenario()->addFunction('generateSharedToken'));

$scenario = Artillery::scenario()
	->addFunction('generateVUToken')
	->addLog('VU id: {{ $uuid }}')
	->addLog('    shared token is: {{ sharedToken }}')
	->addLog('    VU-specific token is: {{ vuToken }}')
	->addRequest(
		Artillery::request('get', '/')
			->setHeaders([
				'x-auth-one' => '{{ sharedToken }}',
				'x-auth-two' => '{{ vuToken }}'
			]));

$artillery->addScenario($scenario)->build();