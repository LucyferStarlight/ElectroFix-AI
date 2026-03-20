<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithCompanyScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\Api\V1\OrderDiagnosticResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\AiDiagnosticService;
use App\Services\OrderCreationService;
use App\Services\Exceptions\AiQuotaExceededException;
use Illuminate\Http\Request;

class OrderApiController extends Controller
{
    use ApiResponse;
    use InteractsWithCompanyScope;

    public function __construct(
        private readonly OrderCreationService $orderCreationService,
        private readonly AiDiagnosticService $aiDiagnosticService
    ) {
    }

    public function index(Request $request)
    {
        $query = Order::query()
            ->with(['customer', 'equipment', 'technicianProfile.user', 'latestDiagnostic'])
            ->latest('created_at');

        $this->applyCompanyScope($query, $request);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($technicianProfileId = (int) $request->query('technician_profile_id')) {
            $query->where('technician_profile_id', $technicianProfileId);
        }
        if ($customerId = (int) $request->query('customer_id')) {
            $query->where('customer_id', $customerId);
        }
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $orders = $query->paginate((int) $request->query('per_page', 20));

        return $this->paginated(OrderResource::collection($orders), $orders);
    }

    public function store(StoreOrderRequest $request)
    {
        $result = $this->orderCreationService->create($request->user(), $request->validated());
        $order = $result['order']->loadMissing(['customer', 'equipment', 'technicianProfile.user', 'latestDiagnostic']);

        return $this->successResource(
            new OrderResource($order),
            ['ai_warning' => $result['ai_warning']],
            201
        );
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $this->assertCompanyAccess($request, $order->company_id);
        $order->update(['status' => $request->validated('status')]);

        return $this->successResource(new OrderResource($order->fresh(['customer', 'equipment', 'technicianProfile'])));
    }

    public function storeDiagnostic(Request $request, Order $order)
    {
        $this->assertCompanyAccess($request, $order->company_id);
        $order->loadMissing('equipment', 'company.subscription.planModel');

        if ($order->ai_diagnosed_at) {
            return response()->json([
                'ok' => false,
                'data' => null,
                'meta' => [],
                'error' => [
                    'code' => 'DIAGNOSTIC_ALREADY_EXISTS',
                    'message' => 'Esta orden ya cuenta con un diagnóstico IA.',
                ],
            ], 422);
        }

        $data = $request->validate([
            'symptoms' => ['required', 'string', 'min:5', 'max:600'],
        ]);

        $company = $order->company;
        $symptoms = (string) $data['symptoms'];
        try {
            $this->aiDiagnosticService->diagnose($order, $company, $request->user(), $symptoms);
        } catch (AiQuotaExceededException $exception) {
            return response()->json([
                'ok' => false,
                'data' => null,
                'meta' => [],
                'error' => [
                    'code' => strtoupper($exception->status()),
                    'message' => $exception->getMessage(),
                ],
            ], 422);
        }

        $order->loadMissing('latestDiagnostic');

        return $this->successResource(new OrderDiagnosticResource($order->latestDiagnostic), status: 201);
    }
}
