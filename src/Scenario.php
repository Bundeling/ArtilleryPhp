<?php

namespace ArtilleryPhp;

/**
 * The scenario is the core of an Artillery test script. It defines the flow of requests.
 * It has all methods related to a scenario and its flow.
 * @example <pre><code class="language-php">$loginScenario = Artillery::scenario()
 *    ->addRequest(Artillery::request('GET', 'https://example.com/login'))
 *    ->addRequest(Artillery::request('POST', 'https://example.com/login', ['body' => ['username' => 'user', 'password' => 'pass']]));
 *
 * $artillery->addBefore($loginScenario)
 *     ->addScenario(Artillery::request('GET', 'https://example.com/inbox'), 'inbox')
 *     ->addScenario(Artillery::request('GET', 'https://example.com/news'), 'news');
 * </code></pre>
 * @link https://www.artillery.io/docs/guides/getting-started/writing-your-first-test#scenarios
 * @link https://www.artillery.io/docs/guides/overview/why-artillery#scenarios
 */
class Scenario {
	/** @internal */
	protected array $scenario = [];
	/** @internal */
	protected array $flow = [];

	/** @internal */
	public function toArray(): array {
		return $this->scenario + ['flow' => $this->flow];
	}

	/** @internal */
	protected function getFlow(): array {
		return $this->flow;
	}

	/**
	 * Add a function or array of functions from the JavaScript file defined with Artillery::setProcessor to be run at the end of this scenario.
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#setting-scenario-level-hooks
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#function-actions-and-beforescenario--afterscenario-hooks
	 * @param string|string[] $function The function or array of functions to add.
	 * @return $this The current Scenario instance.
	 */
	public function addAfterScenario(array|string $function): self {
		if (!array_key_exists('afterScenario', $this->scenario)) $this->scenario['afterScenario'] = [];
		$this->scenario['afterScenario'][] = $function;
		return $this;
	}

	/**
	 * Add a function or array of functions from the JavaScript file defined with Artillery::setProcessor to be run at the start of this scenario.
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#setting-scenario-level-hooks
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#function-actions-and-beforescenario--afterscenario-hooks
	 * @param string|string[] $function The function or array of functions to add.
	 * @return $this The current Scenario instance.
	 */
	public function addBeforeScenario(array|string $function): self {
		if (!array_key_exists('beforeScenario', $this->scenario)) $this->scenario['beforeScenario'] = [];
		$this->scenario['beforeScenario'][] = $function;
		return $this;
	}

	/**
	 * Set an engine to use for this scenario, if not set then the HTTP engine will be used. If 'ws' is set, then use WsRequest only in this Scenario.
	 * @description Custom engines can be defined with Artillery::addEngine.<br>
	 * This library aims to fully support HTTP, partial support for WebSocket, and only raw support for others.
	 * @param string $engine Desired engine to use in this scenario.
	 * @return $this The current Scenario instance.
	 * @example <pre><code class="language-php">$webSocketScenario = Artillery::scenario()
	 *     ->setEngine('ws')
	 *     ->addRequest(
	 *         Artillery::wsRequest('ws://localhost:8080', ['send' => 'Hello World!'])
	 *     );
	 * </code></pre>
	 */
	public function setEngine(string $engine): self {
		$this->scenario['engine'] = $engine;
		return $this;
	}

	/**
	 * Set the name of this scenario for metrics and reporting.
	 * @param string $name The name of this scenario.
	 * @return $this The current Scenario instance.
	 */
	public function setName(string $name): self {
		$this->scenario['name'] = $name;
		return $this;
	}

	/**
	 * Set the weight chance to be picked for this scenario when its weight is compared to that of other scenarios added to the Artillery script.
	 * @description Weights allow you to specify that some scenarios should be picked more often than others.<br>
	 * If it is not set, the default weight is 1.
	 * @example <pre><code class="language-php">// Here the $scenario1 will be picked 10% of the time:
	 * $artillery->addScenario($scenario1);
	 * $artillery->addScenario($scenario2->setWeight(10));
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/test-script-reference#scenario-weights
	 * @param int $weight The weight of this scenario.
	 * @return $this The current Scenario instance.
	 */
	public function setWeight(int $weight): self {
		$this->scenario['weight'] = $weight;
		return $this;
	}

	/**
	 * Add/merge the flow of another Scenario into this scenario's flow.
	 * @param Scenario $scenario The Scenario from which flow to add to this Scenario's flow.
	 * @return $this The current Scenario instance.
	 * @link https://www.artillery.io/docs/guides/getting-started/writing-your-first-test#adding-a-scenario-and-flow
	 * @example <pre><code class="language-php">$scenario = Artillery::scenario()
	 *     ->addFlow(
	 *         Artillery::Scenario()
	 *             ->addRequest(Artillery::request('GET', 'https://example.com'))
	 *             ->addThink(2)
	 *     );
	 * </code></pre>
	 */
	public function addFlow(Scenario $scenario): self {
		$this->flow = array_merge($this->flow, $scenario->getFlow());
		return $this;
	}

	// region Flow

	/**
	 * Add a request object directly to this scenario's flow.
	 * @param RequestInterface $request The request to add.
	 * @return $this The current Scenario instance.
	 * @link https://www.artillery.io/docs/guides/guides/http-reference
	 * @example <pre><code class="language-php">$scenario->addRequest(
	 *     Artillery::request('get', '/users')
	 *         ->addCapture('json', '$.users[0].id', 'userId')
	 *     );
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#get--post--put--patch--delete-requests
	 */
	public function addRequest(RequestInterface $request): self {
		$this->flow[] = $request->toArray();
		return $this;
	}

	/**
	 * Adds a loop to the flow, which can be another Scenario, a request or an array of either.
	 * To make it even more fun; loops can be nested.
	 * @param Scenario|RequestInterface|(Scenario|RequestInterface)[]  $loop A Scenario, Request or array containing these types.
	 * @param ?int $count The number of times to loop.
	 * @param string|null $over The variable reference to loop over.
	 * @param string|null $whileTrue The condition to continue looping.
	 * @return $this The current Scenario instance.
	 * @link https://github.com/rjnienaber/artillery-test/blob/master/nested_loops/test.yml
	 * @example <pre><code class="language-php">$scenario->addLoop(Artillery::request('get', '/pages/{{ $loopCount }}'), 100);
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#loops
	 */
	public function addLoop(array|Scenario|RequestInterface $loop, int $count = null, string $over = null, string $whileTrue = null): self {
		if ($loop instanceof Scenario) $ret = ['loop' => $loop->getFlow()];
		elseif ($loop instanceof RequestInterface) $ret = ['loop' => [$loop->toArray()]];
		else if (is_array($loop)) {
			$ret = ['loop' => []];
			foreach ($loop as $r) {
				if ($r instanceof Scenario) $ret['loop'][] = $r->getFlow();
				elseif ($r instanceof RequestInterface) $ret['loop'][] = [$r->toArray()];
				else $ret['loop'][] = $r;
			}
		}

		if ($count) $ret['count'] = $count;
		if ($over) $ret['over'] = $over;
		if ($whileTrue) $ret['whileTrue'] = $whileTrue;
		$this->flow[] = $ret;
		return $this;
	}

	/**
	 * Adds a log to the flow which will print messages to the console at this point in the flow.
	 * @description It can also include a variable reference like '{{ variable }}'.<br>
	 * Log messages will get printed to the console even when running the tests in "quiet" mode (using the --quiet or -q flag with the run command).
	 * @param string $message The message to log once this part of the flow is reached.
	 * @param string|null $ifTrue The condition for this log.
	 * @return $this The current Scenario instance.
	 * @example <pre><code class="language-php">$scenario->addLog('Creating user {{ username }}.')
	 *     ->addRequest(Artillery::request('post', '/new/{{ username }}'))
	 *     ->addLog('Changing name to {{ name }}.')
	 *     ->addRequest(Artillery::request('put', '/change/{{ username }}', ['name' => '{{ name }}']));
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#logging
	 */
	public function addLog(string $message, string $ifTrue = null): self {
		$this->flow[] = ['log' => $message] + ($ifTrue ? ['ifTrue' => $ifTrue] : []);
		return $this;
	}

	/**
	 * Adds a pause segment to the flow to pause the virtual user for N seconds.
	 * @description The argument to think is the number of second to pause for.<br>
	 * Floating numbers are supported, e.g., 0.5 pauses for half a second.
	 * @param float|int $duration The duration of the pause in seconds.
	 * @param string|null $ifTrue The condition for this pause to be run
	 * @return $this The current Scenario instance.
	 * @example <pre><code class="language-php">$scenario->addRequest(Artillery::request('post', '/new/{{ username }}'))
	 *    ->addThink(0.5)
	 *    ->addRequest(Artillery::request('put', '/change/{{ username }}', ['name' => '{{ name }}']));
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#pausing-execution-with-think
	 */
	public function addThink(float|int $duration, string $ifTrue = null): self {
		$this->flow[] = ['think' => $duration] + ($ifTrue ? ['ifTrue' => $ifTrue] : []);
		return $this;
	}

	/**
	 * Set a function or array of functions from the JavaScript file defined with Artillery::setProcessor() to be executed at this point in the Scenario's flow.
	 * @param string|string[] $function The function to add.
	 * @param string|null $ifTrue The condition for this function to be run
	 * @return $this The current Scenario instance.
	 * @example <pre><code class="language-php">$artillery->setProcessor('functions.js');
	 * // Possibly set some variables from javascript at the start of the flow.
	 * $scenario = Artillery::scenario()
	 *    ->addFunction('myFunction');
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#function-steps-in-a-flow
	 */
	public function addFunction(array|string $function, string $ifTrue = null): self {
		$this->flow[] = ['function' => $function] + ($ifTrue ? ['ifTrue' => $ifTrue] : []);
		return $this;
	}

	// endregion
}