<?php

namespace OPGG\LaravelMcpServer\Utils;

use Illuminate\Http\Request;

final class RequestQueryParameterUtil
{
    /**
     * @return array<int|string, mixed>
     */
    public static function all(Request $request): array
    {
        $queryParameters = $request->query->all();

        $queryString = $request->server('QUERY_STRING');
        if (! is_string($queryString) || trim($queryString) === '') {
            return $queryParameters;
        }

        $valuesByKey = [];
        foreach (explode('&', $queryString) as $segment) {
            if ($segment === '') {
                continue;
            }

            $parts = explode('=', $segment, 2);
            $key = urldecode($parts[0]);
            if ($key === '' || str_ends_with($key, '[]') || str_contains($key, '[')) {
                continue;
            }

            $value = urldecode($parts[1] ?? '');
            $valuesByKey[$key][] = $value;
        }

        foreach ($valuesByKey as $key => $values) {
            if (count($values) <= 1) {
                continue;
            }

            $queryParameters[$key] = $values;
        }

        return $queryParameters;
    }
}
