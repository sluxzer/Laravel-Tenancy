<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    abstract protected function model(): string;

    public function find(string|int $id): ?Model
    {
        return $this->query()->find($id);
    }

    public function findOrFail(string|int $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->query()->get($columns);
    }

    public function create(array $data): Model
    {
        return $this->query()->create($data);
    }

    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    public function paginate(
        int $perPage = 20,
        array $columns = ['*'],
        string $pageName = 'page'
    ): LengthAwarePaginator {
        return $this->query()->paginate($perPage, $columns, $pageName);
    }

    public function query(): Builder
    {
        return $this->model()::query();
    }

    protected ?Builder $currentQuery = null;

    public function with(array $relations): self
    {
        $this->currentQuery = $this->query()->with($relations);

        return $this;
    }

    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        $query = $this->currentQuery ?? $this->query();
        $this->currentQuery = $query->where($column, $operator, $value);

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $query = $this->currentQuery ?? $this->query();
        $this->currentQuery = $query->orderBy($column, $direction);

        return $this;
    }

    public function get(): Collection
    {
        $query = $this->currentQuery ?? $this->query();
        $this->currentQuery = null;

        return $query->get();
    }

    public function first(): ?Model
    {
        $query = $this->currentQuery ?? $this->query();
        $this->currentQuery = null;

        return $query->first();
    }
}
