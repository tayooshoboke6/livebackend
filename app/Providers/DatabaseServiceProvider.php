<?php

namespace App\Providers;

use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDOException;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Add retry mechanism for database connections
        Connection::resolverFor('mysql', function ($connection, $database, $prefix, $config) {
            $connection = new \Illuminate\Database\MySqlConnection($connection, $database, $prefix, $config);
            
            // Add retry mechanism for handling connection errors
            $connection->setPdo($this->addRetryMechanism(
                fn () => $connection->getReadPdo(), 
                $config
            ));
            
            $connection->setReadPdo($this->addRetryMechanism(
                fn () => $connection->getReadPdo(), 
                $config
            ));
            
            return $connection;
        });
        
        // Log slow queries in development environment
        if (app()->environment('local', 'development')) {
            DB::listen(function (QueryExecuted $query) {
                // Log queries that take longer than 500ms
                if ($query->time > 500) {
                    Log::channel('daily')->warning(
                        'Slow query: ' . $query->sql,
                        [
                            'time' => $query->time,
                            'bindings' => $query->bindings,
                            'connection' => $query->connection->getName(),
                        ]
                    );
                }
            });
        }
    }
    
    /**
     * Add retry mechanism to database connections
     *
     * @param  \Closure  $getPdo
     * @param  array  $config
     * @return \PDO
     */
    protected function addRetryMechanism($getPdo, array $config)
    {
        $retryCount = $config['retry_count'] ?? 3;
        $retryAfter = $config['retry_after'] ?? 60;
        
        $pdo = null;
        $attempts = 0;
        $lastException = null;
        
        while ($attempts < $retryCount && $pdo === null) {
            $attempts++;
            
            try {
                $pdo = $getPdo();
            } catch (PDOException $e) {
                $lastException = $e;
                
                // Only retry on connection errors
                if (!$this->isConnectionError($e)) {
                    throw $e;
                }
                
                Log::warning("Database connection failed (attempt {$attempts}/{$retryCount}): {$e->getMessage()}");
                
                if ($attempts < $retryCount) {
                    // Wait before retrying
                    usleep($retryAfter * 1000);
                }
            }
        }
        
        if ($pdo === null) {
            throw $lastException;
        }
        
        return $pdo;
    }
    
    /**
     * Determine if the given exception is a connection error
     *
     * @param  \PDOException  $e
     * @return bool
     */
    protected function isConnectionError(PDOException $e)
    {
        $message = $e->getMessage();
        $code = $e->getCode();
        
        // Common MySQL/MariaDB connection error codes
        $connectionErrors = [
            2002, // Connection refused
            2003, // Can't connect to MySQL server
            2006, // Server has gone away
            2013, // Lost connection during query
        ];
        
        return in_array($code, $connectionErrors) || 
               str_contains($message, 'Connection refused') ||
               str_contains($message, 'Lost connection') ||
               str_contains($message, 'gone away');
    }
}
