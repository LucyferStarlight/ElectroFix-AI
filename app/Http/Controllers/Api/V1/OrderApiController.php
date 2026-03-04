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
use App\Services\AiTokenEstimator;
use App\Services\AiUsageService;
use App\Services\Exceptions\AiUsageException;
use App\Services\OrderCreationService;
use App\Services\OrderDiagnosticService;
use Illuminate\Http\Request;

class OrderApiController extends Controller
{
    use ApiResponse;
    use InteractsWithCompanyScope;

    public function __construct(
        private readonly OrderCreationService $orderCreationService,
        private readonly AiDiagnosticService $aiDiagnosticService,
        private readonly AiTokenEstimator $aiTokenEstimator,
        private readonly AiUsageService $aiUsageService,
        private readonly OrderDiagnosticService $orderDiagnosticService
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
        $order->loadMissing('equipment', 'company.subscription');

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
        $plan = (string) ($company->subscription?->plan ?? 'starter');
        $symptoms = (string) $data['symptoms'];
        $prompt = sprintf(
            'Equipo: %s %s %s. Síntomas: %s',
            $order->equipment?->type,
            $order->equipment?->brand,
            $order->equipment?->model ?? '',
            $symptoms
        );
        $promptChars = mb_strlen($prompt);
        $promptTokens = $this->aiTokenEstimator->estimateFromChars($promptChars);

        try {
            $this->aiUsageService->validateBeforeUsage($company, $plan, $promptTokens);
        } catch (AiUsageException $exception) {
            $this->aiUsageService->registerBlocked(
                $company,
                $order,
                $plan,
                $exception->status(),
                $exception->getMessage(),
                $promptChars
            );

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

        $analysis = $this->aiDiagnosticService->analyze(
            (string) $order->equipment?->type,
            (string) $order->equipment?->brand,
            $order->equipment?->model,
            $symptoms
        );

        $completionChars = mb_strlen((string) json_encode($analysis, JSON_UNESCAPED_UNICODE));
        $completionTokens = $this->aiTokenEstimator->estimateFromChars($completionChars);
        $totalTokens = $promptTokens + $completionTokens;

        try {
            $this->aiUsageService->validateAfterUsage($company, $plan, $totalTokens);
        } catch (AiUsageException $exception) {
            $this->aiUsageService->registerBlocked(
                $company,
                $order,
                $plan,
                $exception->status(),
                $exception->getMessage(),
                $promptChars,
                $completionChars
            );

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

        $diagnostic = $this->orderDiagnosticService->createFromAi(
            $order,
            $request->user(),
            $analysis,
            $promptTokens,
            $completionTokens,
            $symptoms
        );

        $order->update([
            'symptoms' => $symptoms,
            'ai_potential_causes' => $analysis['possible_causes'] ?? [],
            'ai_estimated_time' => $analysis['estimated_time'] ?? null,
            'ai_suggested_parts' => $analysis['suggested_parts'] ?? [],
            'ai_technical_advice' => $analysis['technical_advice'] ?? null,
            'ai_diagnosed_at' => now(),
            'ai_tokens_used' => $totalTokens,
            'ai_provider' => $analysis['provider'] ?? 'local_stub',
            'ai_model' => $analysis['model'] ?? 'heuristic-v2',
            'ai_requires_parts_replacement' => (bool) ($analysis['requires_parts_replacement'] ?? false),
            'ai_cost_repair_labor' => (float) ($analysis['cost_suggestion']['repair_labor_cost'] ?? 0),
            'ai_cost_replacement_parts' => (float) ($analysis['cost_suggestion']['replacement_parts_cost'] ?? 0),
            'ai_cost_replacement_total' => (float) ($analysis['cost_suggestion']['replacement_total_cost'] ?? 0),
        ]);

        $this->aiUsageService->registerSuccess($company, $order, $plan, $promptChars, $completionChars);

        return $this->successResource(new OrderDiagnosticResource($diagnostic), status: 201);
    }
}

