<?php

use ArtilleryPhp\Artillery;

$artillery = Artillery::new('http://localhost:3000')
	->addEnvironment('load', ['phases' => [['duration' => 600, 'arrivalRate' => 25]]])
	->addEnvironment('functional', ['plugins' => ['expect' => []]]);

$postUserRequest = Artillery::request('post', '/users')
	->setJson('username', 'new-user')
	->addCapture('id', 'json', '$.id')
	->addExpect('statusCode', 201)
	->addExpect('contentType', 'json')
	->addExpect('hasProperty', 'username')
	->addExpect('equals', 'new-user');

$getUserRequest = Artillery::request('get', '/users/{{ id }}')
	->addExpect('statusCode', 200)
	->addExpect('contentType', 'json')
	->addExpect('hasProperty', 'username')
	->addExpect('equals', 'new-user');

$deleteUserRequest = Artillery::request('delete', '/users/{{ id }}')
	->addExpect('statusCode', 204);

$scenario = Artillery::scenario()
	->addRequest($postUserRequest)
	->addRequest($getUserRequest)
	->addRequest($deleteUserRequest);

$artillery->addScenario($scenario)->build();
