<?php

namespace ArtilleryPhp;

/**
 * WebSocket Request. Crude implementation.
 * @example <pre><code class="language-php">$scenario = Artillery::scenario()
 *     ->setEngine('ws')
 *     ->addRequest(Artillery::wsRequest('send', 'Hello World!'));
 * </code></pre>
 * @link https://www.artillery.io/docs/guides/guides/ws-reference
 */
class WsRequest extends RequestBase implements RequestInterface {

	/**
	 * WebSocket Request constructor.
	 * @param 'connect'|'send'|null $method The WebSocket command for this request.
	 * @param mixed $request The data to accompany the request.
	 * @example <pre><code class="language-php">$scenario = Artillery::scenario()
	 *     ->setEngine('ws')
	 *     ->addRequest(Artillery::wsRequest('send', 'Hello World!'));
	 * </code></pre>
	 * @link https://www.artillery.io/docs/guides/guides/ws-reference
	 */
	public function __construct(string $method = null, mixed $request = null) {
		if (!$method) $this->method = $method;
		if ($request !== null) $this->request = $request;
	}

	/**
	 * Todo: Investigate a bit https://github.com/artilleryio/artillery/issues/800
	 * @link https://github.com/artilleryio/artillery/issues/800
	 * @param mixed $payload Data to accompany the request.
	 * @return $this The current Request instance.
	 */
	public function setPayload(mixed $payload): self {
		$this->request['payload'] = $payload;
		return $this;
	}
}