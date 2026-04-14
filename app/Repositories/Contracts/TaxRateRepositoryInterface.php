<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TaxRateRepositoryInterface
{
    public function find(string|int $id): ?TaxRate;

    public function findOrFail(string|int $id): TaxRate;

    public function create(array $data): TaxRate;

    public function update(TaxRate $taxRate, array $data): bool;

    public function delete(TaxRate $taxRate): bool;

    public function getAll(?bool $isActive = null, int $perPage = 20): LengthAwarePaginator;

    public function getByCountry(string $countryCode): Collection;
}
