<?php

declare(strict_types=1);

namespace Mcrm\LaravelConnectionStorage\Interfaces;

use Mcrm\LaravelConnectionStorage\DTO\BaseConnectionDataDTO;

/**
 * Интерфейс для сервиса получения данных подключения от внешнего API
 */
interface ExternalConnectionServiceInterface
{
    /**
     * Получает данные подключения для указанного URL
     *
     * @param  string  $url  URL (название коробки)
     * @return array<string, mixed>|null Сырые данные подключения или null, если не найдены
     */
    public function getConnectionData(string $url): ?array;
} 