<?php

namespace ArtilleryPhp;

/**
 * Anonymous Request type. Crude implementation.
 * @example <pre><code class="language-php">$scenario = Artillery::scenario()
 *     ->setEngine('custom')
 *     ->addRequest(Artillery::anyRequest('send', 'Hello World!'));
 * </code></pre>
 * @link https://www.artillery.io/docs/guides/guides/ws-reference
 */
class AnyRequest extends RequestBase { }