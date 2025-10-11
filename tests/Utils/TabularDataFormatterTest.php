<?php

use OPGG\LaravelMcpServer\Utils\TabularDataFormatter;

test('tabular data can be converted to csv and markdown', function () {
    $rows = [
        ['name' => 'Ahri', 'role' => 'Mage', 'release' => 2011],
        ['name' => 'Xin Zhao', 'role' => 'Fighter', 'release' => 2010],
    ];

    $headers = TabularDataFormatter::resolveHeaders($rows);
    $normalized = TabularDataFormatter::normalizeRows($rows, $headers);

    $csv = TabularDataFormatter::toCsv($normalized, $headers);
    expect($csv)->toBe("name,role,release\nAhri,Mage,2011\n\"Xin Zhao\",Fighter,2010\n");

    $markdown = TabularDataFormatter::toMarkdown($normalized, $headers);
    expect($markdown)->toBe("| name | role | release |\n| --- | --- | --- |\n| Ahri | Mage | 2011 |\n| Xin Zhao | Fighter | 2010 |");
});

test('trait metadata key is exposed through formatter constant', function () {
    $trait = new class
    {
        use OPGG\LaravelMcpServer\Services\ToolService\Concerns\ProvidesTabularResponses;

        public function meta(array $rows): array
        {
            return $this->tabularMeta($rows);
        }
    };

    $meta = $trait->meta([
        ['name' => 'Ahri'],
    ]);

    expect($meta)->toHaveKey(TabularDataFormatter::META_KEY);
    expect($meta[TabularDataFormatter::META_KEY]['delimiter'])->toBe(',');
    expect($meta[TabularDataFormatter::META_KEY]['include_markdown'])->toBeTrue();
});
