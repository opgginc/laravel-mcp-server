<?php

use OPGG\LaravelMcpServer\Services\ToolService\Concerns\FormatsTabularToolResponses;
use OPGG\LaravelMcpServer\Services\ToolService\ToolResponse;

test('csv response creates text/csv payload', function () {
    $helper = new class
    {
        use FormatsTabularToolResponses;

        public function csv(array $rows, ?array $columns = null): ToolResponse
        {
            return $this->toolCsvResponse($rows, $columns);
        }
    };

    $response = $helper->csv([
        ['id' => 1, 'name' => 'Annie'],
        ['id' => 2, 'name' => 'Olaf'],
    ]);

    expect($response)->toBeInstanceOf(ToolResponse::class);
    $payload = $response->toArray();
    expect($payload['content'][0]['type'])->toBe('text/csv');
    expect($payload['content'][0]['text'])
        ->toContain('id,name')
        ->toContain('Annie');
});

test('markdown response renders markdown table', function () {
    $helper = new class
    {
        use FormatsTabularToolResponses;

        public function markdown(array $rows): string
        {
            return $this->toMarkdownTable($rows);
        }
    };

    $markdown = $helper->markdown([
        ['id' => 1, 'name' => 'Annie'],
    ]);

    expect($markdown)->toStartWith('| id | name |');
    expect($markdown)->toContain('| 1 | Annie |');
});

test('throws when encountering nested arrays', function () {
    $helper = new class
    {
        use FormatsTabularToolResponses;

        public function csv(array $rows): ToolResponse
        {
            return $this->toolCsvResponse($rows);
        }
    };

    $helper->csv([
        ['id' => 1, 'name' => ['nested' => 'value']],
    ]);
})->throws(InvalidArgumentException::class);
