<?php

namespace OPGG\LaravelMcpServer\Services\PromptService;

use Illuminate\Container\Container;
use InvalidArgumentException;

class PromptRepository
{
    /** @var array<string, Prompt> */
    protected array $prompts = [];

    protected Container $container;

    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? Container::getInstance();
    }

    /**
     * @param  Prompt[]  $prompts
     */
    public function registerPrompts(array $prompts): self
    {
        foreach ($prompts as $prompt) {
            $this->registerPrompt($prompt);
        }

        return $this;
    }

    public function registerPrompt(Prompt|string $prompt): self
    {
        if (is_string($prompt)) {
            $prompt = $this->container->make($prompt);
        }

        if (! $prompt instanceof Prompt) {
            throw new InvalidArgumentException('Prompt must extend '.Prompt::class);
        }

        $this->prompts[$prompt->identifier] = $prompt;

        return $this;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPromptSchemas(): array
    {
        return array_values(array_map(fn (Prompt $p) => $p->toArray(), $this->prompts));
    }

    public function render(string $identifier, array $vars = []): ?array
    {
        if (isset($this->prompts[$identifier])) {
            return $this->prompts[$identifier]->render($vars);
        }

        foreach ($this->prompts as $prompt) {
            if ($prompt->matches($identifier, $vars)) {
                return $prompt->render($vars);
            }
        }

        return null;
    }
}
