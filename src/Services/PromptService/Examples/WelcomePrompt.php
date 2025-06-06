<?php

namespace OPGG\LaravelMcpServer\Services\PromptService\Examples;

use OPGG\LaravelMcpServer\Services\PromptService\Prompt;

class WelcomePrompt extends Prompt
{
    public string $name = 'welcome-user';

    public ?string $description = 'A customizable welcome message for users';

    public array $arguments = [
        [
            'name' => 'username',
            'description' => 'The name of the user to welcome',
            'required' => true,
        ],
        [
            'name' => 'role',
            'description' => 'The role of the user (optional)',
            'required' => false,
        ],
    ];

    public string $text = 'Welcome, {username}! You are logged in as {role}.';
}
