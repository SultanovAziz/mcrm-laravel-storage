<?php

declare(strict_types=1);

namespace Mcrm\LaravelConnectionStorage;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Mcrm\LaravelConnectionStorage\Interfaces\ConnectionCacheServiceInterface;
use Mcrm\LaravelConnectionStorage\Interfaces\ConnectionDataProviderInterface;
use Mcrm\LaravelConnectionStorage\Interfaces\ConnectionNotifierInterface;
use Mcrm\LaravelConnectionStorage\Interfaces\ConnectionValidatorInterface;
use Mcrm\LaravelConnectionStorage\Interfaces\ExternalConnectionServiceInterface;
use Mcrm\LaravelConnectionStorage\Interfaces\StorageDriverInterface;
use Mcrm\LaravelConnectionStorage\Services\ConnectionCacheService;
use Mcrm\LaravelConnectionStorage\Services\ConnectionDataProvider;
use Mcrm\LaravelConnectionStorage\Services\ConnectionNotifier;
use Mcrm\LaravelConnectionStorage\Services\ConnectionValidator;
use Mcrm\LaravelConnectionStorage\Services\ExternalConnectionService;
use Mcrm\LaravelConnectionStorage\Drivers\RedisStorageDriver;
use Mcrm\LaravelConnectionStorage\Drivers\VaultStorageDriver;

class ConnectionStorageServiceProvider extends ServiceProvider
{
    /**
     * Регистрирует сервисы библиотеки
     */
    public function register(): void
    {
        $configPath = config_path('connection-storage.php');
        
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'connection-storage');
        } else {
            $this->mergeConfigFrom(
                __DIR__ . '/Config/connection-storage.php',
                'connection-storage'
            );
        }

        // Регистрация драйвера хранилища
        $this->app->singleton(StorageDriverInterface::class, function (Application $app) {
            $config = $app['config']['connection-storage'];
            $driver = $config['storage_driver'] ?? 'redis';

            return match ($driver) {
                'vault' => new VaultStorageDriver(
                    $config['vault']['url'],
                    $config['vault']['login'],
                    $config['vault']['password'],
                    $config['service']['key'],
                    $config['vault']['mount_path'],
                    $config['vault']['timeout']
                ),
                'redis' => new RedisStorageDriver(
                    $config['cache']['store'],
                    $config['service']['key']
                ),
                default => throw new \InvalidArgumentException("Неподдерживаемый драйвер хранилища: {$driver}")
            };
        });

        $this->app->singleton(ConnectionCacheServiceInterface::class, function (Application $app) {
            $config = $app['config']['connection-storage'];
            
            return new ConnectionCacheService(
                $app->make(StorageDriverInterface::class),
                $config['cache']['prefix']
            );
        });

        $this->app->singleton(ExternalConnectionServiceInterface::class, function (Application $app) {
            $config = $app['config']['connection-storage'];
            
            return new ExternalConnectionService(
                $config['external_api']['url'],
                $config['external_api']['timeout'],
                $config['external_api']['token']
            );
        });

        $this->app->singleton(ConnectionValidatorInterface::class, function (Application $app) {
            $config = $app['config']['connection-storage'];
            
            return new ConnectionValidator(
                $app->make(StorageDriverInterface::class),
                $config['validation']['timeout'],
                $config['validation']['block_duration'],
                $config['validation']['block_prefix']
            );
        });

        $this->app->singleton(ConnectionNotifierInterface::class, function (Application $app) {
            $config = $app['config']['connection-storage'];
            
            return new ConnectionNotifier(
                $config['external_api']['notification_url'],
                $config['service']['name'],
                $config['notifications']['timeout'],
                $config['notifications']['enabled'],
                $config['external_api']['token']
            );
        });

        $this->app->singleton(ConnectionDataProviderInterface::class, function (Application $app) {
            $config = $app['config']['connection-storage'];
            
            return new ConnectionDataProvider(
                $app->make(ConnectionCacheServiceInterface::class),
                $app->make(ExternalConnectionServiceInterface::class),
                $app->make(ConnectionValidatorInterface::class),
                $app->make(ConnectionNotifierInterface::class),
                $config['dto']['class'],
                $config['service']['key'],
                $config['validation']['enabled']
            );
        });
    }

    /**
     * Загружает ресурсы пакета
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/Config/connection-storage.php' => config_path('connection-storage.php'),
            ], 'connection-storage-config');
        }
    }
} 