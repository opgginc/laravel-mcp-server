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
    public string $name;

    /**
     * Optional description of the prompt.
     */
    public ?string $description = null;

    /**
     * Arguments that can be used in the prompt.
     *
     * @var array<int, array{name: string, description?: string, required?: bool}>
     */
    public array $arguments = [];

    /**
     * The prompt text. Can include placeholder variables like {name}.
     */
    public string $text;

    /**
     * Attempt to match the given identifier against this prompt's name
     * template. If it matches, extracted variables are returned via the
     * provided array reference.
     */
    public function matches(string $identifier, array &$variables = []): bool
    {
        $regex = '/^'.preg_quote($this->name, '/').'$/';
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
        $data = [
            'name' => $this->name,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if (! empty($this->arguments)) {
            $data['arguments'] = $this->arguments;
        }

        return $data;
    }

    /**
     * Render the prompt text using provided arguments.
     *
     * @param  array<string, string>  $arguments
     */
    public function render(array $arguments = []): array
    {
        $this->validateArguments($arguments);

        $rendered = $this->text;
        foreach ($arguments as $key => $value) {
            $rendered = str_replace('{'.$key.'}', $value, $rendered);
        }

        $response = [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        'type' => 'text',
                        'text' => $rendered,
                    ],
                ],
            ],
        ];

        if ($this->description !== null) {
            $response['description'] = $this->description;
        }

        return $response;
    }

    /**
     * Validate that all required arguments are provided.
     *
     * @param  array<string, string>  $providedArguments
     *
     * @throws \InvalidArgumentException
     */
    protected function validateArguments(array $providedArguments): void
    {
        foreach ($this->arguments as $argument) {
            $argName = $argument['name'];
            $isRequired = $argument['required'] ?? false;

            if ($isRequired && (! isset($providedArguments[$argName]) || trim($providedArguments[$argName]) === '')) {
                throw new \InvalidArgumentException("Required argument '{$argName}' is missing or empty");
            }
        }
    }
}
