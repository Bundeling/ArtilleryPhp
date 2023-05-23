<?php declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

use ArtilleryPhp\Artillery;

$artillery = Artillery::new('http://localhost:3000')
	->addPhase(['duration' => 60, 'arrivalRate' => 5, 'rampTo' => 20], 'Warm up')
	->addPhase(['duration' => 60, 'arrivalRate' => 20], 'Sustain')
	->setPlugin('expect');

$flow = Artillery::scenario()
	->addRequest(
		Artillery::request('get', '/login')
			->addCapture('token', 'json', '$.token')
			->addExpect('statusCode', 200)
			->addExpect('contentType', 'json')
			->addExpect('hasProperty', 'token'))
	->addRequest(
		Artillery::request('get', '/inbox')
			->setQueryString('token', '{{ token }}')
			->addExpect('statusCode', 200));

$scenario = Artillery::scenario()->addLoop($flow, 10);

$artillery->addScenario($scenario);

file_put_contents(__DIR__ . '/artillery.yaml', $artillery->toYaml());
// shell_exec('artillery run --output artillery-report.json artillery.yaml');