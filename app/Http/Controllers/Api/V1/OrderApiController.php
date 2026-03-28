<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithCompanyScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveOrderRequest;
use App\Http\Requests\IndexDiagnosticInsightsRequest;
use App\Http\Requests\RejectOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\StoreOrderDiagnosticRequest;
use App\Http\Requests\StoreSimilarCasesRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\Api\V1\OrderDiagnosticResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Jobs\ProcessAiDiagnosticJob;
use App\Models\Order;
use App\Services\Exceptions\InvalidOrderStatusTransitionException;
use App\Services\Exceptions\OrderApprovalException;
use App\Services\Exceptions\OrderWorkflowException;
use App\Services\DiagnosticCaseSearchService;
use App\Services\OrderCreationService;
use App\Services\OrderStateMachine;
use App\Services\OrderWorkflowService;
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
        } catch (InvalidOrderStatusTransitionException|OrderApprovalException|OrderWorkflowException $exception) {
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

    public function approve(
        ApproveOrderRequest $request,
        Order $order
    )
    {
        $this->assertCompanyAccess($request, $order->company_id);

        try {
            $order->approve(
                $request->validated('approved_by') ?? 'customer',
                (string) $request->validated('approval_channel')
            );
        } catch (InvalidOrderStatusTransitionException|OrderApprovalException|OrderWorkflowException $exception) {
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
        } catch (InvalidOrderStatusTransitionException|OrderApprovalException|OrderWorkflowException $exception) {
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

    public function storeDiagnostic(
        StoreOrderDiagnosticRequest $request,
        Order $order,
        OrderWorkflowService $orderWorkflowService
    )
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

        try {
            $orderWorkflowService->ensureCanDiagnose($order);
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

        $company = $order->company;
        $symptoms = (string) $request->validated('symptoms');
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

    public function similarCases(StoreSimilarCasesRequest $request, DiagnosticCaseSearchService $diagnosticCaseSearchService)
    {
        $data = $request->validated();

        $companyId = $request->user()?->company_id;
        $context = [
            'company_id' => $companyId,
            'equipment_id' => $data['equipment_id'] ?? null,
            'equipment_type' => $data['equipment_type'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
        ];

        $results = $diagnosticCaseSearchService->findSimilarCases(
            (string) $data['symptoms'],
            $context,
            (int) ($data['limit'] ?? 10)
        )->map(static function (array $row): array {
            return [
                'relevance_rank' => $row['relevance_rank'],
                'similarity_percentage' => $row['similarity_percentage'],
                'matched_keywords' => $row['matched_keywords'],
                'diagnostic' => [
                    'id' => $row['diagnostic']->id,
                    'order_id' => $row['diagnostic']->order_id,
                    'failure_type' => $row['diagnostic']->failure_type,
                    'equipment_type' => $row['diagnostic']->equipment_type,
                    'diagnostic_summary' => $row['diagnostic']->diagnostic_summary,
                    'created_at' => $row['diagnostic']->created_at?->toIso8601String(),
                ],
            ];
        });

        return $this->success($results);
    }

    public function diagnosticInsights(
        IndexDiagnosticInsightsRequest $request,
        DiagnosticCaseSearchService $diagnosticCaseSearchService
    )
    {
        $data = $request->validated();

        $companyId = $request->user()?->company_id;
        $limit = (int) ($data['limit'] ?? 10);

        return $this->success([
            'frequent_failures' => $diagnosticCaseSearchService->getFrequentFailures($companyId, $limit),
            'average_repair_cost_by_issue' => $diagnosticCaseSearchService->getAverageRepairCostByIssue($companyId, $limit),
        ]);
    }
}
