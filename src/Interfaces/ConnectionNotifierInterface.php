<?php

declare(strict_types=1);

namespace Mcrm\LaravelConnectionStorage\Interfaces;

/**
 * Интерфейс для отправки уведомлений о проблемах с соединениями
 */
interface ConnectionNotifierInterface
{
    /**
     * Отправляет уведомление о некорректных данных подключения
     *
     * @param  string  $boxName  Название коробки
     * @param  string  $reason  Причина некорректности
     * @param  array<string, mixed>  $details  Дополнительные детали
     * @return bool Результат отправки
     */
    public function notifyInvalidConnection(string $boxName, string $reason, array $details = []): bool;

    /**
     * Отправляет уведомление об ошибке соединения
     *
     * @param  string  $boxName  Название коробки
     * @param  string  $connectionType  Тип соединения (database, rabbitmq, etc.)
     * @param  string  $error  Описание ошибки
     * @param  array<string, mixed>  $connectionConfig  Конфигурация соединения
     * @return bool Результат отправки
     */
    public function notifyConnectionError(string $boxName, string $connectionType, string $error, array $connectionConfig = []): bool;
} 