<?php

declare(strict_types=1);

namespace Mcrm\LaravelConnectionStorage\Drivers;

use Mcrm\LaravelConnectionStorage\Interfaces\StorageDriverInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Драйвер хранилища на основе Redis
 */
class RedisStorageDriver implements StorageDriverInterface
{
    /**
     * @param  string  $store  Название хранилища Cache
     * @param  string  $service  Ключ сервиса для извлечения данных
     */
    public function __construct(
        private readonly string $store,
        private readonly string $service
    ) {}

    /**
     * Получает значение из Redis
     */
    public function get(string $key): mixed
    {
        try {
            $data = Cache::store($this->store)->get($key);

            if (isset($data[$this->service])) {
                return $data[$this->service];
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('Ошибка при получении данных из Redis', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Сохраняет значение в Redis
     */
    public function put(string $key, mixed $value, int $ttl): bool
    {
        try {
            return Cache::store($this->store)->put($key, $value, $ttl);
        } catch (\Throwable $e) {
            Log::error('Ошибка при сохранении данных в Redis', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Удаляет значение из Redis
     */
    public function forget(string $key): bool
    {
        try {
            return Cache::store($this->store)->forget($key);
        } catch (\Throwable $e) {
            Log::error('Ошибка при удалении данных из Redis', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Проверяет наличие ключа в Redis
     */
    public function has(string $key): bool
    {
        try {
            return Cache::store($this->store)->has($key);
        } catch (\Throwable $e) {
            Log::error('Ошибка при проверке ключа в Redis', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Получает или сохраняет значение в Redis
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        try {
            return Cache::store($this->store)->remember($key, $ttl, $callback);
        } catch (\Throwable $e) {
            Log::error('Ошибка при работе с remember в Redis', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

