<?php

namespace OPGG\LaravelMcpServer\Services\ResourceService;

class Resource
{
    protected string $uri;

    protected string $name;

    protected ?string $description;

    protected ?string $mimeType;

    /** @var callable|null */
    protected $reader;

    /**
     * @param  callable():array|null  $reader  Returns content array ['text'=>string] or ['blob'=>string]
     */
    public function __construct(string $uri, string $name, ?string $description = null, ?string $mimeType = null, ?callable $reader = null)
    {
        $this->uri = $uri;
        $this->name = $name;
        $this->description = $description;
        $this->mimeType = $mimeType;
        $this->reader = $reader;
    }

    public function metadata(): array
    {
        $data = [
            'uri' => $this->uri,
            'name' => $this->name,
        ];
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        if ($this->mimeType !== null) {
            $data['mimeType'] = $this->mimeType;
        }

        return $data;
    }

    public function read(): array
    {
        $content = [];
        if ($this->reader) {
            $content = call_user_func($this->reader);
        }

        return [
            'uri' => $this->uri,
            ...($this->mimeType ? ['mimeType' => $this->mimeType] : []),
            ...$content,
        ];
    }
}
