<?php

declare(strict_types=1);

namespace Mcrm\LaravelConnectionStorage\Interfaces;

use Mcrm\LaravelConnectionStorage\DTO\BaseConnectionDataDTO;

/**
 * Интерфейс для провайдера данных подключения
 */
interface ConnectionDataProviderInterface
{
    /**
     * Получает данные подключения для указанного URL
     *
     * @param  string  $identifier  Идентификатор (название коробки)
     * @return BaseConnectionDataDTO|null Данные подключения или null, если данные не найдены
     */
    public function getConnectionData(string $identifier): ?BaseConnectionDataDTO;

    /**
     * Сохраняет данные подключения в кеш вручную
     *
     * @param  string  $identifier  Идентификатор (название коробки)
     * @param  array<string, mixed>  $data  Данные подключения
     * @return bool Результат сохранения
     */
    public function cacheConnectionData(string $identifier, array $data): bool;
}
