<?php

declare(strict_types=1);

namespace Mcrm\LaravelConnectionStorage\Drivers;

use Mcrm\LaravelConnectionStorage\Interfaces\StorageDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Драйвер хранилища на основе HashiCorp Vault
 */
class VaultStorageDriver implements StorageDriverInterface
{
    /**
     * Кэшированный токен авторизации
     */
    private ?string $authenticatedToken = null;

    /**
     * Время истечения токена (timestamp)
     */
    private ?int $tokenExpiration = null;

    /**
     * @param  string  $url  URL сервера Vault
     * @param  string  $login  Логин для авторизации
     * @param  string  $password  Пароль для авторизации
     * @param  string  $service  Название сервиса
     * @param  string  $mountPath  Путь монтирования KV секретов
     * @param  int  $timeout  Таймаут запроса
     */
    public function __construct(
        private readonly string $url,
        private readonly string $login,
        private readonly string $password,
        private readonly string $service,
        private readonly string $mountPath = 'secret',
        private readonly int $timeout = 10
    ) {}

    /**
     * Получить авторизованный токен для работы с Vault
     *
     * @return string|null
     */
    private function getAuthenticatedToken(): ?string
    {
        // Проверяем кэшированный токен
        if (!empty($this->authenticatedToken) && $this->tokenExpiration !== null && time() < $this->tokenExpiration) {
            return $this->authenticatedToken;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->url}/v1/auth/userpass/login/{$this->login}", [
                    'password' => $this->password,
                ]);

            if (!$response->successful()) {
                Log::error('Ошибка авторизации в Vault', [
                    'login' => $this->login,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $responseBody = $response->json();

            if (!isset($responseBody['auth']['client_token'])) {
                Log::error('Не удалось получить токен из ответа Vault');
                return null;
            }

            $token = $responseBody['auth']['client_token'];

            // Кэшируем токен на 50 минут (vault токены обычно живут 1 час)
            $this->authenticatedToken = $token;
            $this->tokenExpiration = time() + 3000; // 50 минут



            return $token;
        } catch (\Throwable $e) {
            Log::error('Ошибка авторизации в Vault', [
                'login' => $this->login,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Получает значение из Vault
     */
    public function get(string $key): mixed
    {
        $token = $this->getAuthenticatedToken();
        if ($token === null) {
            Log::error('Не удалось получить токен для Vault');
            return null;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->get("{$this->url}/v1/{$this->mountPath}/data/{$key}/{$this->service}");

            if (!$response->successful()) {
                if ($response->status() === 404) {
                    return null;
                }

                Log::error('Ошибка при получении данных из Vault', [
                    'key' => $key,
                    'service' => $this->service,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();

            // Vault возвращает данные в формате: data.data.value
            if (!isset($data['data']['data'])) {
                return null;
            }

            $storedData = $data['data']['data'];

            // Проверяем TTL
            if (isset($storedData['expires_at'])) {
                if (time() > $storedData['expires_at']) {
                    $this->forget($key);
                    return null;
                }
            }

            return $storedData;
        } catch (\Throwable $e) {
            Log::error('Ошибка при получении данных из Vault', [
                'key' => $key,
                'service' => $this->service,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Сохраняет значение в Vault
     */
    public function put(string $key, mixed $value, int $ttl): bool
    {
        $token = $this->getAuthenticatedToken();
        if ($token === null) {
            Log::error('Не удалось получить токен для Vault');
            return false;
        }

        try {
            $expiresAt = time() + $ttl;

            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->post("{$this->url}/v1/{$this->mountPath}/data/{$key}/{$this->service}", [
                    'data' => [
                        'value' => $value,
                        'expires_at' => $expiresAt,
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('Ошибка при сохранении данных в Vault', [
                    'key' => $key,
                    'service' => $this->service,
                    'status' => $response->status(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Ошибка при сохранении данных в Vault', [
                'key' => $key,
                'service' => $this->service,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Удаляет значение из Vault
     */
    public function forget(string $key): bool
    {
        $token = $this->getAuthenticatedToken();
        if ($token === null) {
            Log::error('Не удалось получить токен для Vault');
            return false;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->delete("{$this->url}/v1/{$this->mountPath}/metadata/{$key}/{$this->service}");

            if (!$response->successful() && $response->status() !== 404) {
                Log::error('Ошибка при удалении данных из Vault', [
                    'key' => $key,
                    'service' => $this->service,
                    'status' => $response->status(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Ошибка при удалении данных из Vault', [
                'key' => $key,
                'service' => $this->service,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Проверяет наличие ключа в Vault
     */
    public function has(string $key): bool
    {
        $value = $this->get($key);

        return $value !== null;
    }

    /**
     * Получает или сохраняет значение в Vault
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();

        if ($value !== null) {
            $this->put($key, $value, $ttl);
        }

        return $value;
    }
}

