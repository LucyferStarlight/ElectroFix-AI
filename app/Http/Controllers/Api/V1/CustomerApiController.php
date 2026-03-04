<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithCompanyScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerApiController extends Controller
{
    use ApiResponse;
    use InteractsWithCompanyScope;

    public function index(Request $request)
    {
        $query = Customer::query()->orderByDesc('created_at');
        $this->applyCompanyScope($query, $request);

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate((int) $request->query('per_page', 20));

        return $this->paginated(CustomerResource::collection($customers), $customers);
    }

    public function store(StoreCustomerRequest $request)
    {
        $user = $request->user();
        $companyId = $this->scopedCompanyId($request) ?: $request->integer('company_id');
        if (! $companyId) {
            abort(422, 'Debes indicar company_id para crear cliente.');
        }
        $this->assertCompanyAccess($request, $companyId);

        $customer = Customer::query()->create([
            ...$request->validated(),
            'company_id' => $companyId,
        ]);

        return $this->successResource(new CustomerResource($customer), status: 201);
    }
}

