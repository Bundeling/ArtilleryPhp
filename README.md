# ArtilleryPhp

[Artillery.io](https://www.artillery.io/) is a modern, powerful & easy-to-use performance testing toolkit. 

[ArtilleryPhp](https://github.com/Bundeling/ArtilleryPhp) is a library to write and maintain Artillery scripts in PHP.

- Documentation: [bundeling.github.io/ArtilleryPhp](https://bundeling.github.io/ArtilleryPhp/namespaces/artilleryphp.html)
- Examples: [ArtilleryPhp-examples](https://github.com/Bundeling/ArtilleryPhp-examples)

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

This example is available at [examples/artilleryphp-usage](https://github.com/Bundeling/ArtilleryPhp-examples/tree/main/artilleryphp-usage).

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
            // To produce an empty object as "{  }", use stdClass.
            // This is automatic when using setPlugin(s), setEngine(s) and setJson(s).
            'expect' => new stdClass(),
        ]
    ]
]);
```

And from an existing YAML file, or other `Artillery` instance:

```php
$file = __DIR__ . '/common-config.yml';
$default = Artillery::fromYaml($file);

$artillery = Artillery::from($default);
```

### Step 2: Define the flow of your scenario and add it to the Artillery instance:

```php
// Create some requests:
$loginRequest = Artillery::request('get', '/login')
    ->addCapture('token', 'json', '$.token')
    ->addExpect('statusCode', 200)
    ->addExpect('contentType', 'json')
    ->addExpect('hasProperty', 'token');
    
$inboxRequest = Artillery::request('get', '/inbox')
    ->setQueryString('token', '{{ token }}')
    ->addExpect('statusCode', 200);

// Create a flow with the requests, and a 500ms delay between:
$flow = Artillery::scenario()
    ->addRequest($loginRequest)
    ->addThink(0.5)
    ->addRequest($inboxRequest);

// Let's loop the flow 10 times:
$scenario = Artillery::scenario()->addLoop($flow, 10);

// Add the scenario to the Artillery instance:
$artillery->addScenario($scenario);
```

#### Tips:

Where it's applicable methods have plural versions to add/set multiple entries, such as:

Plural versions exist to take multiple entries of raw array representations:

```php
$loginRequest = Artillery::request('post', '/login')
    ->setQueryStrings([
        'username' => '{{ username }},
        'password' => '{{ password }}'])
    ->addCaptures([
        ['json' => '$.token', 'as' => 'token'],
        ['json' => '$.id', 'as' => 'id']])

```

Take note of the difference between the set and add differention, and;

Refer to the [Artillery reference docs](https://www.artillery.io/docs) for raw representation specs.


### Step 3: Export the YAML:

```php
// Without argument will build the YAML as the same name as the php file:
$artillery->build();

// Maybe even run the script right away:
$artillery->run();
```

This will produce the following `readme-example.yml` file:

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

#### Tips:

For a very basic script, you can also add Requests (single or array) directly to the Artillery instance to create a new Scenario out of it:

```php
$artillery = Artillery::new()
    ->addScenario(Artillery::request('get', 'http://www.google.com'));
```
### Notes

Current implementation builds up an internal array representation. Meaning very little to no support for operations like getting a `Scenario` instance from a specific index or unsetting a property. For now think in terms of composition, and look forward to v2.

## Artillery Class

The `Artillery` class has all the methods related to the config section of the Artillery script, along with adding scenarios.

Docs: https://bundeling.github.io/ArtilleryPhp/classes/ArtilleryPhp-Artillery

For custom settings, there is a `set(key: string, value: mixed)` function available.

### Target:

If a target is set, it will be used as the base Url for all the requests in the script.

You can either pass the base Url in the constructor or use the `setTarget` method on the Artillery instance. You can also skip this step entirely and provide fully qualified Urls in each Request.

```php
// Base URL in the Scenario with relateve path in the request:
$artillery = Artillery::new('http://localhost:3000')->addScenario(Artillery::request('get', '/home'));;

// Without target, and fully qualified URL in Request:
$artillery = Artillery::new()->addScenario(Artillery::request('get', 'http://localhost:3000/home'));
```

### Environments:

Environments can be specified with overrides for the config, such as the target URL and phases.

You can either use the config of another Artillery instance, or as an array of config values:

```php
$local = Artillery::new('http://localhost:8080')
    ->addPhase(['duration' => 30, 'arrivalRate' => 1, 'rampTo' => 10])
    ->setHttpTimeout(60);

$production = Artillery::new('https://example.com')
    ->addPhase(['duration' => 300, 'arrivalRate' => 10, 'rampTo' => 100])
    ->setHttpTimeout(30);

$artillery = Artillery::new()
    ->setEnvironment('staging', ['target' => 'https://staging.example.com'])
    ->setEnvironment('production', $production)
    ->setEnvironment('local', $local);
````

### Static factory helpers, use these to get a new instance and immediately call methods on it:

- Artillery: `new([targetUrl: null|string = null]): Artillery`
- Scenario: `scenario([name: null|string = null]): Scenario`
- Request: `request([method: null|string = null], [url: null|string = null]): Request`
- WsRequest: `wsRequest([method: null|string = null], [request: mixed = null]): WsRequest`
- AnyRequest: `anyRequest([method: null|string = null], [request: mixed = null]): AnyRequest`

```php
$artillery = Artillery::new($targetUrl)
    ->addPhase(['duration' => 60, 'arrivalRate' => 10]);
    
$request = Artillery::request('get', '/login')
    ->addCapture('token', 'json', '$.token');
    
$scenario = Artillery::scenario('Logging in')->addRequest($request);
```

### Scenario-related methods:

You can add a fully built scenario, or pass a single Request or array of Requests, and a Scenario will be made from it.

See the [Scenario Class](#scenario-class) for more details.

- `addScenario(scenario: array|RequestInterface|RequestInterface[]|Scenario, [options: mixed[]|null = null])`
  - Add a Scenario to the scenarios section of the Artillery script.
- `setAfter(after: array|RequestInterface|RequestInterface[]|Scenario)`
  - Set a Scenario to run after a Scenario from the scenarios section is complete.
- `setBefore(before: array|RequestInterface|RequestInterface[]|Scenario)`
  - Adds a Scenario to run before any given Scenario from the scenarios section.

#### Processor & function hooks:

A scenario's flow, and requests, can have JavaScript function hooks that can read and modify context such as variables:

Here's a very demonstrative example from [examples/generating-vu-tokens](https://github.com/Bundeling/ArtilleryPhp-examples/tree/main/generating-vu-tokens):

```php
// This scenario will run once before any main scenarios/virtual users; here we're using a js function 
// from a processor to generate a variable available in all future scenarios and their virtual users:
$before = Artillery::scenario()->addFunction('generateSharedToken');

// One of the main scenarios, which has access to the shared token,
// and here we're generating a token unique to every main scenario that executed.
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

$artillery = Artillery::new('http://www.artillery.io')
    ->setProcessor('./helpers.js')
    ->setBefore($before)
    ->addScenario($scenario);
```

With `./helpers.js` as:

```js
module.exports = {
  generateSharedToken,
  generateVUToken
};

function generateSharedToken(context, events, done) {
  context.vars.sharedToken = `shared-token-${Date.now()}`;
  return done();
}

function generateVUToken(context, events, done) {
  context.vars.vuToken = `vu-token-${Date.now()}`;
  return done();
}
```

#### Tips:

- For custom settings, there is a `set(key: string, value: mixed)` function available.

### Config settings:

Please refer to the docs: https://bundeling.github.io/ArtilleryPhp/classes/ArtilleryPhp-Artillery#methods

- `addEnsureCondition(expression: string, [strict: bool|null = null])`
- `addEnsureConditions(thresholds: array[])`
- `addEnsureThreshold(metricName: string, value: int)`
- `addEnsureThresholds(thresholds: int[][])`
- `setEngine(name: string, [options: array|null = null])`
- `setEngines(engines: array[]|string[])`
- `setEnvironment(name: string, config: array|Artillery)`
- `setEnvironments(environments: array[]|Artillery[])`
- `addPayload(path: string, fields: array, [options: bool[]|string[] = [...]])`
- `addPayloads(payloads: bool[][]|string[][])`
- `addPhase(phase: array, [name: null|string = null])`
- `addPhases(phases: array[])`
- `setPlugin(name: string, [options: array|null = null])`
- `setPlugins(plugins: array)`
- `setVariable(name: string, value: mixed)`
- `setVariables(variables: mixed[])`
- `setHttp(key: string, value: bool|int|mixed)`
- `setHttps(options: bool[]|int[])`
- `setHttpTimeout(timeout: int)`
- `setHttpMaxSockets(maxSockets: int)`
- `setHttpExtendedMetrics([extendedMetrics: bool = true])`
- `setProcessor(path: string)`
- `setTarget(url: string)`
- `setTls(rejectUnauthorized: bool)`
- `setWs(wsOptions: array)`

### Render/load:

- `build([file: null|string = null]): Artillery` Build the script and save it to a file.
- `toYaml(): string` Render the script to a Yaml string.
- `from(artillery: Artillery): Artillery` New Artillery instance from given Artillery instance.
- `fromArray(script: array): Artillery` New Artillery instance from given array data.
- `toArray(): array` Get the array representation of the current Artillery instance.
- `run([reportFile: null|string = null], [debug: null|string = null]): Artillery` Run the built script (or build and run-), and save the report to a file.

## Scenario Class

The `Scenario` class has all the methods related to a scenario as well as all methods related to its flow.

Docs: https://bundeling.github.io/ArtilleryPhp/classes/ArtilleryPhp-Scenario

```php
// Imagine we have an already defined Scenario as $defaultScenario
$scenario = Artillery::scenario()  
    ->setName('Request, pause 2 seconds, then default flow.')
    ->addRequest(Artillery::request('GET', 'https://example.com'))  
    ->addThink(2)  
    ->addFlow($defaultScenario);
```

### Methods:
Docs: https://bundeling.github.io/ArtilleryPhp/classes/ArtilleryPhp-Scenario#methods

Custom Scenario settings:

- `set(key: string, value: mixed)`

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

Similarly, for requests, there are scenario level hooks for before and after:
- `addAfterResponse(function: array|string|string[])`
- `addBeforeRequest(function: array|string|string[])`

Flow methods:
- `addRequest(request: RequestInterface)`
- `addRequests(requests: RequestInterface[])`
- `addLoop(loop: array|RequestInterface|RequestInterface[]|Scenario|Scenario[], [count: int|null = null], [over: null|string = null], [whileTrue: null|string = null])`
- `addLog(message: string, [ifTrue: null|string = null])`
- `addThink(duration: float, [ifTrue: null|string = null])`
- `addFunction(function: array|string|string[], [ifTrue: null|string = null])`

## Request Class

The `Request` class has all the methods related to HTTP requests along with some shared methods inherited from a `RequestBase` class.

For WebSocket there is a crude implementation of the `WsRequest` class available at `Artillery::wsRequest()`. 

For custom requests `AnyRequest` is meant to be used anonymously with these functions:
- `set(key: string, value: mixed)`
- `setMethod(method: string)`
- `setRequest(request: mixed)`

Docs: https://bundeling.github.io/ArtilleryPhp/classes/ArtilleryPhp-Request

Method and URL can be set in the constructor:
```php
$getTarget = Artillery::request('get', '/inbox')  
    ->setJson('client_id', '{{ id }}')  
    ->addCapture('first_inbox_id', 'json', '$[0].id');  
$postResponse = Artillery::request('post', '/inbox')  
    ->setJsons(['user_id' => '{{ first_inbox_id }}', 'message' => 'Hello, world!']);
```

### Methods:

Please refer to the docs: https://bundeling.github.io/ArtilleryPhp/classes/ArtilleryPhp-Request#methods

- `addAfterResponse(function: array|string|string[])`
- `addBeforeRequest(function: array|string|string[])`
- `setAuth(user: string, pass: string)`
- `setBody(body: mixed)`
- `setCookie(name: string, value: string)`
- `setCookies(cookies: string[])`
- `setFollowRedirect([followRedirect: bool = true])`
- `setForm(key: string, value: mixed)`
- `setForms(form: array)`
- `setFormDatas(formData: array)`
- `setFormData(key: string, value: mixed)`
- `setGzip([gzip: bool = true])`
- `setHeader(key: string, value: string)`
- `setHeaders(headers: string[])`
- `setIfTrue(expression: string)`
- `setJson([key: null|string = null], [value: mixed = null])`
- `setJsons(jsons: mixed[])`
- `setMethod(method: string)`
- `setQueryString(key: string, value: mixed)`
- `setQueryStrings(qs: array)`
- `setUrl(url: string)`

Inherited:
- `set(key: string, data: mixed)`
- `setMethod(method: string)`
- `setRequest(request: mixed)`
- `addCapture(as: string, type: string, expression: string, [strict: bool = true], [attr: null|string = null], [index: int|null|string = null])`
- `addCaptures(captures: int[][]|string[][])`
- `addExpect(type: string, value: mixed, [equals: mixed = null])`
- `addExpects(expects: mixed[][])`
- `addMatch(type: string, expression: string, value: mixed, [strict: bool = true], [attr: null|string = null], [index: int|null|string = null])`
- `addMatches(matches: mixed[][])`
