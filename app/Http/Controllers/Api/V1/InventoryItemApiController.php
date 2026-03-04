<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithCompanyScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Resources\Api\V1\InventoryItemResource;
use App\Models\Company;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryItemApiController extends Controller
{
    use ApiResponse;
    use InteractsWithCompanyScope;

    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index(Request $request)
    {
        $query = InventoryItem::query()->orderBy('name');
        $this->applyCompanyScope($query, $request);

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('internal_code', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate((int) $request->query('per_page', 20));

        return $this->paginated(InventoryItemResource::collection($items), $items);
    }

    public function store(StoreInventoryItemRequest $request)
    {
        $companyId = $this->scopedCompanyId($request) ?: $request->integer('company_id');
        if (! $companyId) {
            abort(422, 'Debes indicar company_id para crear el producto de inventario.');
        }
        $this->assertCompanyAccess($request, $companyId);

        $company = Company::query()->findOrFail($companyId);
        $item = $this->inventoryService->createItem($company, $request->user(), $request->validated());

        return $this->successResource(new InventoryItemResource($item), status: 201);
    }
}

