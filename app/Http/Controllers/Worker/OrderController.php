<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Services\AiDiagnosticService;
use App\Support\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->with(['customer', 'equipment'])
            ->orderByDesc('created_at');

        $customers = Customer::query()->orderBy('name');
        $equipments = Equipment::query()->orderByDesc('created_at');

        if ($request->user()->role !== 'developer') {
            $companyId = $request->user()->company_id;
            $orders->where('company_id', $companyId);
            $customers->where('company_id', $companyId);
            $equipments->where('company_id', $companyId);
        }

        if ($search = trim((string) $request->query('search'))) {
            $orders->where(function ($q) use ($search): void {
                $q->where('id', $search)
                    ->orWhere('technician', 'like', "%{$search}%")
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
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $customer = Customer::query()->findOrFail($request->integer('customer_id'));
        $equipment = Equipment::query()->findOrFail($request->integer('equipment_id'));

        if ($customer->company_id !== $equipment->company_id) {
            abort(422, 'Cliente y equipo no pertenecen a la misma empresa.');
        }

        if ($request->user()->role !== 'developer' && $customer->company_id !== $request->user()->company_id) {
            abort(403, 'No puedes crear órdenes fuera de tu empresa.');
        }

        Order::query()->create([
            ...$request->validated(),
            'company_id' => $customer->company_id,
            'status' => $request->input('status', OrderStatus::RECEIVED),
            'estimated_cost' => $request->input('estimated_cost', 0),
        ]);

        return back()->with('success', 'Orden creada exitosamente.');
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order);
        $order->update(['status' => $request->validated('status')]);

        return back()->with('success', 'Estado de orden actualizado.');
    }

    public function diagnose(Request $request, AiDiagnosticService $aiDiagnosticService): JsonResponse
    {
        $data = $request->validate([
            'equipment_id' => ['required', 'integer', 'exists:equipments,id'],
            'symptoms' => ['required', 'string', 'min:5'],
        ]);

        $equipment = Equipment::query()->findOrFail($data['equipment_id']);

        if ($request->user()->role !== 'developer' && $equipment->company_id !== $request->user()->company_id) {
            abort(403, 'No puedes analizar equipos fuera de tu empresa.');
        }

        $analysis = $aiDiagnosticService->analyze(
            $equipment->type,
            $equipment->brand,
            $equipment->model,
            $data['symptoms']
        );

        return response()->json($analysis);
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        if ($request->user()->role !== 'developer' && $order->company_id !== $request->user()->company_id) {
            abort(403, 'No puedes modificar esta orden.');
        }
    }
}
