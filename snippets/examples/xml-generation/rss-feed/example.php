<?php

// @cdata ... @end wraps its rendered body in a CDATA section. A "]]>" inside the body
// would end the section early, so it is split across two sections.
$tsuku->registerDirective('cdata', static fn (string $body): string =>
    '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $body) . ']]>');

echo $tsuku->process($template, $data);
