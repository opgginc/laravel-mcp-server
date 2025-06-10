<?php

namespace OPGG\LaravelMcpServer\Services\ResourceService\Examples;

use OPGG\LaravelMcpServer\Services\ResourceService\Resource;

/**
 * Example static Resource that provides a list of all users.
 * This complements the UserResourceTemplate to show both static and dynamic resources.
 *
 * Usage:
 * - Static URI: "database://users"
 * - Returns a list of all users
 * - Users can then use the UserResourceTemplate to get individual user details
 */
class UserListResource extends Resource
{
    public string $uri = 'database://users';

    public string $name = 'Users List';

    public ?string $description = 'List of all users in the database';

    public ?string $mimeType = 'application/json';

    /**
     * Read and return the list of all users.
     *
     * In a real implementation, this would query the database
     * to get all users and return a summary list.
     */
    public function read(): array
    {
        // In a real implementation, you would query your database:
        // $users = User::select(['id', 'name', 'email'])->get();
        //
        // For this example, we'll return mock data:
        $users = [
            ['id' => 1, 'name' => 'User 1', 'email' => 'user1@example.com'],
            ['id' => 2, 'name' => 'User 2', 'email' => 'user2@example.com'],
            ['id' => 3, 'name' => 'User 3', 'email' => 'user3@example.com'],
        ];

        $response = [
            'total' => count($users),
            'users' => $users,
            'template' => 'database://users/{id}',
            'description' => 'Use the template URI to get individual user details',
        ];

        return [
            'uri' => $this->uri,
            'mimeType' => $this->mimeType,
            'text' => json_encode($response, JSON_PRETTY_PRINT),
        ];
    }
}
