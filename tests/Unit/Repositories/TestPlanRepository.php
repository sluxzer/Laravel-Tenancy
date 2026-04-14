<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Plan;
use App\Repositories\Eloquent\BaseRepository;

class TestPlanRepository extends BaseRepository
{
    protected function model(): string
    {
        return Plan::class;
    }
}
