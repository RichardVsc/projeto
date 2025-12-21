<?php

declare(strict_types=1);

namespace HyperfTest\Integration;

use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;

/**
 * Wrapper for HTTP responses in tests.
 * Provides fluent assertion methods.
 */
class TestResponse
{
    private ?ResponseInterface $response;

    private ?array $decodedJson = null;

    private int $statusCode;

    public function __construct(?ResponseInterface $response = null, int $statusCode = 200, ?array $json = null)
    {
        $this->response = $response;
        $this->statusCode = $response ? $response->getStatusCode() : $statusCode;
        $this->decodedJson = $json;
    }

    /**
     * Create a TestResponse from an array (without PSR-7 response).
     */
    public static function fromArray(array $data, int $statusCode = 200): self
    {
        return new self(null, $statusCode, $data);
    }

    /**
     * Get the underlying PSR-7 response.
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get the response status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the response body as string.
     */
    public function getContent(): string
    {
        if ($this->response) {
            return (string) $this->response->getBody();
        }

        return json_encode($this->decodedJson ?? []);
    }

    /**
     * Get the response body as decoded JSON array.
     */
    public function json(?string $key = null): mixed
    {
        if ($this->decodedJson === null && $this->response) {
            $this->decodedJson = json_decode($this->getContent(), true) ?? [];
        }

        $data = $this->decodedJson ?? [];

        if ($key === null) {
            return $data;
        }

        return data_get($data, $key);
    }

    /**
     * Assert that the response has a given status code.
     */
    public function assertStatus(int $status): self
    {
        Assert::assertEquals(
            $status,
            $this->getStatusCode(),
            sprintf(
                'Expected status code [%d] but received [%d]. Response: %s',
                $status,
                $this->getStatusCode(),
                $this->getContent()
            )
        );

        return $this;
    }

    /**
     * Assert that the response status code is 200.
     */
    public function assertOk(): self
    {
        return $this->assertStatus(200);
    }

    /**
     * Assert that the response status code is 201.
     */
    public function assertCreated(): self
    {
        return $this->assertStatus(201);
    }

    /**
     * Assert that the response status code is 404.
     */
    public function assertNotFound(): self
    {
        return $this->assertStatus(404);
    }

    /**
     * Assert that the response status code is 403.
     */
    public function assertForbidden(): self
    {
        return $this->assertStatus(403);
    }

    /**
     * Assert that the response status code is 422.
     */
    public function assertUnprocessable(): self
    {
        return $this->assertStatus(422);
    }

    /**
     * Assert that the response status code is 400.
     */
    public function assertBadRequest(): self
    {
        return $this->assertStatus(400);
    }

    /**
     * Assert that the response contains the given JSON.
     */
    public function assertJson(array $data): self
    {
        $responseData = $this->json();

        foreach ($data as $key => $value) {
            Assert::assertArrayHasKey(
                $key,
                $responseData,
                sprintf('Response JSON does not contain key [%s]', $key)
            );

            Assert::assertEquals(
                $value,
                $responseData[$key],
                sprintf(
                    'Response JSON key [%s] does not match. Expected: %s, Got: %s',
                    $key,
                    json_encode($value),
                    json_encode($responseData[$key])
                )
            );
        }

        return $this;
    }

    /**
     * Assert that the response JSON has the given structure.
     */
    public function assertJsonStructure(array $structure, ?array $data = null): self
    {
        $data = $data ?? $this->json();

        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                Assert::assertArrayHasKey(
                    $key,
                    $data,
                    sprintf('Response JSON does not contain key [%s]', $key)
                );

                $this->assertJsonStructure($value, $data[$key]);
            } else {
                Assert::assertArrayHasKey(
                    $value,
                    $data,
                    sprintf('Response JSON does not contain key [%s]', $value)
                );
            }
        }

        return $this;
    }

    /**
     * Assert that the response JSON contains the given path with a value.
     */
    public function assertJsonPath(string $path, mixed $expected): self
    {
        $actual = $this->json($path);

        Assert::assertEquals(
            $expected,
            $actual,
            sprintf(
                'Response JSON path [%s] does not match. Expected: %s, Got: %s',
                $path,
                json_encode($expected),
                json_encode($actual)
            )
        );

        return $this;
    }

    /**
     * Assert that the response JSON has a key.
     */
    public function assertJsonHas(string $key): self
    {
        Assert::assertArrayHasKey(
            $key,
            $this->json(),
            sprintf('Response JSON does not contain key [%s]', $key)
        );

        return $this;
    }

    /**
     * Dump the response for debugging.
     */
    public function dump(): self
    {
        var_dump([
            'status' => $this->getStatusCode(),
            'body' => $this->json(),
        ]);

        return $this;
    }

    /**
     * Dump the response and die.
     */
    public function dd(): never
    {
        $this->dump();
        exit(1);
    }
}

/*
 * Helper function similar to Laravel's data_get.
 */
if (! function_exists('data_get')) {
    function data_get(mixed $target, array|string|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return $default;
            }
        }

        return $target;
    }
}
