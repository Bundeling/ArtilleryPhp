<?php declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

use ArtilleryPhp\Artillery;

$artillery = Artillery::new('http://localhost:31337')
	->setProcessor('./helpers.js')
	->addPhase(['duration' => 1, 'arrivalCount' => 1]);

$scenario = Artillery::scenario('Set custom header')
	->addRequest(
		Artillery::request('get', '/')
			->setQueryString('foo', 'bar')
			->setHeaders([
				'content-type' => 'application/json',
				'accept' => 'application/json'
			])
			->setJson()
			->addBeforeRequest('setCustomHeader'));

$artillery->addScenario($scenario)->build();
