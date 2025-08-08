<?php

use OPGG\LaravelMcpServer\Services\SwaggerParser\SwaggerParser;
use OPGG\LaravelMcpServer\Services\SwaggerParser\SwaggerToMcpConverter;

beforeEach(function () {
    $parser = new SwaggerParser;
    $this->converter = new SwaggerToMcpConverter($parser);
});

test('converts paths to class names correctly', function ($path, $method, $operationId, $expected) {
    $endpoint = [
        'path' => $path,
        'method' => $method,
        'operationId' => $operationId,
    ];
    
    $className = $this->converter->generateClassName($endpoint);
    expect($className)->toBe($expected);
})->with([
    // OP.GG style paths (no operationId)
    ['/lol/{region}/server-stats', 'GET', null, 'GetLolRegionServerStatsTool'],
    ['/lol/{region}/champions/{championId}', 'GET', null, 'GetLolRegionChampionsChampionIdTool'],
    ['/valorant/{region}/players/{playerId}/matches', 'GET', null, 'GetValorantRegionPlayersPlayerIdMatchesTool'],
    
    // With hash operationId (should use path-based naming)
    ['/lol/{region}/server-stats', 'GET', '5784a7dfd226e1621b0e6ee8c4f39407', 'GetLolRegionServerStatsTool'],
    ['/api/users', 'POST', 'df2eafc7cbf65a9ad14aceecdef3dbd3', 'PostApiUsersTool'],
    
    // With proper operationId (should use operationId)
    ['/users', 'GET', 'getUsers', 'GetUsersTool'],
    ['/login', 'POST', 'userLogin', 'UserLoginTool'],
    
    // Kebab-case paths (no operationId)
    ['/user-profiles/{id}/match-history', 'POST', null, 'PostUserProfilesIdMatchHistoryTool'],
    ['/api/v2/game-stats', 'GET', null, 'GetApiV2GameStatsTool'],
    
    // Snake_case paths
    ['/user_profiles/{user_id}/game_stats', 'PUT', null, 'PutUserProfilesUserIdGameStatsTool'],
    
    // CamelCase paths
    ['/userProfiles/{userId}/gameStats', 'DELETE', null, 'DeleteUserProfilesUserIdGameStatsTool'],
    
    // Mixed cases
    ['/api/v1/user-profiles/{user_id}/gameStats', 'PATCH', null, 'PatchApiV1UserProfilesUserIdGameStatsTool'],
    
    // Simple paths
    ['/users', 'GET', null, 'GetUsersTool'],
    ['/login', 'POST', null, 'PostLoginTool'],
    
    // Nested resources
    ['/teams/{teamId}/players/{playerId}/stats', 'GET', null, 'GetTeamsTeamIdPlayersPlayerIdStatsTool'],
    
    // Paths with numbers
    ['/api/v3/stats', 'GET', null, 'GetApiV3StatsTool'],
    ['/2024/tournaments', 'GET', null, 'Get2024TournamentsTool'],
]);

test('converts paths to tool names correctly', function ($path, $method, $expected) {
    $endpoint = [
        'path' => $path,
        'method' => $method,
        'operationId' => null,
        'summary' => '',
        'description' => '',
        'tags' => [],
        'deprecated' => false,
        'parameters' => [],
        'requestBody' => null,
        'responses' => [],
        'security' => [],
    ];
    
    $toolParams = $this->converter->convertEndpointToTool($endpoint, 'TestTool');
    expect($toolParams['toolName'])->toBe($expected);
})->with([
    // OP.GG style paths
    ['/lol/{region}/server-stats', 'GET', 'get-lol-region-server-stats'],
    ['/lol/{region}/champions/{championId}', 'GET', 'get-lol-region-champions-champion-id'],
    
    // Kebab-case paths
    ['/user-profiles/{id}/match-history', 'POST', 'post-user-profiles-id-match-history'],
    
    // Simple paths
    ['/users', 'GET', 'get-users'],
    ['/login', 'POST', 'post-login'],
]);

test('detects and ignores hash operationIds', function () {
    // Test with 32-char hex hash (MD5-like)
    $endpoint = [
        'path' => '/api/users/{id}',
        'method' => 'GET',
        'operationId' => '5784a7dfd226e1621b0e6ee8c4f39407',
    ];
    
    $className = $this->converter->generateClassName($endpoint);
    expect($className)->toBe('GetApiUsersIdTool');
    
    // Test with proper operationId
    $endpoint2 = [
        'path' => '/api/users/{id}',
        'method' => 'GET',
        'operationId' => 'getUserById',
    ];
    
    $className2 = $this->converter->generateClassName($endpoint2);
    expect($className2)->toBe('GetUserByIdTool');
    
    // Test with uppercase hash
    $endpoint3 = [
        'path' => '/api/orders',
        'method' => 'POST',
        'operationId' => 'DF2EAFC7CBF65A9AD14ACEECDEF3DBD3',
    ];
    
    $className3 = $this->converter->generateClassName($endpoint3);
    expect($className3)->toBe('PostApiOrdersTool');
});