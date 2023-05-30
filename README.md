# ArtilleryPhp
ArtilleryPhp is a PHP library that provides a fluent interface for [Artillery.io](https://www.artillery.io/) scripts.

Library documentation: [https://artilleryphp.netlify.app](https://artilleryphp.netlify.app/packages/application)

Examples (look for the .php files): [ArtilleryPhp-Examples](https://github.com/Bundeling/ArtilleryPhp-examples)

Documentation contains:
- Full explanation for each class and method.
- Example code for each class and most methods.
- Links to every section of the [Artillery reference docs](https://www.artillery.io/docs).

## Table of Contents
-   [Installation](#installation)
-   [Usage](#usage)
-   [Artillery Class](#artillery-class)
-   [Scenario Class](#scenario-class)
-   [Request Class](#request-class)

## Installation
You can install the library via Composer:
```text
composer require bundeling/artilleryphp
```

This library requires the `symfony/yaml` package to render its internal arrays to a YAML format.

## Usage
Here is an example of how to use the ArtilleryPhp library:
### Step 1: Create a new Artillery instance
You can use `Artillery::new($target)` to get a new instance, and use the fluent interface to set config values:
```php
use ArtilleryPhp\Artillery;

$artillery = Artillery::new('http://localhost:3000')
    ->addPhase(['duration' => 60, 'arrivalRate' => 5, 'rampTo' => 20], 'Warm up')
    ->addPhase(['duration' => 60, 'arrivalRate' => 20], 'Sustain')
    ->setPlugin('expect');
```
You can also create one from a full or partial array representation:
```php
use ArtilleryPhp\Artillery;

$artillery = Artillery::fromArray([
    'config' => [
        'target' => 'http://localhost:3000',
        'phases' => [
            ['duration' => 60, 'arrivalRate' => 5, 'rampTo' => 20, 'name' => 'Warm up'],
            ['duration' => 60, 'arrivalRate' => 20, 'name' => 'Sustain'],
        ],
        'plugins' => [
            'expect' => [],
        ]
    ]
]);
```
### Step 2: Define the flow of your scenario and add it to the Artillery instance:
```php
$scenario = Artillery::scenario()
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
            
$loopedScenario = Artillery::scenario()->addLoop($scenario, 10);

$artillery->addScenario($loopedScenario);
```
### Step 3: Export the YAML:
```php
$filePath = __DIR__ . '/artillery.yaml';
$artillery->build($filePath);

// Maybe even run the script right away:
// $artillery->run();
```
This will produce the following `artillery.yaml` file:
```yaml
config:
  target: 'http://localhost:3000'
  phases:
    - duration: 60
      arrivalRate: 5
      rampTo: 20
      name: 'Warm up'
    - duration: 60
      arrivalRate: 20
      name: Sustain
  plugins:
    expect: {  }
scenarios:
  - flow:
      - loop:
          - get:
              url: /login
              payload:
                url: /login
              capture:
                json: $.token
                as: token
              expect:
                - statusCode: 200
                - contentType: json
                - hasProperty: token
          - get:
              url: /inbox
              qs:
                token: '{{ token }}'
              expect:
                - statusCode: 200
        count: 10
```
For a very simple script you can also add Requests (single or array) directly to the Artillery instance:
```php
$artillery = Artillery::new()
    ->addScenario(Artillery::request('get', 'http://www.google.com'));
```
This creates a new scenario out of the request(s).
```php
$artillery = Artillery::request('custom')
    ->setRequest(Artillery::EMPTY_OBJECT);
```

## Artillery Class
The `Artillery` class has all the methods related to the config section of the Artillery script, along with adding scenarios.

Docs: https://artilleryphp.netlify.app/classes/artilleryphp-artillery

For custom config settings, there is a `set(key: string, value: mixed)` function available.

### Target:
If a target is set it will be used as the base Url for all of the requests in the script.

You can either pass the base Url in the constructor or use the `setTarget` method on the Artillery instance. You can also skip this step entirely and provide fully qualified Urls in each Request.
```php
$artillery = Artillery::fromArray(['config' => ['target' => 'http://localhost:3000']]);
$artillery = Artillery::new('http://localhost:3000');
$artillery = Artillery::new()->setTarget('http://localhost:3000');
```
### Static factory helpers, use these to get a new instance and immediately call methods on it:
- Artillery: `new([targetUrl: null|string = null]): Artillery`
- Request: `request(method: string, url: string): Request`
- WsRequest: `wsRequest(method: string, request: mixed): WsRequest`
- Scenario: `scenario(): Scenario`

```php
$artillery = Artillery::new($targetUrl)
    ->addPhase(['duration' => 60, 'arrivalRate' => 10]);
$request = Artillery::request('get', '/login')
    ->addCapture('token', 'json', '$.token');
$scenario = Artillery::scenario()->addRequest($request)->addThink(0.5);
$loop = Artillery::scenario()->addLoop($scenario, 10);
$artillery->addScenario($loop);
```

### Scenarios:
- `addScenario(scenario: Scenario)`
  Set a Scenario to the scenarios section of the Artillery script.
- `setAfter(scenario: Scenario)`
  Set a Scenario to run after a Scenario from the scenarios section is complete.
- `setBefore(scenario: Scenario)`
  Adds a Scenario to run before any given Scenario from the scenarios section.
```php
$login = Artillery::scenario()->addRequest(
    Artillery::request('get', '/login')
        ->addCapture('token', 'json', '$.token'));
$inbox = Artillery::scenario()->addRequest(
    Artillery::request('get', '/inbox')
        ->setHeader('auth', '{{ token }}'));
$artillery->setBefore($login)->addScenario($inbox);
```

### Config settings:
Please refer to the docs: https://artilleryphp.netlify.app/classes/artilleryphp-artillery#methods
- `addEnsureCondition(expression: string, [strict: bool = true])`
- `addEnsureThreshold(metricName: string, value: int)`
- `setEngine(name: string, [options: array = [...]])`
- `setEnvironment(name: string, config: array):`
- `addPayload(path: string, fields: array, [options: array = [...]])`
- `addPhase(phase: array, [name: null|string = null])`
- `setPlugin(name: string, [options: array = [...]])`
- `setVariable(name: string, value: mixed)`
- `set(key: string, value: mixed)`
- `setHttp(options: array)`
- `setProcessor(path: string)`
- `setTarget(url: string)`
- `setTls(rejectUnauthorized: bool): Artillery setWs(wsOptions: array)`

### Render/load:
- `toYaml(): string`
  Render the script to a Yaml string.
- `fromArray(script: array): Artillery `
  Construct a new Artillery instance from given array data.
- `toArray(): array`
  Get the array representation of the current Artillery instance.

## Scenario Class
The `Scenario` class has all the methods related to a scenario as well as all methods related to its flow.

Docs: https://artilleryphp.netlify.app/classes/artilleryphp-scenario
```php
// Imagine we have an already defined Scenario as $defaultScenario
$scenario = Artillery::scenario()  
    ->setName('Request, pause 2 seconds, then default flow.')
    ->addRequest(Artillery::request('GET', 'https://example.com'))  
    ->addThink(2)  
    ->addFlow($defaultScenario);
```

### Methods:
Docs: https://artilleryphp.netlify.app/classes/artilleryphp-scenario#methods

Adding to the flow from another scenario into this scenario:
- `addFlow(scenario: Scenario)`

Misc:
- `setName(name: string)`
  Used for metric reports.
- `setWeight(weight: int)`
  Determines the probability that this scenario will be picked compared to other scenarios in the Artillery script. Defaults to 1.

Engine if not set are for HTTP requests, to make a WebSocket scenario you need to specify this scenario's engine to be 'ws' and only use instances of the `WsRequest` class available at `Artillery::wsRequest()`.
- `setEngine(engine: string) `

Scenario-level JavaScript function hook, from the Js file defined in `setProcessor` in the `Artillery` instance:
- `addAfterScenario(function: array|string|string[])`
- `addBeforeScenario(function: array|string|string[])`

Flow methods:
- `addRequest(request: RequestInterface)`
- `addLoop(request: array|Scenario|RequestInterface, [count: int|null = null], [over: null|string = null], [whileTrue: null|string = null])`
- `addLog(message: string, [ifTrue: null|string = null])`
- `addThink(duration: float|int, [ifTrue: null|string = null])`
- `addFunction(function: array|string|string[], [ifTrue: null|string = null])`

## Request Class
The `Request` class has all the methods related to HTTP requests along with some shared methods inherited from a `RequestBase` class.

For WebSocket there is a crude implementation of the `WsRequest` class available at `Artillery::wsRequest()`. For custom requests there is a bare-bone `AnyRequest` class available at `Artillery::anyRequest()` only inheriting from RequestBase. Both `Request` and `WsRequest` can be used anonymously with `set()`, `setMethod()` and `setRequest()` but it can be confusing to have methods available that do not exist.

Docs: https://artilleryphp.netlify.app/classes/artilleryphp-request

For custom config settings, there is a `set(key: string, value: mixed)` function available.

Method and URL can be set in the constructor:
```php
$getTarget = Artillery::request('get', '/inbox')  
    ->setJson('client_id', '{{ id }}')  
    ->addCapture('first_inbox_id', 'json', '$[0].id');  
$postResponse = Artillery::request('post', '/inbox')  
    ->setJsons(['user_id' => '{{ first_inbox_id }}', 'message' => 'Hello, world!']);
```

### Methods:
Please refer to the docs: https://artilleryphp.netlify.app/classes/artilleryphp-request#methods
- `setAuth(user: string, pass: string)`
- `setBody(body: mixed)`
- `setCookie(name: string, value: string)`
- `setCookies(cookies: string[])`
- `setFollowRedirect([followRedirect: bool = true])`
- `setForm(form: array)`
- `setFormData(formData: array)`
- `setGzip([gzip: bool = true])`
- `setHeaders(headers: array)`
- `setHeader(key: string, value: string)`
- `setIfTrue(expression: string)`
- `setJson(key: string, value: mixed)`
- `setJsons(jsons: array)`
- `setQueryStrings(qs: array)`
- `setQueryString(key: string, value: mixed)`
- `setUrl(url: string)`

Inherited:
- `set(key: string, data: mixed)`
- `addCapture(as: string, type: string, expression: string, [strict: bool = true], [attr: null|string = null], [index: int|null|string = null])`
- `addExpect(type: string, value: mixed)`
- `setMethod(method: string)`
- `setRequest(request: mixed)`
