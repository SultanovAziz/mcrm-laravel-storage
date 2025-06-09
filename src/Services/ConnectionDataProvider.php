<?php

declare(strict_types=1);

namespace Mcrm\LaravelConnectionStorage\Services;

use Mcrm\LaravelConnectionStorage\DTO\BaseConnectionDataDTO;
use Mcrm\LaravelConnectionStorage\Interfaces\ConnectionCacheServiceInterface;
use Mcrm\LaravelConnectionStorage\Interfaces\ConnectionDataProviderInterface;
use Mcrm\LaravelConnectionStorage\Interfaces\ConnectionNotifierInterface;
use Mcrm\LaravelConnectionStorage\Interfaces\ConnectionValidatorInterface;
use Mcrm\LaravelConnectionStorage\Interfaces\ExternalConnectionServiceInterface;
use Illuminate\Support\Facades\Log;

/**
 * Провайдер данных подключений с кэшированием, валидацией и внешним API
 */
class ConnectionDataProvider implements ConnectionDataProviderInterface
{
    /**
     * @param  ConnectionCacheServiceInterface  $cacheService  Сервис кэширования
     * @param  ExternalConnectionServiceInterface  $externalService  Сервис внешнего API
     * @param  ConnectionValidatorInterface  $validator  Сервис валидации
     * @param  ConnectionNotifierInterface  $notifier  Сервис уведомлений
     * @param  string  $dtoClass  Класс DTO для десериализации
     * @param  string  $serviceKey  Ключ сервиса для извлечения данных
     * @param  bool  $validationEnabled  Включена ли валидация
     */
    public function __construct(
        private readonly ConnectionCacheServiceInterface $cacheService,
        private readonly ExternalConnectionServiceInterface $externalService,
        private readonly ConnectionValidatorInterface $validator,
        private readonly ConnectionNotifierInterface $notifier,
        private readonly string $dtoClass,
        private readonly string $serviceKey,
        private readonly bool $validationEnabled = true
    ) {}

    /**
     * Получает данные подключения с проверкой кэша и обращением к внешнему API
     *
     * @param  string  $identifier  Идентификатор (название коробки)
     * @return BaseConnectionDataDTO|null Данные подключения или null, если не найдены
     */
    public function getConnectionData(string $identifier): ?BaseConnectionDataDTO
    {
        if ($this->validator->isBlocked($identifier)) {
            return null;
        }

        // Сначала пытаемся получить сырые данные из кэша
        $rawCacheData = $this->cacheService->getRawConnectionData($identifier);

        if ($rawCacheData !== null) {
            $connectionData = $this->processConnectionData($rawCacheData, $identifier, 'cache');

            if ($connectionData !== null) {
                if ($this->validationEnabled && !$this->validateAndNotify($connectionData, $identifier, 'cache')) {
                    return null;
                }

                return $connectionData;
            }
        }

        // Если в кэше нет, запрашиваем у внешнего сервиса
        return $this->fetchFromExternalService($identifier);
    }

    /**
     * Кэширует данные подключения
     *
     * @param  string  $identifier  Идентификатор (название коробки)
     * @param  array<string, mixed> $data  Данные для кэширования
     * @return bool Результат операции
     */
    public function cacheConnectionData(string $identifier, array $data): bool
    {
        return $this->cacheService->cacheConnectionData($identifier, $data);
    }

    /**
     * Получает данные из внешнего сервиса
     *
     * @param  string  $identifier  Идентификатор
     * @return BaseConnectionDataDTO|null Данные подключения
     */
    private function fetchFromExternalService(string $identifier): ?BaseConnectionDataDTO
    {
        try {
            $rawData = $this->externalService->getConnectionData($identifier);

            if ($rawData === null) {
                return null;
            }

            $connectionData = $this->processConnectionData($rawData, $identifier, 'external_api');

            if ($connectionData === null) {
                return null;
            }

            // Проверяем валидность полученных данных
            if ($this->validationEnabled && !$this->validateAndNotify($connectionData, $identifier, 'external_api')) {
                return null;
            }

            return $connectionData;

        } catch (\Throwable $e) {
            Log::error('Ошибка при получении данных из внешнего сервиса', [
                'identifier' => $identifier,
                'error' => $e->getMessage(),
            ]);

            // Блокируем запросы к данному identifier
            $this->validator->blockUrl($identifier);

            return null;
        }
    }

    /**
     * Извлекает данные для текущего сервиса из общего массива
     *
     * @param  array<string, mixed>  $rawData  Сырые данные
     * @return BaseConnectionDataDTO|null Данные сервиса
     */
    private function extractServiceData(array $rawData): ?BaseConnectionDataDTO
    {
        return $rawData[$this->serviceKey] ?? null;
    }

    /**
     * Валидирует данные подключения и отправляет уведомления при ошибках
     *
     * @param  BaseConnectionDataDTO  $connectionData  Данные подключения
     * @param  string  $identifier  Идентификатор коробки
     * @param  string  $source  Источник данных (cache/external_api)
     * @return bool True, если данные валидны
     */
    private function validateAndNotify(BaseConnectionDataDTO $connectionData, string $identifier, string $source): bool
    {
        if (!$this->validator->validateConnectionData($connectionData)) {
            $this->validateIndividualConnections($connectionData, $identifier);

            $this->validator->blockUrl($identifier);

            return false;
        }

        return true;
    }

    /**
     * Проверяет отдельные типы соединений и отправляет уведомления об ошибках
     *
     * @param  BaseConnectionDataDTO  $connectionData  Данные подключения
     * @param  string  $identifier  Идентификатор коробки
     */
    private function validateIndividualConnections(BaseConnectionDataDTO $connectionData, string $identifier): void
    {
        $dbConfig = $connectionData->getConnectionConfig('db');
        if ($dbConfig && !$this->validator->validateDatabaseConnection($dbConfig)) {
            $this->notifier->notifyConnectionError(
                $identifier,
                'database',
                'Не удается подключиться к базе данных',
                $dbConfig
            );
        }

        // Здесь можно добавить проверку других типов соединений
        // например, RabbitMQ, Elasticsearch и т.д.
    }

    /**
     * Обрабатывает сырые данные подключения (извлекает данные сервиса и создает DTO)
     *
     * @param  array<string, mixed>  $rawData  Сырые данные
     * @param  string  $identifier  Идентификатор
     * @param  string  $source  Источник данных (cache/external_api)
     * @return BaseConnectionDataDTO|null Обработанные данные подключения
     */
    private function processConnectionData(array $rawData, string $identifier, string $source): ?BaseConnectionDataDTO
    {
        // Извлекаем данные для нашего сервиса
        /** @var BaseConnectionDataDTO $connectionDto */
        $connectionDto = $this->extractServiceData($rawData);
        if ($connectionDto === null) {
            $this->notifier->notifyInvalidConnection(
                $identifier,
                "Данные для сервиса '{$this->serviceKey}' не найдены в {$source}",
                ['available_services' => array_keys($rawData)]
            );
            return null;
        }

        // Создаем DTO из полученных данных
//        $connectionData = call_user_func([$this->dtoClass, 'fromArray'], $serviceData);
//
//        if ($connectionData === null) {
//            $this->notifier->notifyInvalidConnection(
//                $identifier,
//                "Невозможно создать DTO из данных {$source}",
//                ['raw_data' => $serviceData]
//            );
//            return null;
//        }

        return $connectionDto;
    }
}
