<?php

declare(strict_types=1);

namespace Mcrm\LaravelConnectionStorage\Interfaces;

/**
 * Интерфейс драйвера хранилища данных (Redis, Vault и т.д.)
 */
interface StorageDriverInterface
{
    /**
     * Получает значение из хранилища
     *
     * @param  string  $key  Ключ
     * @return mixed Значение или null, если ключ не найден
     */
    public function get(string $key): mixed;

    /**
     * Сохраняет значение в хранилище
     *
     * @param  string  $key  Ключ
     * @param  mixed  $value  Значение
     * @param  int  $ttl  Время жизни в секундах
     * @return bool Результат операции
     */
    public function put(string $key, mixed $value, int $ttl): bool;

    /**
     * Удаляет значение из хранилища
     *
     * @param  string  $key  Ключ
     * @return bool Результат операции
     */
    public function forget(string $key): bool;

    /**
     * Проверяет наличие ключа в хранилище
     *
     * @param  string  $key  Ключ
     * @return bool True, если ключ существует
     */
    public function has(string $key): bool;

    /**
     * Получает или сохраняет значение
     *
     * @param  string  $key  Ключ
     * @param  int  $ttl  Время жизни в секундах
     * @param  callable  $callback  Функция для получения значения
     * @return mixed Значение
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;
}

