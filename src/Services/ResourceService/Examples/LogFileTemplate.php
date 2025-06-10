<?php

namespace OPGG\LaravelMcpServer\Services\ResourceService\Examples;

use OPGG\LaravelMcpServer\Services\ResourceService\ResourceTemplate;

class LogFileTemplate extends ResourceTemplate
{
    public string $uriTemplate = 'file:///logs/{date}.log';

    public string $name = 'Log file by date';

    public ?string $description = 'Access log file for the given date';

    public ?string $mimeType = 'text/plain';

    /**
     * Read log file content for the specified date.
     *
     * @param  string  $uri  The full URI being requested (e.g., "file:///logs/2024-01-01.log")
     * @param  array  $params  Extracted parameters (e.g., ['date' => '2024-01-01'])
     * @return array Resource content with uri, mimeType, and text
     */
    public function read(string $uri, array $params): array
    {
        $date = $params['date'] ?? 'unknown';

        // In a real implementation, you would read the actual log file
        // For this example, we'll return mock log data
        $logContent = "Log entries for {$date}\n";
        $logContent .= "[{$date} 10:00:00] INFO: Application started\n";
        $logContent .= "[{$date} 10:05:00] INFO: Processing requests\n";
        $logContent .= "[{$date} 10:10:00] WARNING: High memory usage detected\n";
        $logContent .= "[{$date} 10:15:00] INFO: Memory usage normalized\n";

        return [
            'uri' => $uri,
            'mimeType' => $this->mimeType,
            'text' => $logContent,
        ];
    }
}
