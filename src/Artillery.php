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
 *     ->setPlugin('expect');
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
	 * @return Artillery A new Artillery instance.
	 */
	public static function fromArray(array $script): self {
		$instance = new self();
		if (@$script['config']) $instance->config = $script['config'];
		if (@$script['before']) $instance->before = $script['before'];
		if (@$script['scenarios']) $instance->scenarios = $script['scenarios'];
		if (@$script['after']) $instance->after = $script['after'];
		return $instance;
	}

	/**
	 * Creates a new Artillery instance copy from another Artillery instance.
	 * @param Artillery $script Another Artillery instance.
	 * @return Artillery A new Artillery instance.
	 */
	public static function from(Artillery $script): self {
		return self::fromArray($script->toArray());
	}

	/**
	 * Export the Artillery script as YAML string.
	 * @link https://symfony.com/doc/current/components/yaml.html
	 * @param bool $correctNewlines Keep array entries on the same line as its dash notation, try turning this off if you have broken output.
	 * @param int $inline The level where you switch to inline YAML.
	 * @param int $indent The number of spaces to use for indentation of nested nodes.
	 * @param int $flags A bit field of Yaml::DUMP_* constants to customize the dumped YAML string.
	 * @return string The YAML representation of the Artillery script.
	 */
	public function toYaml(bool $correctNewlines = true, int $inline = PHP_INT_MAX, int $indent = 2, int $flags = 0): string {
		if (!$correctNewlines) return Yaml::dump($this->toArray(), $inline, $indent, $flags);

		return preg_replace_callback('/-\s*\n\s*(\w+)/', function ($matches) {
			return '- ' . $matches[1];
		}, Yaml::dump($this->toArray(), $inline, $indent, $flags));
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
	 * Set a Scenario or flow of Request(s) to the 'after' section of the Artillery script, which will be executed after a scenario.
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#before-and-after-sections
	 * @param RequestInterface[]|RequestInterface|Scenario $scenario The Scenario or Request(s) to set as the 'after' scenario.
	 * @return $this The current Artillery instance.
	 */
	public function setAfter(array|RequestInterface|Scenario $scenario): self {
		if ($scenario instanceof Scenario) $this->after = $scenario->toArray();
		elseif (is_array($scenario)) $this->after = ['flow' => array_map(fn($s) => $s->toArray(), $scenario)];
		else $this->after = ['flow' => $scenario->toArray()];
		return $this;
	}

	/**
	 * Set a Scenario or flow of Request(s) to the 'before' section of the Artillery script, which will be executed before a main scenario.
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#before-and-after-sections
	 * @param RequestInterface[]|RequestInterface|Scenario $scenario The Scenario or Request(s) to set as the 'before' scenario.
	 * @return $this The current Artillery instance.
	 */
	public function setBefore(array|RequestInterface|Scenario $scenario): self {
		if ($scenario instanceof Scenario) $this->before = $scenario->toArray();
		elseif (is_array($scenario)) $this->before = ['flow' => array_map(fn($s) => $s->toArray(), $scenario)];
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

	/**
	 * Adds an array of Scenarios to the main scenarios section of the Artillery script. You can also provide a single Request or array of Requests, and a scenario will be made from it.
	 * @example <pre><code class="language-php"> // Define a set of scenarios to use in the script, in this case 3 different scenarios.
	 * $defaultScenarios = [
	 *     Artillery::scenario()->addRequest(Artillery::request('GET', '/')),
	 *     Artillery::request('GET', '/about'),
	 *     [Artillery::request('GET', '/contact'), Artillery::request('GET', '/contact-us')],
	 * ];
	 *
	 * // Add the 3 scenarios to the Artillery script.
	 * $artillery = Artillery::new()->addScenarios($defaultScenarios);
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#scenarios-section
	 * @param (Scenario|RequestInterface|RequestInterface[])[] $scenarios An array of Scenarios, or Requests, or arrays of Requests to add as new entries in the scenarios section of the script.
	 * @return $this The current Artillery instance.
	 */
	public function addScenarios(array $scenarios): self {
		foreach ($scenarios as $scenario) {
			$this->addScenario($scenario);
		}
		return $this;
	}

	// region Config

	/**
	 * Adds an 'ensure' condition to the Artillery script. Metrics are listed in the report output.
	 * @description Artillery can validate if a metrics value meets a predefined threshold. If it doesn't, it will exit with a non-zero exit code.<br>
	 * Setting strict: false on a condition will make that check optional. Failing optional checks do not cause Artillery to exit with a non-zero exit code. Checks are strict by default.<br>
	 * The built-in 'ensure' plugin needs to be enabled with Artillery::setPlugin('ensure') for this feature.
	 * @example <pre><code class="language-php">$artillery = Artillery::new()
	 *     ->setPlugin('ensure')
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
		if (!@$this->config['ensure']) $this->config['ensure'] = [];
		if (!@$this->config['ensure']['conditions']) $this->config['ensure']['conditions'] = [];
		$condition = ['expression' => $expression];
		if ($strict === false) $condition['strict'] = false;
		$this->config['ensure']['conditions'][] = $condition;
		return $this;
	}

	/**
	 * Adds an 'ensure' threshold to the config section of the Artillery script.
	 * @description Artillery can validate if a metrics value meets a predefined threshold. If it doesn't, it will exit with a non-zero exit code.<br>
	 * The built-in 'ensure' plugin needs to be enabled with Artillery::setPlugin('ensure') for this feature.
	 * @example <pre><code class="language-php">$artillery = Artillery::new()
	 *     ->setPlugin('ensure')
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
	 * @example <pre><code class="language-php">$artillery->setEngine('custom');
	 * $artillery->set('custom', ['some' => 'setting']);</code></pre>
	 * @param string $name The name of the engine.
	 * @param array $options The options for this engine.
	 * @return $this The current Artillery instance.
	 */
	public function setEngine(string $name, array $options = []): self {
		if (!array_key_exists('engines', $this->config)) $this->config['engines'] = [];
		$this->config['engines'][$name] = $options;
		return $this;
	}

	/**
	 *  TODO: document
	 * @param array $engines
	 * @return $this
	 */
	public function setEngines(array $engines): self {
		foreach ($engines as $name => $options) $this->setEngine($name, $options);
		return $this;
	}

	/**
	 * Adds an environment to the config's environments section of the Artillery script with environment-specific config overrides.
	 * Either as an array or another Artillery instance (just make sure not to have nested environments defined).
	 * @description When running your performance test, you can specify the environment on the command line using the -e flag.
	 * @example <pre><code class="language-php">$local = Artillery::new('http://localhost:8080')
	 *     ->addPhase(['duration' => 30, 'arrivalRate' => 1, 'rampTo' => 10])
	 *     ->setHttpTimeout(60);
	 *
	 * $production = Artillery::new('https://example.com')
	 *     ->addPhase(['duration' => 300, 'arrivalRate' => 10, 'rampTo' => 100])
	 *     ->setHttpTimeout(30);
	 *
	 * $artillery = Artillery::new()
	 *     ->addEnvironment('staging', ['target' => 'https://staging.example.com'])
	 *     ->addEnvironment('production', $production)
	 *     ->addEnvironment('local', $local);
	 * </code></pre>
	 * <pre><code>artillery run -e local my-script.yml</code></pre>
	 * @param string $name The name of the environment.
	 * @param Artillery|array $config Config overrides for this environment, as an array or another Artillery instance.
	 * @return $this The current Artillery instance.
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#environments---config-profiles
	 * @example <pre><code class="language-php">$artillery->addEnvironment('staging', ['target' => 'https://staging.example.com']);
	 * </code></pre>
	 * <pre><code>artillery run -e staging my-script.yml</code></pre>
	 */
	public function addEnvironment(string $name, Artillery|array $config): self {
		if (!array_key_exists('environments', $this->config)) $this->config['environments'] = [];
		if ($config instanceof Artillery) $this->config['environments'][$name] = $config->config;
	    else $this->config['environments'][$name] = $config;
		return $this;
	}

	/**
	 * Adds an array of environments to the config's environments section of the Artillery script.
	 * Either as arrays or Artillery instances (just make sure not to have nested environments defined).
	 * @description When running your performance test, you can specify the environment on the command line using the -e flag.
	 * @example <pre><code class="language-php">$local = Artillery::new('http://localhost:8080')
	 *     ->addPhase(['duration' => 30, 'arrivalRate' => 1, 'rampTo' => 10])
	 *     ->setHttpTimeout(60);
	 *
	 * $production = Artillery::new('https://example.com')
	 *     ->addPhase(['duration' => 300, 'arrivalRate' => 10, 'rampTo' => 100])
	 *     ->setHttpTimeout(30);
	 *
	 * $defaultEnvironments = [
	 *     'staging' => ['target' => 'https://staging.example.com'],
	 *     'production' => $production,
	 *     'local' => $local
	 * ];
	 *
	 * $artillery = Artillery::new()->addEnvironments($defaultEnvironments);
	 * </code></pre>
	 * <pre><code>artillery run -e local my-script.yml</code></pre>
	 * @param array<string, array|Artillery> $environments Environment definitions, as arrays or Artillery instances.
	 * @return $this The current Artillery instance.
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#environments---config-profiles
	 * @example <pre><code class="language-php">$artillery->addEnvironment('staging', ['target' => 'https://staging.example.com']);
	 * </code></pre>
	 * <pre><code>artillery run -e staging my-script.yml</code></pre>
	 */
	public function addEnvironments(array $environments): self {
		foreach ($environments as $name => $config) $this->addEnvironment($name, $config);
		return $this;
	}

	/**
	 * Adds a CSV file as payload the config section of the Artillery script.
	 * @description You can use a CSV file to provide dynamic data to test scripts.<br>
	 * For example, you might have a list of usernames and passwords that you want to use to test authentication in your API.<br>
	 * Payload file options:<br>
	 *  * order (default: random) - Control how rows are selected from the CSV file for each new virtual user. This option may be set to sequence to iterate through the rows in a sequence (looping around and starting from the beginning after reaching the last row). Note that this will not work as expected when running distributed tests, as each node will have its own copy of the CSV data.<br>
	 *  * skipHeader (default: false) - Set to true to make Artillery skip the first row in the file (typically the header row).<br>
	 *  * delimiter (default: ,) - If the payload file uses a delimiter other than a comma, set this option to the delimiter character.<br>
	 *  * cast (default: true) - By default, Artillery will convert fields to native types (e.g. numbers or booleans). To keep those fields as strings, set this option to false.<br>
	 *  * skipEmptyLines (default: true) - By default, Artillery skips empty lines in the payload. Set to false to include empty lines.<br>
	 *  * loadAll and name - set loadAll to true to provide all rows to each VU, and name to a variable name which will contain the data
	 * @example <pre><code class="language-php">$artillery = Artillery::new()
	 *     ->addPayload('users.csv', ['username', 'password'], ['skipHeader' => true])
	 *     ->addScenario(Artillery::request('post', '/login')
	 *         ->setJsons(['username' => '{{ username }}', 'password' => '{{ password }}']));
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#payload---loading-data-from-csv-files
	 * @param string $path The path of the payload file.
	 * @param array $fields The fields to be used from the payload file.
	 * @param array<'order'|'skipHeader'|'delimiter'|'cast'|'skipEmptyLines'|'loadAll'|'name', string|bool> $options Additional options for the payload.
	 * @return $this The current Artillery instance.
	 */
	public function addPayload(string $path, array $fields, array $options = []): self {
		if (!array_key_exists('payload', $this->config)) $this->config['payload'] = [];
		$this->config['payload'][] = ['path' => $path, 'fields' => $fields] + $options;
		return $this;
	}

	/**
	 * Adds an array of payloads the config section of the Artillery script.
	 * @description You can use a CSV file to provide dynamic data to test scripts.<br>
	 * For example, you might have a list of usernames and passwords that you want to use to test authentication in your API.<br>
	 * Payload file options:<br>
	 *  * fields - Names of variables to use for each column in the CSV file
	 *  * order (default: random) - Control how rows are selected from the CSV file for each new virtual user. This option may be set to sequence to iterate through the rows in a sequence (looping around and starting from the beginning after reaching the last row). Note that this will not work as expected when running distributed tests, as each node will have its own copy of the CSV data.
	 *  * skipHeader (default: false) - Set to true to make Artillery skip the first row in the file (typically the header row).
	 *  * delimiter (default: ,) - If the payload file uses a delimiter other than a comma, set this option to the delimiter character.
	 *  * cast (default: true) - By default, Artillery will convert fields to native types (e.g. numbers or booleans). To keep those fields as strings, set this option to false.
	 *  * skipEmptyLines (default: true) - By default, Artillery skips empty lines in the payload. Set to false to include empty lines.
	 *  * loadAll and name - set loadAll to true to provide all rows to each VU, and name to a variable name which will contain the data
	 * @example <pre><code class="language-php">$defaultPayloads = [
	 *     ['path' => 'users.csv', 'fields' => ['username', 'password'], 'skipHeader' => true]
	 *     ['path' => 'animals.csv', 'fields' => ['name', 'specie'], 'skipHeader' => true]
	 * ];
	 *
	 * $scenario = Artillery::scenario()
	 *     ->addRequest(Artillery::request('post', '/login')
	 *         ->setJsons(['username' => '{{ username }}', 'password' => '{{ password }}']))
	 *     ->addRequest(Artillery::request('get', '/animals')
	 *         ->setJsons(['name' => '{{ name }}', 'specie' => '{{ specie }}']));
	 *
	 * $artillery = Artillery::new()->addPayloads($defaultPayloads)->addScenario($scenario);
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#payload---loading-data-from-csv-files
	 * @param array<'path'|'fields'|'order'|'skipHeader'|'delimiter'|'cast'|'skipEmptyLines'|'loadAll'|'name', string|bool>[] $payloads Payloads to be added.
	 * @return $this The current Artillery instance.
	 */
	public function addPayloads(array $payloads): self {
		if (!array_key_exists('payload', $this->config)) $this->config['payload'] = [];
		foreach ($payloads as $payload) {
			$this->config['payload'][] = $payload;
		}
		return $this;
	}

	/**
	 * Adds a phase to the config section of the Artillery script.
	 * @description A load phase defines how Artillery generates new virtual users (VUs) in a specified time period. For example, a typical performance test will have a gentle warm-up phase, followed by a ramp-up phase, and finalizing with a maximum load for a duration of time.
	 * @param array{duration?: int, arrivalCount?: int, arrivalRate?: int, rampTo?: int, pause?: int} $phase The phase to be added.
	 * @param string|null $name The name of the phase.
	 * @return $this The current Artillery instance.
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#phases---load-phases
	 * @example <pre><code class="language-php">$artillery = Artillery::new()
	 *     ->addPhase(['duration' => 60, 'arrivalRate' => 10], 'warm up')
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
	 * Adds an array of phase to the config section of the Artillery script.
	 * @description A load phase defines how Artillery generates new virtual users (VUs) in a specified time period. For example, a typical performance test will have a gentle warm-up phase, followed by a ramp-up phase, and finalizing with a maximum load for a duration of time.
	 * @param array{duration?: int, arrivalCount?: int, arrivalRate?: int, rampTo?: int, pause?: int, name?: string}[] $phases The phases to be added.
	 * @return $this The current Artillery instance.
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#phases---load-phases
	 * @example <pre><code class="language-php">$defaultPhases = [
	 *     ['duration' => 60, 'arrivalRate' => 10, 'name' => 'warm up'],
	 *     ['duration' => 300, 'arrivalRate' => 10, 'rampTo' => 100, 'name' => 'ramp up'],
	 *     ['duration' => 600, 'arrivalRate' => 100, 'name' => 'sustained load']
	 * ];
	 *
	 * $artillery = Artillery::new()->addPhases($defaultPhases);
	 * </code></pre>
	 */
	public function addPhases(array $phases): self {
		if (!array_key_exists('phases', $this->config)) $this->config['phases'] = [];
		foreach ($phases as $phase) {
			$this->config['phases'][] = $phase;
		}
		return $this;
	}

	/**
	 * Enables a plugin in the config section of the Artillery script.
	 * @description Artillery has support for plugins, which can add functionality and extend its built-in features. Plugins can hook into Artillery's internal APIs and extend its behavior with new capabilities.<br>
	 * Plugins are distributed as normal npm packages which are named with an artillery-plugin- prefix, e.g. artillery-plugin-expect.
	 * @example <pre><code>npm install artillery-plugin-expect</code></pre>
	 * <pre><code class="language-php">// There is built in support for 'expect' and 'ensure' plugins.
	 * $artillery->setPlugin('expect');
	 * $expectRequest = Artillery::request('get', '/users/1')
	 *     ->addExpect('statusCode', [200, 201]);
	 *
	 * // Others can be handled like this:
	 * $artillery->setPlugin('hls');
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
	public function setPlugin(string $name, array $options = []): self {
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
	 *      ->setJsons(['username' => '{{ username }}', 'password' => '12345678']);
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
	 * Set an option in the http property of the config section in the Artillery script.
	 * @example <pre><code class="language-php">// Set the timeout to 30 seconds:
	 * $artillery = Artillery::new()->setHttp('timeout', 30);
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#http-specific-configuration
	 * @param 'timeout'|'maxSockets'|'extendedMetrics' $key The key of the option.
	 * @param int|bool $value The value of the option.
	 * @return $this The current Artillery instance.
	 */
	public function setHttp(string $key, mixed $value): self {
		if (!array_key_exists('http', $this->config)) $this->config['http'] = [];
		$this->config['http'][$key] = $value;
		return $this;
	}

	/**
	 * Set an array of options for the http property in the config section of the Artillery script.
	 * @example <pre><code class="language-php">// Set the timeout to 30 seconds and the maximum number of sockets per virtual user to 1.
	 * $artillery = Artillery::new()->setHttps(['timeout' => 30, 'maxSockets' => 1]);
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#http-specific-configuration
	 * @param array<'timeout'|'maxSockets'|'extendedMetrics', int|bool> $options The http options.
	 * @return $this The current Artillery instance.
	 */
	public function setHttps(array $options): self {
		foreach($options as $key => $value) $this->setHttp($key, $value);
		return $this;
	}

	/**
	 * Set the timeout option for the http property in the config section of the Artillery script.
	 * @example <pre><code class="language-php">// Set the http timeout to 30 seconds
	 * $artillery = Artillery::new()->setHttpTimeout(30);
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#http-specific-configuration
	 * @param int $timeout The timeout in seconds.
	 * @return $this The current Artillery instance.
	 */
	public function setHttpTimeout(int $timeout): self {
		$this->setHttp('timeout', $timeout);
		return $this;
	}

	/**
	 * By default, Artillery creates one TCP connection per virtual user. To allow for multiple sockets per virtual user (to mimic the behavior of a web browser, for example), specify the number of connections.
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#max-sockets-per-virtual-user
	 * @param int $maxSockets The maximum number of sockets per virtual user.
	 * @return $this The current Artillery instance.
	 */
	public function setHttpMaxSockets(int $maxSockets): self {
		$this->setHttp('maxSockets', $maxSockets);
		return $this;
	}

	/**
	 * The HTTP engine can be configured to track additional performance metrics by setting extendedMetrics to true.
     * @description The following additional metrics will be reported:<br>
	 *  * 'http.dns' Time taken by DNS lookups<br>
	 *  * 'http.tcp' Time taken to establish TCP connections<br>
	 *  * 'http.tls' Time taken by completing TLS handshakes<br>
	 *  * 'http.total' Time for the entire response to be downloaded
	 * @param bool $extendedMetrics Whether to track additional performance metrics.
	 * @return $this The current Artillery instance.
	 */
	public function setHttpExtendedMetrics(bool $extendedMetrics = true): self {
		$this->setHttp('extendedMetrics', $extendedMetrics);
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
	 * @example <pre><code class="language-php">$artillery = Artillery::new()->setWs(['subprotocols' => ['json']);
	 * $artillery->addScenario(Artillery::wsRequest('send', ['message' => 'Hello World!'])
	 *         ->addCapture('response', 'json', '$.message'));
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