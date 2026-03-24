<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithCompanyScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBillingDocumentRequest;
use App\Http\Resources\Api\V1\BillingDocumentResource;
use App\Models\BillingDocument;
use App\Models\Company;
use App\Models\Customer;
use App\Services\BillingService;
use Illuminate\Http\Request;

class BillingDocumentApiController extends Controller
{
    use ApiResponse;
    use InteractsWithCompanyScope;

    public function __construct(private readonly BillingService $billingService)
    {
    }

    public function index(Request $request)
    {
        $query = BillingDocument::query()
            ->with(['items', 'customer', 'user'])
            ->latest('issued_at');

        $this->applyCompanyScope($query, $request);

        if ($type = $request->query('document_type')) {
            $query->where('document_type', $type);
        }

        if ($source = $request->query('source')) {
            $query->where('source', $source);
        }

        if ($customerId = (int) $request->query('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('issued_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('issued_at', '<=', $to);
        }

        $documents = $query->paginate((int) $request->query('per_page', 20));

        return $this->paginated(BillingDocumentResource::collection($documents), $documents);
    }

    public function store(StoreBillingDocumentRequest $request)
    {
        $user = $request->user();
        $companyId = $this->scopedCompanyId($request) ?: $request->integer('company_id');

        if (! $companyId) {
            abort(422, 'Debes indicar company_id para crear el documento.');
        }

        $this->assertCompanyAccess($request, $companyId);

        if ($request->input('customer_mode') === 'registered') {
            $customerId = $request->integer('customer_id');
            $belongs = Customer::query()
                ->where('company_id', $companyId)
                ->where('id', $customerId)
                ->exists();

            if (! $belongs) {
                abort(422, 'El cliente seleccionado no pertenece a la empresa indicada.');
            }
        }

        $company = Company::query()->findOrFail($companyId);
        $document = $this->billingService->createDocument($company, $user, array_merge(
            $request->validated(),
            $request->only([
                'repair_outcome',
                'outcome_notes',
                'work_performed',
                'actual_amount_charged',
                'diagnostic_accuracy',
                'technician_notes',
                'actual_causes',
            ])
        ));

        return $this->successResource(
            new BillingDocumentResource($document->loadMissing(['items', 'customer', 'user'])),
            status: 201
        );
    }
}
