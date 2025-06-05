<?php

namespace OPGG\LaravelMcpServer\Services\ResourceService;

class ResourceTemplate
{
    public function __construct(
        public string $uriTemplate,
        public string $name,
        public ?string $description = null,
        public ?string $mimeType = null,
    ) {}

    public function toArray(): array
    {
        $data = [
            'uriTemplate' => $this->uriTemplate,
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
}
