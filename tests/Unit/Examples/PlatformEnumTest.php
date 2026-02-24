<?php

use OPGG\LaravelMcpServer\Services\ToolService\Examples\Enums\Platform;

test('platform enum exposes web and desktop values', function () {
    $values = array_map(
        static fn (Platform $platform): string => $platform->value,
        Platform::cases()
    );

    expect($values)->toBe(['web', 'desktop']);
});
