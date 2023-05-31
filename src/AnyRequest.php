<?php

namespace ArtilleryPhp;

/**
 * Anonymous Request type. Can be used for not-yet-supported or custom engines. Crude implementation.
 * @example <pre><code class="language-php">$emitAndValidateResponse = Artillery::scenario('Emit and validate response')
 *     ->setEngine('socketio')
 *     ->addRequest(
 *         Artillery::anyRequest('emit')
 *             ->set('channel', 'echo')
 *             ->set('data', 'Hello from Artillery')
 *             ->set('response', ['channel' => 'echoResponse', 'data' => 'Hello from Artillery']));
 * </code></pre>
 * @link https://www.artillery.io/docs/guides/guides/ws-reference
 */
class AnyRequest extends RequestBase { }