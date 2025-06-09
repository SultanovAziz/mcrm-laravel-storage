<?php

declare(strict_types=1);

namespace Mcrm\LaravelConnectionStorage\Services;

use Mcrm\LaravelConnectionStorage\DTO\BaseConnectionDataDTO;
use Mcrm\LaravelConnectionStorage\Interfaces\ExternalConnectionServiceInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для получения данных подключения от внешнего API
 */
class ExternalConnectionService implements ExternalConnectionServiceInterface
{
    /**
     * @param  string  $apiUrl  URL внешнего API
     * @param  int  $timeout  Таймаут запроса в секундах
     * @param  string|null  $token  Bearer токен для авторизации
     */
    public function __construct(
        private readonly string $apiUrl,
        private readonly int $timeout = 10,
        private readonly ?string $token = null
    ) {}

    /**
     * Получает данные подключения для указанного URL
     *
     * @param  string  $url  URL (название коробки)
     * @return array<string, mixed>|null Сырые данные подключения или null, если данные не найдены
     */
    public function getConnectionData(string $url): ?array
    {
        try {
            $httpClient = Http::timeout($this->timeout);
            
            // Добавляем Bearer токен, если он указан
            if ($this->token) {
                $httpClient = $httpClient->withToken($this->token);
            }
            
            $response = $httpClient->get("{$this->apiUrl}/connection/{$url}");

            if (!$response->successful()) {
                Log::error('Ошибка при получении данных подключения', [
                    'url' => $url,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            if (!is_array($data)) {
                Log::error('Некорректный формат данных подключения', [
                    'url' => $url,
                    'response' => $data,
                ]);

                return null;
            }

            return $data;
        } catch (ConnectionException $e) {
            Log::error('Ошибка соединения с внешним API', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Непредвиденная ошибка при получении данных подключения', [
                'url' => $url,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }
} 