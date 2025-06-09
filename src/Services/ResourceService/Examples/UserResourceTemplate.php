<?php

namespace OPGG\LaravelMcpServer\Services\ResourceService\Examples;

use OPGG\LaravelMcpServer\Services\ResourceService\ResourceTemplate;

/**
 * Example ResourceTemplate that demonstrates how to create dynamic user resources.
 * This solves the problem described in GitHub discussion #32.
 *
 * Usage:
 * - Template URI: "database://users/{id}"
 * - Client can request: "database://users/123" to get user with ID 123
 * - The read() method will be called with ['id' => '123'] as parameters
 */
class UserResourceTemplate extends ResourceTemplate
{
    public string $uriTemplate = 'database://users/{id}';

    public string $name = 'User by ID';

    public ?string $description = 'Access individual user details by user ID';

    public ?string $mimeType = 'application/json';

    /**
     * List all available user resources.
     *
     * This method returns a list of concrete user resources that can be accessed
     * through this template. In a real implementation, you would query your database
     * to get all available users.
     *
     * @return array Array of user resource definitions
     */
    public function list(): ?array
    {
        // In a real implementation, you would query your database:
        // $users = User::select(['id', 'name'])->get();
        //
        // For this example, we'll return mock data:
        $users = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
            ['id' => 3, 'name' => 'Charlie'],
        ];

        $resources = [];
        foreach ($users as $user) {
            $resources[] = [
                'uri' => "database://users/{$user['id']}",
                'name' => "User: {$user['name']}",
                'description' => "Profile data for user {$user['name']} (ID: {$user['id']})",
                'mimeType' => $this->mimeType,
            ];
        }

        return $resources;
    }

    /**
     * Read user data for the specified user ID.
     *
     * In a real implementation, this would:
     * 1. Extract the user ID from the parameters
     * 2. Query the database to fetch user details
     * 3. Return the user data as JSON
     *
     * @param  string  $uri  The full URI being requested (e.g., "database://users/123")
     * @param  array  $params  Extracted parameters (e.g., ['id' => '123'])
     * @return array Resource content with uri, mimeType, and text/blob
     */
    public function read(string $uri, array $params): array
    {
        $userId = $params['id'] ?? null;

        if ($userId === null) {
            return [
                'uri' => $uri,
                'mimeType' => 'application/json',
                'text' => json_encode(['error' => 'Missing user ID'], JSON_PRETTY_PRINT),
            ];
        }

        // In a real implementation, you would query your database here:
        // $user = User::find($userId);
        //
        // For this example, we'll return mock data:
        $userData = [
            'id' => (int) $userId,
            'name' => "User {$userId}",
            'email' => "user{$userId}@example.com",
            'created_at' => '2024-01-01T00:00:00Z',
            'profile' => [
                'bio' => "This is the bio for user {$userId}",
                'location' => 'Example City',
            ],
        ];

        return [
            'uri' => $uri,
            'mimeType' => $this->mimeType,
            'text' => json_encode($userData, JSON_PRETTY_PRINT),
        ];
    }
}
