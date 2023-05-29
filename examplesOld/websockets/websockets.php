<?php declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

use ArtilleryPhp\Artillery;
// config:
//  target: "ws://localhost:8888/"
//  processor: "./my-functions.js"
//  phases:
//    - duration: 60
//      arrivalRate: 25
//
//scenarios:
//  - name: "Sending a string"
//    engine: ws
//    flow:
//      - send: "Artillery"
//
//  - name: "Sending an object from a function"
//    engine: ws
//    flow:
//      - function: "createRandomScore"
//      - send: "{{ data }}"

$artillery = Artillery::new('ws://localhost:8888')
	->setProcessor('./my-functions.js')
	->addPhase(['duration' => 60, 'arrivalRate' => 25]);

$stringScenario = Artillery::scenario('Sending a string')
	->setEngine('ws')
	->addRequest(Artillery::wsRequest('send', 'Artillery'));

$objectScenario = Artillery::scenario('Sending an object from a function')
	->setEngine('ws')
	->addFunction('createRandomScore')
	->addRequest(Artillery::wsRequest('send', '{{ data }}'));

$artillery->addScenarios([$stringScenario, $objectScenario])->build();