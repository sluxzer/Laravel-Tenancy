<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Query Cache Service
 *
 * Handles caching of database queries with intelligent invalidation.
 */
class QueryCacheService
{
    protected int $defaultTtl = 3600; // 1 hour

    protected string $prefix = 'query_cache';

    /**
     * Cache a query result.
     */
    public function cache(Builder $query, string $key, ?int $ttl = null): mixed
    {
        $fullKey = $this->getFullKey($key);

        return Cache::remember($fullKey, $ttl ?? $this->defaultTtl, fn () => $query->get());
    }

    /**
     * Cache a query result with tags for invalidation.
     */
    public function cacheWithTags(Builder $query, array $tags, string $key, ?int $ttl = null): mixed
    {
        $fullKey = $this->getFullKey($key);

        return Cache::tags($tags)->remember($fullKey, $ttl ?? $this->defaultTtl, fn () => $query->get());
    }

    /**
     * Cache a single model.
     */
    public function cacheModel(Model $model, ?string $key = null, ?int $ttl = null): Model
    {
        $cacheKey = $key ?? $this->getModelKey($model);
        $fullKey = $this->getFullKey($cacheKey);

        Cache::put($fullKey, $model, $ttl ?? $this->defaultTtl);

        return $model;
    }

    /**
     * Get a cached model.
     */
    public function getCachedModel(string $modelClass, string $id, ?string $key = null): ?Model
    {
        $cacheKey = $key ?? "{$modelClass}:{$id}";
        $fullKey = $this->getFullKey($cacheKey);

        return Cache::get($fullKey);
    }

    /**
     * Get cache key for a model.
     */
    protected function getModelKey(Model $model): string
    {
        return get_class($model).':'.$model->getKey();
    }

    /**
     * Get full cache key with prefix.
     */
    protected function getFullKey(string $key): string
    {
        $tenantPrefix = tenancy()->tenant ? 'tenant:'.tenancy()->tenant->id : 'global';

        return "{$this->prefix}:{$tenantPrefix}:{$key}";
    }

    /**
     * Invalidate cache by key.
     */
    public function invalidate(string $key): void
    {
        $fullKey = $this->getFullKey($key);
        Cache::forget($fullKey);
    }

    /**
     * Invalidate cache by pattern.
     */
    public function invalidatePattern(string $pattern): void
    {
        $fullPattern = $this->getFullKey($pattern);
        $this->forgetKeysByPattern($fullPattern);
    }

    /**
     * Forget keys by pattern.
     */
    protected function forgetKeysByPattern(string $pattern): void
    {
        $redis = Cache::getStore();

        if (method_exists($redis, 'connection')) {
            $connection = $redis->connection();
            $keys = $connection->keys($pattern.'*');

            if (! empty($keys)) {
                $connection->del($keys);
            }
        }
    }

    /**
     * Invalidate cache by tags.
     */
    public function invalidateTags(array $tags): void
    {
        Cache::tags($tags)->flush();
    }

    /**
     * Invalidate cache for a model.
     */
    public function invalidateModel(Model $model): void
    {
        $key = $this->getModelKey($model);
        $this->invalidate($key);

        // Also invalidate model class patterns
        $this->invalidatePattern(get_class($model).':*');
    }

    /**
     * Invalidate cache for a tenant.
     */
    public function invalidateTenant(int $tenantId): void
    {
        $this->invalidatePattern("tenant:{$tenantId}:*");
    }

    /**
     * Get or set cached value.
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $fullKey = $this->getFullKey($key);

        return Cache::remember($fullKey, $ttl, $callback);
    }

    /**
     * Get or set cached value with tags.
     */
    public function rememberTags(array $tags, string $key, int $ttl, callable $callback): mixed
    {
        $fullKey = $this->getFullKey($key);

        return Cache::tags($tags)->remember($fullKey, $ttl, $callback);
    }

    /**
     * Get cached value without fallback.
     */
    public function get(string $key): mixed
    {
        $fullKey = $this->getFullKey($key);

        return Cache::get($fullKey);
    }

    /**
     * Set cached value.
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $fullKey = $this->getFullKey($key);

        return Cache::put($fullKey, $value, $ttl ?? $this->defaultTtl);
    }

    /**
     * Check if key exists.
     */
    public function has(string $key): bool
    {
        $fullKey = $this->getFullKey($key);

        return Cache::has($fullKey);
    }

    /**
     * Increment cached value.
     */
    public function increment(string $key, int $value = 1): int
    {
        $fullKey = $this->getFullKey($key);

        return Cache::increment($fullKey, $value);
    }

    /**
     * Decrement cached value.
     */
    public function decrement(string $key, int $value = 1): int
    {
        $fullKey = $this->getFullKey($key);

        return Cache::decrement($fullKey, $value);
    }

    /**
     * Lock a cache key.
     */
    public function lock(string $key, int $ttl, callable $callback): mixed
    {
        $fullKey = $this->getFullKey($key);

        return Cache::lock($fullKey, $ttl)->block(10, $callback);
    }

    /**
     * Generate cache key from query.
     */
    public function generateQueryKey(Builder $query, string $prefix = ''): string
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        return $prefix.':'.md5($sql.json_encode($bindings));
    }

    /**
     * Cache paginated results.
     */
    public function cachePaginated(Builder $query, string $key, int $page, int $perPage, ?int $ttl = null): array
    {
        $fullKey = $this->getFullKey("{$key}:page:{$page}:per_page:{$perPage}");

        return Cache::remember($fullKey, $ttl ?? $this->defaultTtl, fn () => $query->paginate($perPage)->toArray());
    }

    /**
     * Get cache statistics.
     */
    public function getStats(): array
    {
        $redis = Cache::getStore();

        if (! method_exists($redis, 'connection')) {
            return [];
        }

        $connection = $redis->connection();
        $info = $connection->info('stats');
        $pattern = $this->getFullKey('*');
        $keys = $connection->keys($pattern);

        return [
            'total_keys' => count($keys),
            'keyspace_hits' => $info['keyspace_hits'] ?? 0,
            'keyspace_misses' => $info['keyspace_misses'] ?? 0,
            'hit_rate' => $this->calculateHitRate($info),
            'memory_usage' => $info['used_memory_human'] ?? null,
        ];
    }

    /**
     * Calculate cache hit rate.
     */
    protected function calculateHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        return $total > 0 ? ($hits / $total) * 100 : 0;
    }

    /**
     * Warm up cache for a tenant.
     */
    public function warmup(int $tenantId, array $queries = []): void
    {
        foreach ($queries as $name => $query) {
            try {
                $this->cache($query, $name);
            } catch (\Exception $e) {
                Log::warning('Cache warmup failed', [
                    'query' => $name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Clear all cache for this prefix.
     */
    public function flushAll(): void
    {
        $this->forgetKeysByPattern($this->getFullKey('*'));
    }

    /**
     * Get cache keys count.
     */
    public function getKeysCount(string $pattern = '*'): int
    {
        $redis = Cache::getStore();

        if (! method_exists($redis, 'connection')) {
            return 0;
        }

        $connection = $redis->connection();
        $keys = $connection->keys($this->getFullKey($pattern));

        return count($keys);
    }
}
