<?php declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

use ArtilleryPhp\Artillery;

$artillery = Artillery::new('https://artillery.io')
	->addPhase(['arrivalCount' => 300, 'duration' => 300])
	->addPayload('./csv/urls.csv', ['url']);

$requestFlow = Artillery::scenario()
	->addRequest(Artillery::request('get', '{{ url }}'))
	->addThink(1);

$artillery->addScenario(Artillery::scenario()->addLoop($requestFlow, 100))->build();