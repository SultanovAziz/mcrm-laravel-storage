<?php

declare(strict_types=1);

namespace Mcrm\LaravelConnectionStorage\Services;

use Mcrm\LaravelConnectionStorage\DTO\BaseConnectionDataDTO;
use Mcrm\LaravelConnectionStorage\Interfaces\ConnectionCacheServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для кэширования данных подключения в Redis
 */
class ConnectionCacheService implements ConnectionCacheServiceInterface
{
    /**
     * Время жизни кэша по умолчанию в секундах (24 часа)
     */
    private const DEFAULT_TTL = 86400;

    /**
     * @param  string  $cacheStore  Хранилище кэша (redis)
     * @param  string  $cachePrefix  Префикс ключей кэша
     */
    public function __construct(
        private readonly string $cacheStore = 'redis',
        private readonly string $cachePrefix = 'connection_data_'
    ) {}

    /**
     * Получает данные подключения из кэша для указанного URL
     *
     * @param  string  $url  URL (название коробки)
     * @return BaseConnectionDataDTO|null Данные подключения или null, если данных нет в кэше
     */
    public function getConnectionData(string $url): ?BaseConnectionDataDTO
    {
        $key = $this->getCacheKey($url);
        /** @var array<string, mixed>|null $data */
        $data = Cache::store($this->cacheStore)->get($key);

        if (!$data) {
            return null;
        }

        try {
            // Получаем класс DTO из конфигурации
            $dtoClass = config('connection-storage.dto.class', \Mcrm\LaravelConnectionStorage\DTO\ConnectionDataDTO::class);
            return $dtoClass::fromArray($data);
        } catch (\Throwable $e) {
            Log::warning('Ошибка при десериализации данных подключения из кэша', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Получает сырые данные подключения из кэша (массив) для указанного URL
     *
     * @param  string  $url  URL (название коробки)
     * @return array<string, mixed>|null Сырые данные подключения или null, если данных нет в кэше
     */
    public function getRawConnectionData(string $url): ?array
    {
        $key = $this->getCacheKey($url);
        /** @var array<string, mixed>|null $data */
        $data = Cache::store($this->cacheStore)->get($key);

        return $data ?: null;
    }

    /**
     * Кэширует данные подключения
     *
     * @param  string  $url  URL (название коробки)
     * @param  array<string, mixed> $data  Данные для кэширования
     * @return bool Результат операции
     */
    public function cacheConnectionData(string $url, array $data): bool
    {
        return $this->set($url, $data);
    }

    /**
     * Сохраняет данные подключения в кэш
     *
     * @param  string  $url  URL (название коробки)
     * @param  array<string, mixed> $data  Данные подключения
     * @param  int|null  $ttl  Время жизни кэша в секундах (null - использовать значение по умолчанию)
     * @return bool Результат сохранения
     */
    public function set(string $url, array $data, ?int $ttl = null): bool
    {
        $key = $this->getCacheKey($url);
        $ttl = $ttl ?? self::DEFAULT_TTL;

        try {
            return Cache::store($this->cacheStore)->put(
                $key,
                $data,
                $ttl
            );
        } catch (\Throwable $e) {
            Log::error('Ошибка при сохранении данных подключения в кэш', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Удаляет данные подключения из кэша
     *
     * @param  string  $url  URL (название коробки)
     * @return bool Результат удаления
     */
    public function forget(string $url): bool
    {
        $key = $this->getCacheKey($url);

        try {
            return Cache::store($this->cacheStore)->forget($key);
        } catch (\Throwable $e) {
            Log::error('Ошибка при удалении данных подключения из кэша', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Получает и сохраняет данные подключения, если их нет в кэше
     *
     * @param  string  $url  URL (название коробки)
     * @param  callable  $callback  Функция для получения данных, если их нет в кэше
     * @param  int|null  $ttl  Время жизни кэша в секундах (null - использовать значение по умолчанию)
     * @return BaseConnectionDataDTO|null Данные подключения или null, если данные не найдены
     */
    public function remember(string $url, callable $callback, ?int $ttl = null): ?BaseConnectionDataDTO
    {
        $key = $this->getCacheKey($url);
        $ttl = $ttl ?? self::DEFAULT_TTL;

        try {
            $data = Cache::store($this->cacheStore)->remember(
                $key,
                $ttl,
                function () use ($callback) {
                    $result = $callback();

                    if ($result instanceof BaseConnectionDataDTO) {
                        return $result->toArray();
                    }

                    if ($result === null) {
                        // Если функция вернула null, сохраняем false в кэше,
                        // чтобы не делать постоянные запросы к внешнему API
                        // при отсутствии данных
                        return false;
                    }

                    return null;
                }
            );

            // Если в кэше сохранили false, возвращаем null
            if ($data === false) {
                return null;
            }

            if (!is_array($data)) {
                return null;
            }

            // Получаем класс DTO из конфигурации
            $dtoClass = config('connection-storage.dto.class', \Mcrm\LaravelConnectionStorage\DTO\ConnectionDataDTO::class);
            return $dtoClass::fromArray($data);
        } catch (\Throwable $e) {
            Log::error('Ошибка при работе с кэшем данных подключения', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            // Пытаемся получить данные напрямую через callback в случае ошибки кэша
            try {
                return $callback();
            } catch (\Throwable $innerException) {
                Log::error('Ошибка при получении данных через callback', [
                    'url' => $url,
                    'message' => $innerException->getMessage(),
                ]);

                return null;
            }
        }
    }

    /**
     * Формирует ключ для кэша
     *
     * @param  string  $url  URL (название коробки)
     * @return string Ключ кэша
     */
    private function getCacheKey(string $url): string
    {
        return $this->cachePrefix . $url;
    }
}
