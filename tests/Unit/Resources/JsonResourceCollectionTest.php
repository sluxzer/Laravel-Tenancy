<?php

declare(strict_types=1);

use App\Http\Resources\JsonResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

// Since JsonResourceCollection is a simple helper class that doesn't depend on database or tenancy,
// we'll use simple test data instead of models to avoid tenancy cache issues.

it('can create paginated response', function () {
    // Create test data
    $items = collect([
        ['id' => 1, 'name' => 'Item 1'],
        ['id' => 2, 'name' => 'Item 2'],
        ['id' => 3, 'name' => 'Item 3'],
    ]);

    // Create a paginator
    $paginator = new LengthAwarePaginator(
        $items->take(2)->values(),
        3,
        2,
        1
    );

    // Create a simple test resource class
    $testResourceClass = new class(['id' => 1, 'name' => 'test']) extends JsonResource
    {
        public function toArray($request)
        {
            return [
                'id' => $this->resource['id'],
                'name' => $this->resource['name'],
            ];
        }
    };

    $result = JsonResourceCollection::paginated($paginator, get_class($testResourceClass));

    expect($result['success'])->toBeTrue()
        ->and($result['data'])->toHaveCount(2)
        ->and($result['pagination']['total'])->toBe(3)
        ->and($result['pagination']['per_page'])->toBe(2)
        ->and($result['pagination']['current_page'])->toBe(1)
        ->and($result['pagination']['last_page'])->toBe(2);
});

it('can create single resource response', function () {
    // Create a test resource with simple data
    $testData = ['id' => 123, 'name' => 'Test Item'];

    $testResource = new class($testData) extends JsonResource
    {
        public function toArray($request)
        {
            return [
                'id' => $this->resource['id'],
                'name' => $this->resource['name'],
            ];
        }
    };

    $result = JsonResourceCollection::single($testResource);

    expect($result['success'])->toBeTrue()
        ->and($result['data']['id'])->toBe(123)
        ->and($result['data']['name'])->toBe('Test Item');
});

it('can create success response', function () {
    $data = ['message' => 'Success'];

    $result = JsonResourceCollection::success('Operation completed', $data);

    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toBe('Operation completed')
        ->and($result['data'])->toBe($data);
});

it('can create error response', function () {
    $result = JsonResourceCollection::error('Something went wrong', 400, ['field' => ['error']]);

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toBe('Something went wrong')
        ->and($result['errors'])->toBe(['field' => ['error']]);
});
