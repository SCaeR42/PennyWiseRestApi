<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private array $params = [];
    private ?array $user = null;

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $query,
        private readonly array $body,
        private readonly array $headers,
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = rtrim((string) parse_url($uri, PHP_URL_PATH), '/');
        if ($path === '') {
            $path = '/';
        }

        $query = $_GET ?? [];

        $rawBody = file_get_contents('php://input') ?: '';
        $body = [];
        if ($rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $body = $decoded;
            }
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['CONTENT-TYPE'] = $_SERVER['CONTENT_TYPE'];
        }

        return new self($method, $path, $query, $body, $headers);
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $name, ?string $default = null): ?string
    {
        return $this->params[$name] ?? $default;
    }

    public function paramInt(string $name): int
    {
        return (int) $this->param($name, '0');
    }

    public function query(string $name, mixed $default = null): mixed
    {
        return $this->query[$name] ?? $default;
    }

    public function page(): int
    {
        return Pagination::normalizePage((int) $this->query('page', 1));
    }

    public function perPage(): int
    {
        return Pagination::normalizePerPage((int) $this->query('per_page', 20));
    }

    public function input(string $name, mixed $default = null): mixed
    {
        return $this->body[$name] ?? $default;
    }

    public function all(): array
    {
        return $this->body;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtoupper($name)] ?? null;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('AUTHORIZATION');
        if ($header !== null && preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    public function setUser(array $claims): void
    {
        $this->user = $claims;
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function userId(): int
    {
        return (int) ($this->user['sub'] ?? 0);
    }
}
