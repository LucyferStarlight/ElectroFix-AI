<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithCompanyScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignEquipmentCustomerRequest;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Resources\Api\V1\EquipmentResource;
use App\Models\Company;
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
        $customer = $request->input('customer_mode') === 'walk_in'
            ? $this->resolveWalkInCustomer($request)
            : Customer::query()->findOrFail($request->integer('customer_id'));
        $this->assertCompanyAccess($request, $customer->company_id);

        $equipment = Equipment::query()->create([
            ...collect($request->validated())->except(['customer_mode'])->all(),
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
        ]);

        return $this->successResource(new EquipmentResource($equipment), status: 201);
    }

    public function assignCustomer(AssignEquipmentCustomerRequest $request, Equipment $equipment)
    {
        $this->assertCompanyAccess($request, $equipment->company_id);

        $customer = Customer::query()->findOrFail($request->integer('customer_id'));
        if ($customer->company_id !== $equipment->company_id) {
            abort(422, 'El cliente seleccionado no pertenece a la misma empresa que el equipo.');
        }

        $equipment->update([
            'customer_id' => $customer->id,
        ]);

        return $this->successResource(new EquipmentResource($equipment->fresh('customer')));
    }

    private function resolveWalkInCustomer(Request $request): Customer
    {
        $companyId = $this->scopedCompanyId($request) ?: $request->user()?->company_id ?: $request->integer('company_id');
        abort_unless($companyId, 422, 'Debes indicar company_id para usar Cliente de Mostrador.');

        $company = Company::query()->findOrFail($companyId);
        $this->assertCompanyAccess($request, $company->id);

        return Customer::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Cliente de Mostrador',
            ],
            [
                'phone' => $company->billing_phone ?: $company->owner_phone ?: 'PENDIENTE',
                'email' => null,
                'address' => 'Mostrador',
            ]
        );
    }
}
