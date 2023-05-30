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
class WsRequest extends RequestBase {

	/** @inheritDoc */
	public function addCapture(string $as, string $type, string $expression, bool $strict = true, string $attr = null, int|string $index = null): self {
		// compatability with ws request (capture)
		// https://github.com/artilleryio/artillery/pull/917
		if ($this->request && !@$this->request['payload']) $this->request['payload'] = $this->request;
        return parent::addCapture($as, $type, $expression, $strict, $attr, $index);
	}

	/** @inheritDoc */
	public function addExpect(string $type, mixed $value, mixed $equals = null): self {
		if ($this->request && !@$this->request['payload']) $this->request['payload'] = $this->request;
		return parent::addExpect($type, $value, $equals);
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