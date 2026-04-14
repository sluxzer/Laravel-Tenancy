<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\QueryCacheService;
use Illuminate\Database\Eloquent\Builder;

trait WithQueryCache
{
    protected static ?QueryCacheService $queryCacheService = null;

    protected static function bootWithQueryCache(): void
    {
        static::saved(function ($model) {
            self::getQueryCacheService()->invalidatePattern(get_class($model).':*');
        });

        static::deleted(function ($model) {
            self::getQueryCacheService()->invalidatePattern(get_class($model).':*');
        });
    }

    protected static function getQueryCacheService(): QueryCacheService
    {
        return self::$queryCacheService ??= app(QueryCacheService::class);
    }

    public function scopeCache(Builder $query, string $key, ?int $ttl = null): mixed
    {
        return self::getQueryCacheService()->cache($query, $key, $ttl);
    }
}
