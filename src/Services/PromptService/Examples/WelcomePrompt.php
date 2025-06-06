<?php

namespace OPGG\LaravelMcpServer\Services\PromptService\Examples;

use OPGG\LaravelMcpServer\Services\PromptService\Prompt;

class WelcomePrompt extends Prompt
{
    public string $identifier = 'prompt://welcome';

    public string $name = 'Welcome Prompt';

    public string $text = 'Welcome, user!';
}
