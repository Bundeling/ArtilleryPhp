<?php

namespace ArtilleryPhp;

/** Functions shared across both HTTP and WebSocket requests. */
abstract class RequestBase implements RequestInterface {
	/** @internal */ protected string $method;
	/** @internal */ protected mixed $request;

	/** @internal */
	public function toArray(): array {
		return [$this->method => $this->request];
	}

	/**
	 * Set arbitrary data in the request, such as those from third party extension.
	 * @param mixed $data Arbitrary data to add to the request.
	 * @return $this The current Request instance.
	 * @example <pre><code class="language-php">$artillery = Artillery::new()->setPlugin('something', ['some' => 'option']);
	 * $request = Artillery::request('get', '/users/1')
	 *     ->set('something', ['pluginAction' => 'values']);
	 * </code></pre>
	 */
	public function set(string $key, mixed $data): self {
		$this->request[$key] = $data;
		return $this;
	}

	/**
	 * Set the method of this request, eg 'put', 'think', 'emit', etc.
	 * @param string $method The HTTP method for this request.
	 * @return $this The current Request instance.
	 */
	public function setMethod(string $method): self {
		$this->method = $method;
		return $this;
	}

	/**
	 * Completely override the request data for this request.
	 * @param mixed $request The request data.
	 * @return $this The current Request instance.
	 */
	public function setRequest(mixed $request): self {
		$this->request = $request;
		return $this;
	}

	/**
	 * Adds a capture to the request to parse responses and re-use those values in subsequent requests as '{{ alias }}'.
	 * @description The capture option must always have an as attribute, which names the value for use in subsequent requests. It also requires one of the following attributes:<br>
	 *  * json - Allows you to define a JSONPath expression.<br>
	 *  * xpath - Allows you to define an XPath expression.<br>
	 *  * regexp - Allows you to define a regular expression that gets passed to a RegExp constructor. A specific capturing group to return may be set with the group attribute (set to an integer index of the group in the regular expression). Flags for the regular expression may be set with the flags attribute.<br>
	 *  * header - Allows you to set the name of the response header whose value you want to capture.<br>
	 *  * selector - Allows you to define a Cheerio element selector. The attr attribute will contain the name of the attribute whose value we want. An optional index attribute may be set to a number to grab an element matching a selector at the specified index, "random" to grab an element at random, or "last" to grab the last matching element. If the index attribute is not specified, the first matching element will get captured.
	 * @param string $as Captured data alias.
	 * @param 'json'|'xpath'|'regexp'|'header'|'selector' $type Capture type (json, xpath, et.c.).
	 * @param string $expression Capture expression.
	 * @param bool $strict Whether to throw an error if the capture expression does not match anything.
	 * @param string|null $attr If $type is 'selector': The name of the attribute whose value we want.
	 * @param int|'random'|'last'|null $index The index attribute may be set to a number to grab an element matching a selector at the specified index, "random" to grab an element at random, or "last" to grab the last matching element. If the index attribute is not specified, the first matching element will get captured.
	 * @return $this The current Request instance.
	 * @example <pre><code class="language-php">// Cheerio element selector: $request->addCapture(as: "productUrl", type: "selector", expression: "a[class^=productLink]", index: "random", attr: "href")<br>
	 * // Xpath: $request->addCapture(as: "JourneyId", type: "xpath", expression: "(//Journey)[1]/JourneyId/text()")<br>
	 * // Header: $request->addCapture(as: "headerValue", type: "header", expression: "x-my-custom-header")
	 * // Json:
	 * $getIdRequest = Artillery::request('get', '/users')
	 *    ->addCapture('first_id', 'json', '$[0].id');
	 * $postMsgRequest = Artillery::request('post', '/inbox')
	 *   ->setPayload(['id' => '{{ first_id }}', 'msg' => 'Hello world!']);
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#extracting-and-re-using-parts-of-a-response-request-chaining
	 */
	public function addCapture(string $as, string $type, string $expression, bool $strict = true, string $attr = null, int|string $index = null): self {
		$capture = [$type => $expression];
		if ($expression === 'selector') {
			if ($index !== null) $capture['index'] = $index;
			if ($attr) $capture['attr'] = $attr;
		}
		$capture['as'] = $as;
		if ($strict === false) $capture['strict'] = false;

		if (!@$this->request['capture']) $this->request['capture'] = [];
		$this->request['capture'][] = $capture;
		return $this;
	}

	/**
	 * Adds an array of capture objects to the request.
	 * @description The capture option must always have an as attribute, which names the value for use in subsequent requests. It also requires one of the following attributes:<br>
	 *  * json - Allows you to define a JSONPath expression.<br>
	 *  * xpath - Allows you to define an XPath expression.<br>
	 *  * regexp - Allows you to define a regular expression that gets passed to a RegExp constructor. A specific capturing group to return may be set with the group attribute (set to an integer index of the group in the regular expression). Flags for the regular expression may be set with the flags attribute.<br>
	 *  * header - Allows you to set the name of the response header whose value you want to capture.<br>
	 *  * selector - Allows you to define a Cheerio element selector. The attr attribute will contain the name of the attribute whose value we want. An optional index attribute may be set to a number to grab an element matching a selector at the specified index, "random" to grab an element at random, or "last" to grab the last matching element. If the index attribute is not specified, the first matching element will get captured.
	 * @example <pre><code class="language-php">// Cheerio element selector: $request->addCapture(as: "productUrl", type: "selector", expression: "a[class^=productLink]", index: "random", attr: "href")<br>
	 * // Xpath: $request->addCapture(as: "JourneyId", type: "xpath", expression: "(//Journey)[1]/JourneyId/text()")<br>
	 * // Header: $request->addCapture(as: "headerValue", type: "header", expression: "x-my-custom-header")
	 * // Json:
	 * $getIdRequest = Artillery::request('get', '/users')
	 *     ->addCaptures([
	 *         ['as' => 'id', 'json' => '$[0].id'],
	 * 	       ['as' => 'name', 'json' => '$[0].name'],
	 *     ]);
	 *
	 * $postMsgRequest = Artillery::request('post', '/inbox')
	 *   ->setPayload(['id' => '{{ id }}', 'msg' => 'Hello {{ name }}!']);
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#extracting-and-re-using-parts-of-a-response-request-chaining
	 * @param array<'as'|'json'|'xpath'|'regexp'|'header'|'selector'|'attr'|'index'|'strict', int|string>[] $captures An array of capture objects. E.g. [['as' => 'user_id', 'json' => '$.id'], [...etc]].
	 * @return $this The current Request instance.
	 */
	public function addCaptures(array $captures): self {
		if (!@$this->request['capture']) $this->request['capture'] = [];
		$this->request['capture'] = array_merge($this->request['capture'], $captures);
		return $this;
	}

	/**
	 * Adds an expectation assertion to the request.
	 * @description Expectations are assertions that are checked after the request is made.
	 * If the assertion fails, the request is considered to have failed.<br>
	 * The built-in 'expect' plugin needs to be enabled with Artillery::setPlugin('expect') for this feature.
	 * Here's a list of the available expectations:<br>
	 * contentType, statusCode, notStatusCode: expectNotStatusCode, hasHeader, headerEquals, hasProperty, equals, matchesRegexp, notHasProperty, cdnHit.
	 * @example <pre><code>npm i artillery-plugin-expect</pre></code><br>
	 * <pre><code class="language-php">$artillery->setPlugin('expect');
	 * $ensureRequest = Artillery::request('get', '/users/1')
	 *    ->addExpect('statusCode', [200, 201]);
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/plugins/plugin-expectations-assertions#expectations
	 * @link https://www.artillery.io/docs/guides/plugins/plugin-expectations-assertions
	 * @param 'contentType'|'statusCode'|'notStatusCode'|'hasHeader'|'headerEquals'|'hasProperty'|'equals'|'matchesRegexp'|'notHasProperty'|'cdnHit' $type The type of expectation to add.
	 * @param mixed $value The value(s) to expect.
	 * @param mixed $equals Equals can be added after hasHeader and hasProperty to do an equality check.
	 * @return $this The current Request instance.
	 */
	public function addExpect(string $type, mixed $value, mixed $equals = null): self {
		if (!@$this->request['expect']) $this->request['expect'] = [];
		$this->request['expect'][] = [$type => $value];

		if ($equals !== null) {
			if ($type === 'hasProperty') {
				$this->request['expect'][] = ['equals' => $equals];
			} elseif ($type === 'hasHeader') {
				$this->request['expect'][] = ['headerEquals' => $equals];
			}
		}
		return $this;
	}

	/**
	 * Adds an array of expectation assertions to the request.
	 * @description Expectations are assertions that are checked after the request is made.
	 * If the assertion fails, the request is considered to have failed.<br>
	 * The built-in 'expect' plugin needs to be enabled with Artillery::setPlugin('expect') for this feature.
	 * @example <pre><code>npm i artillery-plugin-expect</pre></code><br>
	 * <pre><code class="language-php">$artillery->setPlugin('expect');
	 * $expectJson200 = [
	 *     ['statusCode' => 200],
	 *     ['contentType' => 'application/json'],
	 * ];
	 * $ensureRequest = Artillery::request('get', '/users/1')
	 *    ->addExpects($expectJson200);
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/plugins/plugin-expectations-assertions#expectations
	 * @link https://www.artillery.io/docs/guides/plugins/plugin-expectations-assertions
	 * @param array<'statusCode'|'notStatusCode'|'contentType'|'hasProperty'|'notHasProperty'|'equals'|'hasHeader'|'headerEquals'|'matchesRegexp'|'cdnHit', mixed>[] $expects
	 * @return $this The current Request instance.
	 */
	public function addExpects(array $expects): self {
		if (!@$this->request['expect']) $this->request['expect'] = [];
		$this->request['expect'] = array_merge($this->request['expect'], $expects);
		return $this;
	}

	/**
	 * Adds a matching statement to the request, similar to a capture but without an 'as' alias.
	 * @example <pre><code class="language-php">$request = Artillery::request('get', '/users')
	 *     ->addMatch('json', '$[0].name', 'John');
	 * @param 'json'|'xpath'|'regexp'|'header'|'selector' $type The type of capture expression.
	 * @param string $expression The capture expression.
	 * @param mixed $value The value to match against the capture.
	 * @return $this The current Request instance.
	 */
	public function addMatch(string $type, string $expression, mixed $value, bool $strict = true, string $attr = null, int|string $index = null): self {
		$match = [$type => $expression, 'value' => $value];
		if ($expression === 'selector') {
			if ($index !== null) $match['index'] = $index;
			if ($attr) $match['attr'] = $attr;
		}
		if ($strict === false) $match['strict'] = false;

		if (!@$this->request['match']) $this->request['match'] = [];
		$this->request['match'][] = $match;
		return $this;
	}

	/**
	 * Adds an array of matching statement to the request, similar to a capture but without an 'as' alias.
	 * @example <pre><code class="language-php">$request = Artillery::request('get', '/users')
	 *     ->addMatches([
	 *         ['json' => '$[0].name', 'value' => 'John'],
	 *         ['json' => '$[1].name', 'value' => 'Jane']
	 *     ]);
	 * @param array<'json'|'xpath'|'regexp'|'header'|'selector'|'value'|'strict'|'attr'|'index', mixed>[] $matches
	 * @return $this The current Request instance.
	 */
	public function addMatches(array $matches): self {
		if (!@$this->request['match']) $this->request['match'] = [];
		$this->request['match'] = array_merge($this->request['match'], $matches);
		return $this;
	}
}