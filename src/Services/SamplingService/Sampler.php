<?php

namespace OPGG\LaravelMcpServer\Services\SamplingService;

/**
 * Base class describing a sampling request.
 */
abstract class Sampler
{
    /**
     * Conversation messages for the sampling request.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $messages = [];

    /**
     * Optional model preference hints.
     *
     * @var array<string, mixed>|null
     */
    public ?array $modelPreferences = null;

    /**
     * Optional system prompt sent to the model.
     */
    public ?string $systemPrompt = null;

    /**
     * Optional maximum tokens parameter.
     */
    public ?int $maxTokens = null;

    /**
     * Convert the sampler to an array for the sampling/createMessage request.
     */
    public function toArray(): array
    {
        $data = ['messages' => $this->messages];

        if ($this->modelPreferences !== null) {
            $data['modelPreferences'] = $this->modelPreferences;
        }
        if ($this->systemPrompt !== null) {
            $data['systemPrompt'] = $this->systemPrompt;
        }
        if ($this->maxTokens !== null) {
            $data['maxTokens'] = $this->maxTokens;
        }

        return $data;
    }
}
