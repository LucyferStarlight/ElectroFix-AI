<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponse
{
    protected function success(mixed $data, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $data,
            'meta' => $meta,
            'error' => null,
        ], $status);
    }

    protected function successResource(JsonResource|ResourceCollection $resource, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $resource->resolve(),
            'meta' => $meta,
            'error' => null,
        ], $status);
    }

    protected function paginated(ResourceCollection $collection, LengthAwarePaginator $paginator, array $meta = []): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $collection->resolve(),
            'meta' => array_merge($meta, [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]),
            'error' => null,
        ]);
    }
}

