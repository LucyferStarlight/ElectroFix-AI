<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithCompanyScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveOrderRequest;
use App\Http\Requests\RejectOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\Api\V1\OrderDiagnosticResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Jobs\ProcessAiDiagnosticJob;
use App\Models\Order;
use App\Services\Exceptions\InvalidOrderStatusTransitionException;
use App\Services\Exceptions\OrderApprovalException;
use App\Services\Exceptions\OrderWorkflowException;
use App\Services\OrderCreationService;
use App\Services\OrderStateMachine;
use App\Services\OrderWorkflowValidator;
use App\Support\OrderStatus;
use Illuminate\Http\Request;

class OrderApiController extends Controller
{
    use ApiResponse;
    use InteractsWithCompanyScope;

    public function __construct(
        private readonly OrderCreationService $orderCreationService
    ) {}

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

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order,
        OrderStateMachine $orderStateMachine
    ) {
        $this->assertCompanyAccess($request, $order->company_id);
        $newStatus = OrderStatus::normalize((string) $request->validated('status'));

        try {
            $orderStateMachine->transition($order, $newStatus);
        } catch (InvalidOrderStatusTransitionException|OrderApprovalException $exception) {
            return response()->json([
                'ok' => false,
                'data' => null,
                'meta' => [],
                'error' => [
                    'code' => 'INVALID_STATUS_TRANSITION',
                    'message' => $exception->getMessage(),
                ],
            ], 422);
        }

        return $this->successResource(new OrderResource($order->fresh(['customer', 'equipment', 'technicianProfile'])));
    }

    public function approve(ApproveOrderRequest $request, Order $order)
    {
        $this->assertCompanyAccess($request, $order->company_id);

        try {
            $order->approve(
                $request->validated('approved_by') ?? 'customer',
                (string) $request->validated('approval_channel')
            );
        } catch (InvalidOrderStatusTransitionException|OrderApprovalException $exception) {
            return response()->json([
                'ok' => false,
                'data' => null,
                'meta' => [],
                'error' => [
                    'code' => 'ORDER_APPROVAL_FAILED',
                    'message' => $exception->getMessage(),
                ],
            ], 422);
        }

        return $this->successResource(new OrderResource($order->fresh(['customer', 'equipment', 'technicianProfile'])));
    }

    public function reject(RejectOrderRequest $request, Order $order)
    {
        $this->assertCompanyAccess($request, $order->company_id);

        try {
            $order->reject((string) $request->validated('reason'));
        } catch (InvalidOrderStatusTransitionException|OrderApprovalException $exception) {
            return response()->json([
                'ok' => false,
                'data' => null,
                'meta' => [],
                'error' => [
                    'code' => 'ORDER_REJECTION_FAILED',
                    'message' => $exception->getMessage(),
                ],
            ], 422);
        }

        return $this->successResource(new OrderResource($order->fresh(['customer', 'equipment', 'technicianProfile'])));
    }

    public function storeDiagnostic(Request $request, Order $order, OrderWorkflowValidator $orderWorkflowValidator)
    {
        $this->assertCompanyAccess($request, $order->company_id);
        $order->loadMissing('equipment', 'company.subscription.planModel');

        try {
            $orderWorkflowValidator->ensureCanDiagnose($order);
        } catch (OrderWorkflowException $exception) {
            return response()->json([
                'ok' => false,
                'data' => null,
                'meta' => [],
                'error' => [
                    'code' => 'INVALID_ORDER_WORKFLOW_ACTION',
                    'message' => $exception->getMessage(),
                ],
            ], 422);
        }

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
        $order->update(['ai_diagnosis_pending' => true, 'ai_diagnosis_error' => null]);
        ProcessAiDiagnosticJob::dispatch($order, $company, $request->user(), $symptoms);

        return response()->json([
            'ok' => true,
            'data' => null,
            'meta' => ['status' => 'processing'],
            'message' => 'Diagnóstico en proceso. Consulta GET /orders/{order}/diagnostics en unos segundos.',
        ], 202);
    }

    public function showLatestDiagnostic(Request $request, Order $order)
    {
        $this->assertCompanyAccess($request, $order->company_id);
        $order->loadMissing('latestDiagnostic');

        if ($order->ai_diagnosis_pending) {
            return response()->json([
                'ok' => true,
                'data' => null,
                'meta' => ['status' => 'processing'],
                'error' => null,
            ], 202);
        }

        if ($order->ai_diagnosis_error !== null) {
            return response()->json([
                'ok' => false,
                'data' => null,
                'meta' => [],
                'error' => [
                    'code' => 'AI_DIAGNOSTIC_ERROR',
                    'message' => $order->ai_diagnosis_error,
                ],
            ], 422);
        }

        if ($order->latestDiagnostic) {
            return $this->successResource(new OrderDiagnosticResource($order->latestDiagnostic));
        }

        return response()->json([
            'ok' => false,
            'data' => null,
            'meta' => [],
            'error' => [
                'code' => 'DIAGNOSTIC_NOT_FOUND',
                'message' => 'No existe diagnóstico IA para esta orden.',
            ],
        ], 404);
    }
}
