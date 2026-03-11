<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithCompanyScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Resources\Api\V1\EquipmentResource;
use App\Models\Customer;
use App\Models\Equipment;
use Illuminate\Http\Request;

class EquipmentApiController extends Controller
{
    use ApiResponse;
    use InteractsWithCompanyScope;

    public function index(Request $request)
    {
        $query = Equipment::query()->with('customer')->orderByDesc('created_at');
        $this->applyCompanyScope($query, $request);

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search): void {
                $q->where('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        $equipments = $query->paginate((int) $request->query('per_page', 20));

        return $this->paginated(EquipmentResource::collection($equipments), $equipments);
    }

    public function store(StoreEquipmentRequest $request)
    {
        $customer = Customer::query()->findOrFail($request->integer('customer_id'));
        $this->assertCompanyAccess($request, $customer->company_id);

        $equipment = Equipment::query()->create([
            ...$request->validated(),
            'company_id' => $customer->company_id,
        ]);

        return $this->successResource(new EquipmentResource($equipment), status: 201);
    }
}

