<?php

namespace ArtilleryPhp;

use stdClass;

/**
 * The Request class represents a single HTTP request to be made by Artillery within the flow section of a scenario.
 * @example <pre><code class="language-php">$getTarget = Artillery::request('get', '/inbox')
 *      ->setJson('client_id', '{{ id }}')
 *      ->addCapture('first_inbox_id', 'json', '$[0].id');
 * $postResponse = Artillery::request('post', '/inbox')
 *      ->setJsons(['user_id' => '{{ first_inbox_id }}', 'message' => 'Hello, world!']);
 * </code></pre>
 * @link https://www.artillery.io/docs/guides/guides/http-reference
 */
class Request extends RequestBase {
	/** @var array @internal */ protected mixed $request = [];

	/**
	 * Add a function or array of functions from the JavaScript file defined with Artillery::setProcessor to be executed after the response is received where the response can be inspected, and custom variables can be set.
	 * @param string|string[] $function The function(s) to execute.
	 * @return $this The current Request instance.
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#afterresponse-hooks
	 */
	public function addAfterResponse(array|string $function): self {
		if (!@$this->request['afterResponse']) $this->request['afterResponse'] = [];
		if (is_array($function)) $this->request['afterResponse'] = array_merge($this->request['afterResponse'], $function);
		else $this->request['afterResponse'][] = $function;
		return $this;
	}

	/**
	 * Add a function or array of functions from the JavaScript file defined with Artillery::setProcessor to be executed before the request is sent, where you can set headers or body dynamically.
	 * @param string|string[] $function The function(s) to execute.
	 * @return $this The current Request instance.
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#beforerequest-hooks
	 */
	public function addBeforeRequest(array|string $function): self {
		if (!@$this->request['beforeRequest']) $this->request['beforeRequest'] = [];
		if (is_array($function)) $this->request['beforeRequest'] = array_merge($this->request['beforeRequest'], $function);
		else $this->request['beforeRequest'][] = $function;
		return $this;
	}

	/**
	 * If your request requires Basic HTTP authentication, set your username and password under the auth option.
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Authentication
	 * @param string $user The username to use for authentication.
	 * @param string $pass The password to use for authentication.
	 * @return $this The current Request instance.
	 */
	public function setAuth(string $user, string $pass): self {
		$this->request['auth'] = ['user' => $user, 'pass' => $pass];
		return $this;
	}

	/**
	 * Set the body of the request. This can be a string or an array (which will be stringified into JSON).
	 * @param mixed $body The body of the request in arbitrary data.
	 * @return $this The current Request instance.
	 * @example <pre><code class="language-php">$request->setBody('Hello world!');</code></pre>
	 */
	public function setBody(mixed $body): self {
		$this->request['body'] = $body;
		return $this;
	}

	/**
	 * Set a Cookie to send with the request. These will be merged with any cookies that have been set globally.
	 * @param string $name The name of the cookie to set.
	 * @param string $value The value of the cookie to set.
	 * @return $this The current Request instance.
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#cookies
	 * @example <pre><code class="language-php">$request->setCookie('session_id', '1234567890');</code></pre>
	 */
	public function setCookie(string $name, string $value): self {
		if (!@$this->request['cookie']) $this->request['cookie'] = [];
		$this->request['cookie'][$name] = $value;
		return $this;
	}

	/**
	 * Set an array of Cookies to send with the request. These will be merged with any cookies that have been set globally.
	 * @param array<string, string> $cookies An array of cookies to set.
	 * @return $this The current Request instance.
	 * @example <pre><code class="language-php">$request->setCookies(['session_id' => '1234567890', 'name' => 'Geraldo']);</code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#cookies
	 */
	public function setCookies(array $cookies): self {
		foreach ($cookies as $name => $value) $this->setCookie($name, $value);
		return $this;
	}

	/**
	 * Artillery follows redirects by default. To stop Artillery from following redirects, set this to false.
	 * @param bool $followRedirect If set to false, redirects will not be followed. Defaults to true.
	 * @return $this The current Request instance.
	 */
	public function setFollowRedirect(bool $followRedirect = true): self {
		$this->request['followRedirect'] = $followRedirect;
		return $this;
	}

	/**
	 * Set a URL-encoded form attribute (application/x-www-form-urlencoded).
	 * @param string $key The key of the form data to set.
	 * @param mixed $value The value of the form data to set.
	 * @return $this The current Request instance.
	 * @example <pre><code class="language-php">$request = Artillery::request('post', '/submit')
	 *     ->setForm('name', 'Swanson')
	 *     ->setForm('vote', 'Hamburger');
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#url-encoded-forms-applicationx-www-form-urlencoded
	 */
	public function setForm(string $key, mixed $value): self {
		if (!@$this->request['form']) $this->request['form'] = [];
		$this->request['form'][$key] = $value;
		return $this;
	}

	/**
	 * Set an array of URL-encoded form attributes (application/x-www-form-urlencoded).
	 * @param array $form An array of form data to set. As [key => value, ...].
	 * @return $this The current Request instance.
	 * @example <pre><code class="language-php">$request = Artillery::request('post', '/submit')
	 *     ->setForms(['name' => 'Swanson', 'vote' => 'Hamburger']);
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#url-encoded-forms-applicationx-www-form-urlencoded
	 */
	public function setForms(array $form): self {
		foreach ($form as $key => $value) $this->setForm($key, $value);
		return $this;
	}

	/**
	 * Set an array of Multipart/form-data form attributes (forms containing files, non-ASCII data, and binary data).
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#multipart-forms-multipartform-data
	 * @param array $formData An array of form data to set.
	 * @return $this The current Request instance.
	 */
	public function setFormDatas(array $formData): self {
		foreach ($formData as $key => $value) $this->setFormData($key, $value);
		return $this;
	}

	/**
	 * Set a Multipart/form-data form attribute (forms containing files, non-ASCII data, and binary data).
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#multipart-forms-multipartform-data
	 * @param string $key The key of the form data to set.
	 * @param mixed $value The value of the form data to set.
	 * @return $this The current Request instance.
	 */
	public function setFormData(string $key, mixed $value): self {
		if (!@$this->request['formData']) $this->request['formData'] = [];
		$this->request['formData'][$key] = $value;
		return $this;
	}

	/**
	 * Set gzip to true for Artillery to add an Accept-Encoding header to the request, and decode compressed responses encoded with gzip.
	 * https://www.artillery.io/docs/guides/guides/http-reference#compressed-responses-gzip
	 * @param bool $gzip Whether to use gzip to decode/decompress the response.
	 * @return $this The current Request instance.
	 */
	public function setGzip(bool $gzip = true): self {
		$this->request['gzip'] = $gzip;
		return $this;
	}

	/**
	 * Arbitrary header may be sent under the 'headers' option for a request.
	 * @param string $key The name of the header to set.
	 * @param string $value The value of the header to set.
	 * @return $this The current Request instance.
	 * @example <pre><code class="language-php">$request = Artillery::request()->setHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 12; ...');</code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#setting-headers
	 */
	public function setHeader(string $key, string $value): self {
		if (!@$this->request['headers']) $this->request['headers'] = [];
		$this->request['headers'][$key] = $value;
		return $this;
	}

	/**
	 * Associative array of [name => value, ...] arbitrary headers may be sent under the 'headers' option for a request.
	 * @param array<string, string> $headers A key-value array of headers to set.
	 * @return $this The current Request instance.
	 * @example <pre><code class="language-php">$request = Artillery::request()->setHeaders(['User-Agent' => 'Mozilla/5.0 (Linux; Android 12; ...']);</code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#setting-headers
	 */
	public function setHeaders(array $headers): self {
		foreach ($headers as $key => $value) $this->setHeader($key, $value);
		return $this;
	}

	/**
	 * The ifTrue option can execute a request in a flow only when meeting a condition.
	 * @param string $expression The expression to evaluate.
	 * @return $this The current Request instance.
	 * @example <pre><code class="language-php">$request = Artillery::request('get', '/pages/{{ pageNumber }}')
	 *     ->setIfTrue('pageNumber < 10');
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#conditional-requests
	 */
	public function setIfTrue(string $expression): self {
		$this->request['ifTrue'] = $expression;
		return $this;
	}

	/**
	 * Set a JSON field for the request. If called without parameters, it will initialize an empty JSON object '{  }'.
	 * @example <pre><code class="language-php">$request = Artillery::request('post', '/submit')
	 *     ->setJson('name', 'Swanson')
	 *     ->setJson('vote', 'Hamburger');
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#get--post--put--patch--delete-requests
	 * @param string|null $key The name of the JSON field to set.
	 * @param mixed $value The value of the JSON field to set.
	 * @return $this The current Request instance.
	 */
	public function setJson(string $key = null, mixed $value = null): self {
		if (empty((array)@$this->request['json'])) $this->request['json'] = [];
		if ($key) $this->request['json'][$key] = $value;
		if (empty((array)@$this->request['json'])) $this->request['json'] = new stdClass();
		return $this;
	}

	/**
	 * Set an array of JSON fields for the request.
	 * @example <pre><code class="language-php">$request = Artillery::request('post', '/submit')
	 *     ->setJsons([
	 *         'name' => 'Swanson',
	 *         'vote' => 'Hamburger']);
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#get--post--put--patch--delete-requests
	 * @param array<string, mixed> $jsons An array of data to be stringified as JSON data for the request.
	 * @return $this The current Request instance.
	 */
	public function setJsons(array $jsons): self {
		foreach ($jsons as $key => $value) $this->setJson($key, $value);
		return $this;
	}

	/**
	 * Set the HTTP method to use for the request, e.g 'get', 'post', 'put', 'patch', 'delete'.
	 * @param 'get'|'post'|'put'|'patch'|'delete' $method The HTTP method to use for the request.
	 * @return $this The current Request instance.
	 */
	public function setMethod(string $method): self {
		$this->method = $method;
		return $this;
	}

	/**
	 * Set a query string for the request. This is equivalent to url?key=value.
	 * @example <pre><code class="language-php">$request->setQueryString('page', 1);</code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#query-strings
	 * @param string $key The key of the query string.
	 * @param mixed $value The value of the query string.
	 * @return $this The current Request instance.
	 */
	public function setQueryString(string $key, mixed $value): self {
		if (!@$this->request['qs']) $this->request['qs'] = [];
		$this->request['qs'][$key] = $value;
		return $this;
	}

	/**
	 * Set an array of query strings for the request. ['page' => 1, 'limit' => 10] is equivalent to url?page=1&limit=10.
	 * @example <pre><code class="language-php">$request->setQueryStrings(['page' => 1, 'limit' => 10]);</code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/http-reference#query-strings
	 * @param array{string, string} $qs An array of query string parameters to set as ['param' => value, ..].
	 * @return $this The current Request instance.
	 */
	public function setQueryStrings(array $qs): self {
		foreach ($qs as $key => $value) $this->setQueryString($key, $value);
		return $this;
	}

	/**
	 * Set the URL to send the request to, it can be fully qualified or relative to the Artillery script target URL.
	 * @param string $url The URL to send the request to.
	 * @return $this The current Request instance.
	 */
	public function setUrl(string $url): self {
		$this->request['url'] = $url;
		return $this;
	}
}