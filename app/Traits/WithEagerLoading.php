<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait WithEagerLoading
{
    protected array $eagerLoadRelations = [];

    public function scopeWithEager(Builder $query, array $relations): Builder
    {
        return $query->with($relations);
    }

    public function scopeWithCountEager(Builder $query, array $relations): Builder
    {
        return $query->withCount($relations);
    }

    protected function shouldEagerLoad(string $relation): bool
    {
        return in_array($relation, $this->eagerLoadRelations);
    }

    protected function eagerLoadRelations(array $relations): void
    {
        $this->eagerLoadRelations = array_merge($this->eagerLoadRelations, $relations);
    }
}
