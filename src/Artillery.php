<?php

namespace ArtilleryPhp;

use Symfony\Component\Yaml\Yaml;

/**
 * Main class for Artillery scripts, containing all methods for config and adding scenarios.
 * @example <pre><code class="language-php">use ArtilleryPhp\Artillery;
 *
 * // Create a new Artillery instance
 * $artillery = Artillery::new('http://localhost:3000')
 *     ->addPhase(['duration' => 60, 'arrivalRate' => 5, 'rampTo' => 20], 'Warm up')
 *     ->addPhase(['duration' => 60, 'arrivalRate' => 20], 'Sustain')
 *     ->addPlugin('expect');
 *
 * // Define the flow of your scenario and add it to the Artillery instance
 * $flow = Artillery::scenario()
 *     ->addRequest(
 *         Artillery::request('get', '/login')
 *             ->addCapture('token', 'json', '$.token')
 *             ->addExpect('statusCode', 200)
 *             ->addExpect('contentType', 'json')
 *             ->addExpect('hasProperty', 'token'))
 *     ->addRequest(
 *         Artillery::request('get', '/inbox')
 *             ->setQueryString('token', '{{ token }}')
 *             ->addExpect('statusCode', 200));
 *
 * $scenario = Artillery::scenario()->addLoop($flow, 10);
 *
 * $artillery->addScenario($scenario);
 *
 * // Export the YAML
 * file_put_contents(__DIR__ . '/artillery.yaml', $artillery->toYaml());
 * </code></pre>
 * @link https://www.artillery.io/docs/guides/guides/test-script-reference
 */
class Artillery {
	/** @internal */
	protected array $after = [];
	/** @internal */
	protected array $before = [];
	/** @internal */
	protected array $config = [];
	/** @internal */
	protected array $scenarios = [];

	/**
	 * Artillery constructor, optionally with a target URL.
	 * @param string|null $targetUrl Target base URL for the Artillery script.
	 */
	public function __construct(string $targetUrl = null) {
		if ($targetUrl) $this->setTarget($targetUrl);
	}

	/**
	 * Creates a new Artillery instance, optionally with a target URL.
	 * @param string|null $targetUrl Target base URL for the Artillery script.
	 * @return self A new Artillery instance.
	 */
	public static function new(string $targetUrl = null): self {
		return new self($targetUrl);
	}

	/**
	 * Creates a new Request for HTTP Scenarios with the given HTTP method and URL.
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#get--post--put--patch--delete-requests
	 * @param 'get'|'post'|'put'|'patch'|'delete'|null $method HTTP method for the request.
	 * @param string|null $url URL for the request, will be appended to the target URL set in config: target but can also be a fully qualified URL.
	 * @return Request A new Request instance.
	 */
	public static function request(string $method = null, string $url = null): Request {
		return new Request($method, $url);
	}

	/**
	 * Creates a new WsRequest for WebSocket (engine: ws) Scenarios with the given method and request data.
	 * @link https://www.artillery.io/docs/guides/guides/ws-reference
	 * @param string|null $method Method for the WebSocket request.
	 * @param mixed $request Data for the WebSocket request.
	 * @return WsRequest A new WsRequest instance.
	 */
	public static function wsRequest(string $method = null, mixed $request = null): WsRequest {
		return new WsRequest($method, $request);
	}

	/**
	 * Creates a new Scenario which contains a Flow of Requests.
	 * @link https://www.artillery.io/docs/guides/getting-started/writing-your-first-test#scenarios
	 * @link https://www.artillery.io/docs/guides/overview/why-artillery#scenarios
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#overview
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#scenarios-section
	 * @return Scenario A new Scenario instance.
	 */
	public static function scenario(): Scenario {
		return new Scenario();
	}

	/**
	 * Creates a new Artillery instance from an array representation.
	 * @param array{config?: array, before?: array, scenarios?: array, after?: array} $script Array representation of an Artillery instance.
	 * @return $this A new Artillery instance.
	 */
	public static function fromArray(array $script): self {
		$instance = new self();
		if (isset($script['config'])) $instance->config = $script['config'];
		if (isset($script['before'])) $instance->before = $script['before'];
		if (isset($script['scenarios'])) $instance->scenarios = $script['scenarios'];
		if (isset($script['after'])) $instance->after = $script['after'];
		return $instance;
	}

	/**
	 * Export the Artillery script as YAML string.
	 * @link https://symfony.com/doc/current/components/yaml.html
	 * @param bool $correctNewlines Whether to correct newlines in the YAML string, try turning this off if you have broken output.
	 * @param int $inline The level where you switch to inline YAML.
	 * @param int $indent The number of spaces to use for indentation of nested nodes.
	 * @param int $flags A bit field of Yaml::DUMP_* constants to customize the dumped YAML string.
	 * @return string The YAML representation of the Artillery script.
	 */
	public function toYaml(bool $correctNewlines = true, int $inline = PHP_INT_MAX, int $indent = 2, int $flags = 0): string {
		if ($correctNewlines) {
			return preg_replace_callback('/-\s*\n\s*(\w+)/', function ($matches) {
				return '- ' . $matches[1];
			}, Yaml::dump($this->toArray(), $inline, $indent, $flags));
		}

		return Yaml::dump($this->toArray(), $inline, $indent, $flags);
	}

	/** @internal */
	public function toArray(): array {
		$ret = [];
		if ($this->config) $ret['config'] = $this->config;
		if ($this->before) $ret['before'] = $this->before;
		if ($this->scenarios) $ret['scenarios'] = $this->scenarios;
		if ($this->after) $ret['after'] = $this->after;
		return $ret;
	}

	/**
	 * Set a Scenario or Flow to the 'after' section of the Artillery script, which will be executed after a scenario.
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#before-and-after-sections
	 * @param Scenario|Flow $scenario The Scenario or Flow instance to add.
	 * @return $this The current Artillery instance.
	 */
	public function setAfter(Scenario|Flow $scenario): self {
		if ($scenario instanceof Scenario) $this->after = $scenario->toArray();
		else $this->after = ['flow' => $scenario->toArray()];
		return $this;
	}

	/**
	 * Set a Scenario or Flow to the 'before' section of the Artillery script, which will be executed before a main scenario.
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#before-and-after-sections
	 * @param Scenario|RequestInterface|RequestInterface[] $scenario The Scenario, or Request, or array of requests to add as a new entry in the scenarios section of the script.
	 * @return $this The current Artillery instance.
	 */
	public function setBefore(array|Scenario|RequestInterface $scenario): self {
		if ($scenario instanceof Scenario) $this->before = $scenario->toArray();
		else $this->before = ['flow' => $scenario->toArray()];
		return $this;
	}

	/**
	 * Adds a Scenario to the main scenarios section of the Artillery script. You can also provide a single Request or array of Requests, and a scenario will be made from it.
	 * Optionally, you can provide scenario-level options to set or override for the scenario, such as name or weight.
	 * @example <pre><code class="language-php">// An extremely simple Artillery script with just one request as a scenario:
	 * $artillery = Artillery::new()->addScenario(Artillery::request('GET', 'https://example.com'));
	 * file_put_contents('script.yml', $artillery->toYaml());
	 *
	 * // More complex Scenario that loops over a set of pages from a target base url:
	 * $scenario = Artillery::scenario()
	 *     ->addRequest(Artillery::request('GET', '/'))
	 *     ->addRequest(Artillery::request('GET', '/about'))
	 *     ->addRequest(Artillery::request('GET', '/contact'));
	 *
	 * $artillery = Artillery::new('https://example.com')
	 *     ->addScenario(Artillery::scenario()->addLoop($scenario));
	 *
	 * file_put_contents('script.yml', $artillery->toYaml());
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#scenarios-section
	 * @param Scenario|RequestInterface|RequestInterface[] $scenario The Scenario, or Request, or array of requests to add as a new entry in the scenarios section of the script.
	 * @param array<string, mixed> $options Scenario level options to set or override for the scenario.
	 * @return $this The current Artillery instance.
	 */
	public function addScenario(array|Scenario|RequestInterface $scenario, array $options = null): self {
		if ($scenario instanceof Scenario) $this->scenarios[] = array_merge($scenario->toArray(), $options ?? []);
		else if ($scenario instanceof RequestInterface) $this->scenarios[] = ['flow' => [$scenario->toArray()]] + ($options ?? []);
		else $this->scenarios[] = ['flow' => [array_map(fn($r) => $r->toArray(), $scenario)]] + ($options ?? []);
		return $this;
	}

	// region Config

	/**
	 * Adds an 'ensure' condition to the Artillery script. Metrics are listed in the report output.
	 * @description Artillery can validate if a metrics value meets a predefined threshold. If it doesn't, it will exit with a non-zero exit code.<br>
	 * The built-in 'ensure' plugin needs to be enabled with Artillery::addPlugin('ensure') for this feature.
	 * @example <pre><code class="language-php">$artillery = Artillery::new()
	 *     ->addEnsureCondition('http.response_time.p95 < 250 and http.request_rate > 1000');
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#advanced-conditional-checks
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#ensure---slo-checks
	 * @link https://www.artillery.io/docs/guides/plugins/plugins-overview
	 * @param string $expression The expression to be used as a condition.
	 * @param bool $strict If set to false, the condition is not strict.
	 * @return $this The current Artillery instance.
	 */
	public function addEnsureCondition(string $expression, bool $strict = true): self {
		if (!array_key_exists('ensure', $this->config)) $this->config['ensure'] = [];
		if (!array_key_exists('conditions', $this->config['ensure'])) $this->config['ensure']['conditions'] = [];
		$condition = ['expression' => $expression];
		if ($strict === false) $condition['strict'] = false;
		$this->config['ensure']['conditions'][] = $condition;
		return $this;
	}

	/**
	 * Adds an 'ensure' threshold to the config section of the Artillery script.
	 * @description Artillery can validate if a metrics value meets a predefined threshold. If it doesn't, it will exit with a non-zero exit code.<br>
	 * The built-in 'ensure' plugin needs to be enabled with Artillery::addPlugin('ensure') for this feature.
	 * @example <pre><code class="language-php">$artillery = Artillery::new()
	 *     ->addEnsureThreshold('http.response_time.p99', 250)
	 *     ->addEnsureThreshold('http.response_time.p95', 100);
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#threshold-checks
	 * @param string $metricName The name of the metric to be used as a threshold.
	 * @param int $value The threshold value.
	 * @return $this The current Artillery instance.
	 */
	public function addEnsureThreshold(string $metricName, int $value): self {
		if (!array_key_exists('ensure', $this->config)) $this->config['ensure'] = [];
		if (!array_key_exists('thresholds', $this->config['ensure'])) $this->config['ensure']['thresholds'] = [];
		$this->config['ensure']['thresholds'][] = [$metricName => $value];
		return $this;
	}

	/**
	 * Add a custom engine to the config section of the Artillery script.
	 * @example <pre><code class="language-php">$artillery->addEngine('custom');
	 * $artillery->set('custom', ['some' => 'setting']);</code></pre>
	 * @param string $name The name of the engine.
	 * @param array $options The options for this engine.
	 * @return $this The current Artillery instance.
	 */
	public function addEngine(string $name, array $options = []): self {
		if (!array_key_exists('engines', $this->config)) $this->config['engines'] = [];
		$this->config['engines'][$name] = $options;
		return $this;
	}

	/**
	 * Adds an environment to the config section of the Artillery script.
	 * @description When running your performance test, you can specify the environment on the command line using the -e flag.
	 * @example <pre><code class="language-php">$artillery->addEnvironment('staging', ['target' => 'https://staging.example.com'])
	 *     ->addEnvironment('production', ['target' => 'https://example.com'])
	 *     ->addEnvironment('local', ['target' => 'http://localhost:8080']);
	 * </code></pre>
	 * <pre><code>artillery run -e local my-script.yml</code></pre>
	 * @param string $name The name of the environment.
	 * @param array $config Config overrides for this environment.
	 * @return $this The current Artillery instance.
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#environments---config-profiles
	 * @example <pre><code class="language-php">$artillery->addEnvironment('staging', ['target' => 'https://staging.example.com']);
	 * </code></pre>
	 * <pre><code>artillery run -e staging my-script.yml</code></pre>
	 */
	public function addEnvironment(string $name, array $config): self {
		if (!array_key_exists('environments', $this->config)) $this->config['environments'] = [];
		$this->config['environments'][$name] = $config;
		return $this;
	}

	/**
	 * Adds a CSV file as payload the config section of the Artillery script.
	 * @description You can use a CSV file to provide dynamic data to test scripts. For example, you might have a list of usernames and passwords that you want to use to test authentication in your API.
	 * @example <pre><code class="language-php">$artillery->addPayload('users.csv', ['username', 'password']);
	 * $artillery->addRequest(
	 *     Artillery::request('post', '/login')
	 *        ->setJson(['username' => '{{ username }}', 'password' => '{{ password }}']
	 * );
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#payload---loading-data-from-csv-files
	 * @param string $path The path of the payload file.
	 * @param array $fields The fields to be used from the payload file.
	 * @param array $options Additional options for the payload.
	 * @return $this The current Artillery instance.
	 */
	public function addPayload(string $path, array $fields, array $options = []): self {
		if (!array_key_exists('payload', $this->config)) $this->config['payload'] = [];
		$this->config['payload'][] = ['path' => $path, 'fields' => $fields] + $options;
		return $this;
	}

	/**
	 * Adds a phase to the config section of the Artillery script.
	 * @description A load phase defines how Artillery generates new virtual users (VUs) in a specified time period. For example, a typical performance test will have a gentle warm-up phase, followed by a ramp-up phase, and finalizing with a maximum load for a duration of time.
	 * @param array{duration?: int, arrivalCount?: int, arrivalRate?: int, rampTo?: int, pause?: int} $phase The phase to be added.
	 * @param string|null $name The name of the phase.
	 * @return $this The current Artillery instance.
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#phases---load-phases
	 * @example <pre><code class="language-php">$artillery->addPhase(['duration' => 60, 'arrivalRate' => 10], 'warm up')
	 *     ->addPhase(['duration' => 300, 'arrivalRate' => 10, 'rampTo' => 100], 'ramp up')
	 *     ->addPhase(['duration' => 600, 'arrivalRate' => 100], 'sustained load');
	 * </code></pre>
	 */
	public function addPhase(array $phase, string $name = null): self {
		if (!array_key_exists('phases', $this->config)) $this->config['phases'] = [];
		if ($name) $phase['name'] = $name;
		$this->config['phases'][] = $phase;
		return $this;
	}

	/**
	 * Enables a plugin in the config section of the Artillery script.
	 * @description Artillery has support for plugins, which can add functionality and extend its built-in features. Plugins can hook into Artillery's internal APIs and extend its behavior with new capabilities.<br>
	 * Plugins are distributed as normal npm packages which are named with an artillery-plugin- prefix, e.g. artillery-plugin-expect.
	 * @example <pre><code>npm install artillery-plugin-expect</code></pre>
	 * <pre><code class="language-php">// There is built in support for 'expect' and 'ensure' plugins.
	 * $artillery->addPlugin('expect');
	 * $expectRequest = Artillery::request('get', '/users/1')
	 *     ->addExpect('statusCode', [200, 201]);
	 *
	 * // Others can be handled like this:
	 * $artillery->addPlugin('hls');
	 * $customRequest = Artillery::request('get', '/users/1')
	 *        ->set('hls', ['concurrency' => 200, 'throttle' => 128]);
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/plugins/plugins-overview
	 * @link https://www.npmjs.com/search?ranking=popularity&q=artillery-plugin-
	 * @link https://www.artillery.io/docs/guides/plugins/plugin-expectations-assertions
	 * @link https://www.npmjs.com/package/artillery-plugin-hls
	 * @param string $name The name of the plugin.
	 * @param array $options The options for the plugin.
	 * @return $this The current Artillery instance.
	 */
	public function addPlugin(string $name, array $options = []): self {
		if (!array_key_exists('plugins', $this->config)) $this->config['plugins'] = [];
		$this->config['plugins'][$name] = $options;
		return $this;
	}

	/**
	 * Set a variable in the config section of the Artillery script.
	 * @description Variables can be defined in the config section and used in scenarios as '{{ name }}'.<br>
	 * If you define multiple values for a variable, they will be accessed randomly in your scenarios.
	 * @example <pre><code class="language-php">// Define 3 users:
	 * $artillery->addVariable('username', ['user1', 'user2', 'user3']);
	 * // Pick a random one for each scenario:
	 * $artillery->addRequest(
	 *    Artillery::request('post', '/login')
	 *      ->setJson(['username' => '{{ username }}', 'password' => '12345678']);
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#variables---inline-variables
	 * @param string $name The name of the variable, to be referenced later as '{{ name }}'.
	 * @param mixed $value The value of the variable.
	 * @return $this The current Artillery instance.
	 */
	public function addVariable(string $name, mixed $value): self {
		if (!array_key_exists('variables', $this->config)) $this->config['variables'] = [];
		$this->config['variables'][$name] = $value;
		return $this;
	}

	/**
	 * Set the options for the http property in the config section of the Artillery script.
	 * @example <pre><code class="language-php">// Set the timeout to 5 seconds and the maximum number of sockets per virtual user to 1.
	 * $artillery->setHttp(['timeout' => 5, 'maxSockets' => 1]);
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#http-specific-configuration
	 * @param array{timeout?: int, pool?: int, maxSockets?: int, extendedMetrics?: bool} $options The http options.
	 * @return $this The current Artillery instance.
	 */
	public function setHttp(array $options): self {
		$this->config['http'] = $options;
		return $this;
	}

	/**
	 * Set an arbitrary option in the config section of the Artillery script, such as custom engine settings.
	 * @param string $key The name of the option in the config section.
	 * @param mixed $value The value of the option.
	 * @return $this The current Artillery instance.
	 */
	public function set(string $key, mixed $value): self {
		$this->config[$key] = $value;
		return $this;
	}

	/**
	 * Set the JavaScript processor file for the Artillery script.
	 * @description Functions can be run as part of a flow, or in the various hooks such as beforeScenario, afterResponse, etc.
	 * @example <pre><code class="language-php">$artillery->setProcessor('processor.js');
	 * $request = Artillery::request('get', '/users/1')
	 *    ->addFunction('myFunction');
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#processor---custom-js-code
	 * @param string $path The path of the processor js file relative to the artillery script.
	 * @return $this The current Artillery instance.
	 */
	public function setProcessor(string $path): self {
		$this->config['processor'] = $path;
		return $this;
	}

	/**
	 * Set the target url property of the config section of the Artillery script, used as the base url for requests.
	 * @example <pre><code class="language-php">$artillery->setTarget('http://localhost:3000');
	 *     ->addScenario(Artillery::request('get', '/users/1'));
	 * </code></pre>
	 * @param string $url The base url of the target, e.g. http://localhost:3000.
	 * @return $this The current Artillery instance.
	 */
	public function setTarget(string $url): self {
		$this->config['target'] = $url;
		return $this;
	}

	/**
	 * Set the tls property of the config section of the Artillery script.
	 * @description This setting may be used to tell Artillery to accept self-signed TLS certificates, which it does not do by default.
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#tls---self-signed-certificates
	 * @param bool $rejectUnauthorized Whether to reject unauthorized tls certificates.
	 * @return $this The current Artillery instance.
	 */
	public function setTls(bool $rejectUnauthorized): self {
		$this->config['tls'] = ['rejectUnauthorized' => $rejectUnauthorized];
		return $this;
	}

	/**
	 * Set the ws property of the config section of the Artillery script.
	 * @example <pre><code class="language-php">$artillery->setWs(['subprotocols' => ['json']);
	 * $scenario->addRequest(
	 *     Artillery::wsRequest('send', ['message' => 'Hello World!'])
	 *         ->addCapture('response', 'json', '$.message')
	 *     );
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/ws-reference#websocket-specific-configuration
	 * @link https://www.artillery.io/docs/guides/guides/ws-reference#subprotocols
	 * @link https://www.artillery.io/docs/guides/guides/ws-reference
	 * @description This is the configuration for WebSocket connections in scenarios using Scenario::setEngine('ws').
	 * @param array{pingInterval?: int, pingTimeout?: int} $wsOptions The ws options.
	 * @return $this The current Artillery instance.
	 */
	public function setWs(array $wsOptions): self {
		$this->config['ws'] = $wsOptions;
		return $this;
	}
	// endregion Config
}