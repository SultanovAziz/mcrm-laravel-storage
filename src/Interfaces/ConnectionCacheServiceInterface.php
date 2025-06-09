<?php

declare(strict_types=1);

namespace Mcrm\LaravelConnectionStorage\Interfaces;

use Mcrm\LaravelConnectionStorage\DTO\BaseConnectionDataDTO;

/**
 * Интерфейс для сервиса кэширования данных подключения
 */
interface ConnectionCacheServiceInterface
{
    /**
     * Получает данные подключения из кэша для указанного URL
     *
     * @param  string  $url  URL (название коробки)
     * @return BaseConnectionDataDTO|null Данные подключения или null, если данных нет в кэше
     */
    public function getConnectionData(string $url): ?BaseConnectionDataDTO;

    /**
     * Получает сырые данные подключения из кэша (массив) для указанного URL
     *
     * @param  string  $url  URL (название коробки)
     * @return array<string, mixed>|null Сырые данные подключения или null, если данных нет в кэше
     */
    public function getRawConnectionData(string $url): ?array;

    /**
     * Кэширует данные подключения
     *
     * @param  string  $url  URL (название коробки)
     * @param  array<string, mixed>  $data  Данные для кэширования
     * @return bool Результат операции
     */
    public function cacheConnectionData(string $url, array $data): bool;

    /**
     * Сохраняет данные подключения в кэш
     *
     * @param  string  $url  URL (название коробки)
     * @param array $data Данные подключения
     * @param  int|null  $ttl  Время жизни кэша в секундах (null - использовать значение по умолчанию)
     * @return bool Результат сохранения
     */
    public function set(string $url, array $data, ?int $ttl = null): bool;

    /**
     * Удаляет данные подключения из кэша
     *
     * @param  string  $url  URL (название коробки)
     * @return bool Результат удаления
     */
    public function forget(string $url): bool;

    /**
     * Получает и сохраняет данные подключения, если их нет в кэше
     *
     * @param  string  $url  URL (название коробки)
     * @param  callable  $callback  Функция для получения данных, если их нет в кэше
     * @param  int|null  $ttl  Время жизни кэша в секундах (null - использовать значение по умолчанию)
     * @return BaseConnectionDataDTO|null Данные подключения или null, если данные не найдены
     */
    public function remember(string $url, callable $callback, ?int $ttl = null): ?BaseConnectionDataDTO;
}
