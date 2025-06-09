<?php

declare(strict_types=1);

namespace Mcrm\LaravelConnectionStorage\Services;

use Mcrm\LaravelConnectionStorage\Interfaces\ConnectionNotifierInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для отправки уведомлений о проблемах с соединениями
 */
class ConnectionNotifier implements ConnectionNotifierInterface
{
    /**
     * @param  string  $notificationUrl  URL для отправки уведомлений
     * @param  string  $serviceName  Название текущего сервиса
     * @param  int  $timeout  Таймаут запроса в секундах
     * @param  bool  $enabled  Включены ли уведомления
     * @param  string|null  $token  Bearer токен для авторизации
     */
    public function __construct(
        private readonly string $notificationUrl,
        private readonly string $serviceName,
        private readonly int $timeout = 10,
        private readonly bool $enabled = true,
        private readonly ?string $token = null
    ) {}

    /**
     * Отправляет уведомление о некорректных данных подключения
     *
     * @param  string  $boxName  Название коробки
     * @param  string  $reason  Причина некорректности
     * @param  array<string, mixed>  $details  Дополнительные детали
     * @return bool Результат отправки
     */
    public function notifyInvalidConnection(string $boxName, string $reason, array $details = []): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $payload = [
            'event_type' => 'invalid_connection_data',
            'service' => $this->serviceName,
            'box_name' => $boxName,
            'reason' => $reason,
            'details' => $details,
            'timestamp' => now()->toISOString(),
        ];

        return $this->sendNotification($payload, 'Уведомление о некорректных данных подключения');
    }

    /**
     * Отправляет уведомление об ошибке соединения
     *
     * @param  string  $boxName  Название коробки
     * @param  string  $connectionType  Тип соединения (database, rabbitmq, etc.)
     * @param  string  $error  Описание ошибки
     * @param  array<string, mixed>  $connectionConfig  Конфигурация соединения
     * @return bool Результат отправки
     */
    public function notifyConnectionError(string $boxName, string $connectionType, string $error, array $connectionConfig = []): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $safeConfig = $this->sanitizeConfig($connectionConfig);

        $payload = [
            'event_type' => 'connection_error',
            'service' => $this->serviceName,
            'box_name' => $boxName,
            'connection_type' => $connectionType,
            'error' => $error,
            'config' => $safeConfig,
            'timestamp' => now()->toISOString(),
        ];

        return $this->sendNotification($payload, 'Уведомление об ошибке соединения');
    }

    /**
     * Отправляет уведомление по HTTP
     *
     * @param  array<string, mixed>  $payload  Данные для отправки
     * @param  string  $logMessage  Сообщение для логирования
     * @return bool Результат отправки
     */
    private function sendNotification(array $payload, string $logMessage): bool
    {
        try {
            $httpClient = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => "Laravel-ConnectionStorage/{$this->serviceName}",
                ]);
            
            if ($this->token) {
                $httpClient = $httpClient->withToken($this->token);
            }
            
            $response = $httpClient->post($this->notificationUrl, $payload);

            if ($response->successful()) {
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Удаляет чувствительные данные из конфигурации
     *
     * @param  array<string, mixed>  $config  Конфигурация
     * @return array<string, mixed> Очищенная конфигурация
     */
    private function sanitizeConfig(array $config): array
    {
        $sensitiveKeys = ['password', 'secret', 'token', 'api_key', 'private_key'];
        $sanitized = $config;

        foreach ($sensitiveKeys as $key) {
            if (isset($sanitized[$key])) {
                $sanitized[$key] = '***';
            }
        }

        return $sanitized;
    }
} 