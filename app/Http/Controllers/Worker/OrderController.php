<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveOrderRequest;
use App\Http\Requests\RejectOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\TechnicianProfile;
use App\Services\AiPlanPolicyService;
use App\Services\AiUsageService;
use App\Services\Exceptions\InvalidOrderStatusTransitionException;
use App\Services\Exceptions\OrderApprovalException;
use App\Services\Exceptions\OrderWorkflowException;
use App\Services\Exceptions\OutcomeNotFoundException;
use App\Services\OrderCreationService;
use App\Services\OrderCustomerNotificationService;
use App\Services\OrderStateMachine;
use App\Services\OrderWorkflowValidator;
use App\Services\RepairOutcomeService;
use App\Support\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request, AiPlanPolicyService $aiPlanPolicyService, AiUsageService $aiUsageService)
    {
        $user = $request->user();
        $orders = Order::query()
            ->with(['customer', 'equipment', 'billingItems', 'repairOutcome'])
            ->orderByDesc('created_at');

        $customers = Customer::query()->orderBy('name');
        $equipments = Equipment::query()->orderByDesc('created_at');
        $companyTechnicians = collect();

        if ($user->role !== 'developer') {
            $companyId = $user->company_id;
            $orders->where('company_id', $companyId);
            $customers->where('company_id', $companyId);
            $equipments->where('company_id', $companyId);

            $companyTechnicians = TechnicianProfile::query()
                ->where('company_id', $companyId)
                ->where('is_assignable', true)
                ->whereIn('status', ['available', 'assigned'])
                ->with('user:id,role')
                ->orderBy('display_name')
                ->get(['id', 'display_name', 'user_id', 'status']);
        }

        $plan = (string) ($user?->company?->subscription?->plan ?? 'starter');
        $aiEnabled = $aiPlanPolicyService->supportsAi($plan);
        $monthlyUsage = $user?->company
            ? $aiUsageService->monthlyUsage($user->company)
            : ['queries_used' => 0, 'tokens_used' => 0];

        if ($search = trim((string) $request->query('search'))) {
            $orders->where(function ($q) use ($search): void {
                $q->where('id', $search)
                    ->orWhere('technician', 'like', "%{$search}%")
                    ->orWhereHas('technicianProfile', function ($tq) use ($search): void {
                        $tq->where('display_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($cq) use ($search): void {
                        $cq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('equipment', function ($eq) use ($search): void {
                        $eq->where('brand', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%");
                    });
            });
        }

        return view('worker.orders.index', [
            'currentPage' => 'worker-orders',
            'orders' => $orders->paginate(18)->withQueryString(),
            'customers' => $customers->get(),
            'equipments' => $equipments->get(),
            'search' => $search ?? '',
            'statuses' => OrderStatus::all(),
            'companyTechnicians' => $companyTechnicians,
            'aiPlan' => $plan,
            'aiEnabled' => $aiEnabled,
            'aiQueryLimit' => $aiPlanPolicyService->queryLimit($plan),
            'aiTokenLimit' => $aiPlanPolicyService->tokenLimit($plan),
            'aiQueriesUsed' => $monthlyUsage['queries_used'],
            'aiTokensUsed' => $monthlyUsage['tokens_used'],
        ]);
    }

    public function store(
        StoreOrderRequest $request,
        OrderCreationService $orderCreationService,
        OrderCustomerNotificationService $orderCustomerNotificationService
    ): RedirectResponse {
        $result = $orderCreationService->create($request->user(), $request->validated());
        $warning = $result['ai_warning'] ?? null;
        $order = $result['order'] ?? null;

        if ($order instanceof Order) {
            $orderCustomerNotificationService->sendCreated($order);
        }

        $response = back()->with('success', 'Orden creada exitosamente.');
        if ($warning) {
            $response->with('warning', $warning);
        }

        return $response;
    }

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order,
        OrderStateMachine $orderStateMachine,
        OrderCustomerNotificationService $orderCustomerNotificationService
    ): RedirectResponse {
        $this->authorizeOrder($request, $order);
        $previousStatus = (string) $order->status;
        $newStatus = OrderStatus::normalize((string) $request->validated('status'));

        try {
            $orderStateMachine->transition($order, $newStatus);
        } catch (InvalidOrderStatusTransitionException|OrderApprovalException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        if ($previousStatus !== $newStatus) {
            $orderCustomerNotificationService->sendStatusChanged($order->fresh(), $previousStatus, $newStatus);
        }

        return back()->with('success', 'Estado de orden actualizado.');
    }

    public function approve(ApproveOrderRequest $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        try {
            $order->approve(
                $request->validated('approved_by') ?? 'customer',
                (string) $request->validated('approval_channel')
            );
        } catch (InvalidOrderStatusTransitionException|OrderApprovalException $exception) {
            return back()->withErrors(['approval' => $exception->getMessage()]);
        }

        return back()->with('success', 'Orden aprobada correctamente.');
    }

    public function reject(RejectOrderRequest $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        try {
            $order->reject((string) $request->validated('reason'));
        } catch (InvalidOrderStatusTransitionException|OrderApprovalException $exception) {
            return back()->withErrors(['approval' => $exception->getMessage()]);
        }

        return back()->with('success', 'Orden rechazada correctamente.');
    }

    public function deliver(
        Request $request,
        Order $order,
        OrderWorkflowValidator $orderWorkflowValidator,
        RepairOutcomeService $repairOutcomeService,
        OrderCustomerNotificationService $orderCustomerNotificationService
    ): RedirectResponse {
        $this->authorizeOrder($request, $order);
        $order->loadMissing('billingItems', 'repairOutcome');

        try {
            $orderWorkflowValidator->ensureCanDeliver($order);
        } catch (OrderWorkflowException $exception) {
            abort(422, $exception->getMessage());
        }

        try {
            $outcome = $repairOutcomeService->markDelivered($order, $request->user());
        } catch (OutcomeNotFoundException $exception) {
            abort(422, $exception->getMessage());
        }

        if (! $outcome->delivered_at) {
            abort(422, 'No se pudo registrar la entrega de la orden.');
        }

        $orderCustomerNotificationService->sendDelivered($order->fresh());

        return back()->with('success', 'Orden marcada como entregada al cliente.');
    }

    public function diagnose(Request $request): JsonResponse
    {
        $data = $request->validate([
            'equipment_id' => ['required', 'integer', 'exists:equipments,id'],
            'symptoms' => ['required', 'string', 'min:5', 'max:600'],
        ]);

        $equipment = Equipment::query()->findOrFail($data['equipment_id']);

        if ($request->user()->role !== 'developer' && $equipment->company_id !== $request->user()->company_id) {
            abort(403, 'No puedes analizar equipos fuera de tu empresa.');
        }

        $order = Order::query()
            ->where('equipment_id', $equipment->id)
            ->latest('id')
            ->first();

        if ($order) {
            try {
                app(OrderWorkflowValidator::class)->ensureCanDiagnose($order);
            } catch (OrderWorkflowException $exception) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 422);
            }
        }

        return response()->json([
            'message' => 'La consulta directa está deshabilitada. Activa "Solicitar diagnóstico IA" y guarda la orden.',
        ], 422);
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        if ($request->user()->role !== 'developer' && $order->company_id !== $request->user()->company_id) {
            abort(403, 'No puedes modificar esta orden.');
        }
    }
}
