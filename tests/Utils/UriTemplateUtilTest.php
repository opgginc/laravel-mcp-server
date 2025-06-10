<?php

namespace Tests\Utils;

use OPGG\LaravelMcpServer\Utils\UriTemplateUtil;
use PHPUnit\Framework\TestCase;

class UriTemplateUtilTest extends TestCase
{
    public function test_extract_parameters_returns_empty_array_for_no_parameters(): void
    {
        $template = 'database://users';
        $result = UriTemplateUtil::extractParameters($template);

        $this->assertEmpty($result);
    }

    public function test_extract_parameters_returns_single_parameter(): void
    {
        $template = 'database://users/{id}';
        $result = UriTemplateUtil::extractParameters($template);

        $this->assertEquals(['id'], $result);
    }

    public function test_extract_parameters_returns_multiple_parameters(): void
    {
        $template = 'database://users/{userId}/posts/{postId}';
        $result = UriTemplateUtil::extractParameters($template);

        $this->assertEquals(['userId', 'postId'], $result);
    }

    public function test_match_uri_returns_null_for_no_match(): void
    {
        $template = 'database://users/{id}';
        $uri = 'database://posts/123';

        $result = UriTemplateUtil::matchUri($template, $uri);

        $this->assertNull($result);
    }

    public function test_match_uri_returns_parameters_for_match(): void
    {
        $template = 'database://users/{id}';
        $uri = 'database://users/123';

        $result = UriTemplateUtil::matchUri($template, $uri);

        $this->assertEquals(['id' => '123'], $result);
    }

    public function test_match_uri_handles_multiple_parameters(): void
    {
        $template = 'database://users/{userId}/posts/{postId}';
        $uri = 'database://users/123/posts/456';

        $result = UriTemplateUtil::matchUri($template, $uri);

        $this->assertEquals(['userId' => '123', 'postId' => '456'], $result);
    }

    public function test_match_uri_handles_special_characters_in_base_uri(): void
    {
        $template = 'file:///logs/{date}.log';
        $uri = 'file:///logs/2024-01-01.log';

        $result = UriTemplateUtil::matchUri($template, $uri);

        $this->assertEquals(['date' => '2024-01-01'], $result);
    }

    public function test_expand_template_with_single_parameter(): void
    {
        $template = 'database://users/{id}';
        $params = ['id' => '123'];

        $result = UriTemplateUtil::expandTemplate($template, $params);

        $this->assertEquals('database://users/123', $result);
    }

    public function test_expand_template_with_multiple_parameters(): void
    {
        $template = 'database://users/{userId}/posts/{postId}';
        $params = ['userId' => '123', 'postId' => '456'];

        $result = UriTemplateUtil::expandTemplate($template, $params);

        $this->assertEquals('database://users/123/posts/456', $result);
    }

    public function test_is_valid_template_returns_true_for_valid_template(): void
    {
        $template = 'database://users/{id}';

        $result = UriTemplateUtil::isValidTemplate($template);

        $this->assertTrue($result);
    }

    public function test_is_valid_template_returns_true_for_no_parameters(): void
    {
        $template = 'database://users';

        $result = UriTemplateUtil::isValidTemplate($template);

        $this->assertTrue($result);
    }

    public function test_is_valid_template_returns_false_for_unbalanced_braces(): void
    {
        $template = 'database://users/{id';

        $result = UriTemplateUtil::isValidTemplate($template);

        $this->assertFalse($result);
    }

    public function test_match_uri_with_complex_file_path(): void
    {
        $template = 'file:///logs/{year}/{month}/{day}.log';
        $uri = 'file:///logs/2024/01/15.log';

        $result = UriTemplateUtil::matchUri($template, $uri);

        $this->assertEquals([
            'year' => '2024',
            'month' => '01',
            'day' => '15',
        ], $result);
    }
}
