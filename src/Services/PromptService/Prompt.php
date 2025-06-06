<?php

namespace OPGG\LaravelMcpServer\Services\PromptService;

/**
 * Base Prompt class representing a reusable text snippet.
 */
abstract class Prompt
{
    /**
     * Unique identifier for this prompt.
     */
    public string $identifier;

    /**
     * Human readable name.
     */
    public string $name;

    /**
     * Optional description of the prompt.
     */
    public ?string $description = null;

    /**
     * The prompt text. Can include placeholder variables like {name}.
     */
    public string $text;

    /**
     * Attempt to match the given identifier against this prompt's identifier
     * template. If it matches, extracted variables are returned via the
     * provided array reference.
     */
    public function matches(string $identifier, array &$variables = []): bool
    {
        $regex = '/^'.preg_quote($this->identifier, '/').'$/';
        $regex = str_replace('\\{', '(?P<', $regex);
        $regex = str_replace('\\}', '>[^\/]+)', $regex);

        if (preg_match($regex, $identifier, $matches)) {
            $variables = array_merge($variables, array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));

            return true;
        }

        return false;
    }

    public function toArray(): array
    {
        return array_filter([
            'identifier' => $this->identifier,
            'name' => $this->name,
            'description' => $this->description,
        ], static fn ($v) => $v !== null);
    }

    /**
     * Render the prompt text using provided variables.
     *
     * @param  array<string, string>  $variables
     */
    public function render(array $variables = []): array
    {
        $rendered = $this->text;
        foreach ($variables as $key => $value) {
            $rendered = str_replace('{'.$key.'}', $value, $rendered);
        }

        return [
            'identifier' => $this->expandIdentifier($variables),
            'text' => $rendered,
        ];
    }

    protected function expandIdentifier(array $variables): string
    {
        $id = $this->identifier;
        foreach ($variables as $key => $value) {
            $id = str_replace('{'.$key.'}', $value, $id);
        }

        return $id;
    }
}
