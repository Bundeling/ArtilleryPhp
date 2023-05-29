<?php declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

use ArtilleryPhp\Artillery;

$artillery = Artillery::new('http://localhost:4000/')
	->addPhase(['duration' => 60, 'arrivalRate' => 25]);

$createUserMutation = <<<'GRAPHQL'
	mutation CreateUserMutation($createUserInput: UserInput) {
		createUser(input: $createUserInput) {
			id
		}
	}
	GRAPHQL;

$updateUserMutation = <<<'GRAPHQL'
	mutation UpdateUserMutation($userId: ID!, $updateUserInput: UserInput) {
		updateUser(id: $userId, input: $updateUserInput) {
			username
			email
		}
	}
	GRAPHQL;

$userQuery = <<<'GRAPHQL'
	query UserQuery($userId: ID!) {
		user(id: $userId) {
			username
			email
		}
	}
	GRAPHQL;

$deleteUserMutation = <<<'GRAPHQL'
	mutation DeleteUserMutation($userId: ID!) {
		deleteUser(id: $userId) {
			id
		}
	}
	GRAPHQL;

$scenario = Artillery::scenario('Create, update, and delete a user from the database')
	->addRequest(
		Artillery::request('post', '/')
			->setJson('query', $createUserMutation)
			->setJson('variables', ['createUserInput' => [
				'username' => '{{ $randomString() }}',
				'email' => 'user-{{ $randomString() }}@artillery.io'
			]])
			->addCapture('userId', 'json', '$.data.createUser.id')
	)
	->addRequest(
		Artillery::request('post', '/')
			->setJson('query', $userQuery)
			->setJson('variables', ['userId' => '{{ userId }}'])
	)
	->addRequest(
		Artillery::request('post', '/')
			->setJson('query', $updateUserMutation)
			->setJson('variables', ['userId' => '{{ userId }}', 'updateUserInput' => [
				'email' => 'user-{{ $randomString() }}@artillery.io'
			]])
	)
	->addRequest(
		Artillery::request('post', '/')
			->setJson('query', $deleteUserMutation)
			->setJson('variables', ['userId' => '{{ userId }}'])
	);

$artillery->addScenario($scenario)->build();