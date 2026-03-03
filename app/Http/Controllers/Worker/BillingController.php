<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBillingDocumentRequest;
use App\Models\BillingDocument;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Support\OrderStatus;
use App\Services\BillingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(private readonly BillingService $billingService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->company_id, 404, 'No se encontró empresa activa para este usuario.');

        $documents = BillingDocument::query()
            ->with(['customer', 'user'])
            ->where('company_id', $user->company_id)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $customers = Customer::query()
            ->where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        $inventoryItems = InventoryItem::query()
            ->where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        return view('worker.billing.index', [
            'currentPage' => 'worker-billing',
            'documents' => $documents,
            'customers' => $customers,
            'inventoryItems' => $inventoryItems,
            'company' => $user->company,
            'orderStatuses' => OrderStatus::all(),
        ]);
    }

    public function store(StoreBillingDocumentRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->company, 404, 'No se encontró empresa activa para este usuario.');

        if ($request->input('customer_mode') === 'registered') {
            $customer = Customer::query()->where('company_id', $user->company_id)->find($request->integer('customer_id'));
            if (! $customer) {
                abort(422, 'El cliente seleccionado no pertenece a tu empresa.');
            }
        }

        $document = $this->billingService->createDocument($user->company, $user, $request->validated());

        return redirect()
            ->route('worker.billing.show', $document)
            ->with('success', 'Documento generado correctamente.');
    }

    public function show(Request $request, BillingDocument $document)
    {
        $this->authorizeDocument($request, $document);

        $document->load(['items.inventoryItem', 'items.order', 'customer', 'company', 'user']);

        return view('worker.billing.show', [
            'currentPage' => 'worker-billing',
            'document' => $document,
        ]);
    }

    public function pdf(Request $request, BillingDocument $document)
    {
        $this->authorizeDocument($request, $document);

        $document->load(['items.inventoryItem', 'items.order', 'customer', 'company', 'user']);

        $pdf = Pdf::loadView('worker.billing.pdf', [
            'document' => $document,
        ])->setPaper('a4');

        return $pdf->download($document->document_number.'.pdf');
    }

    public function customerServices(Request $request, Customer $customer): JsonResponse
    {
        $user = $request->user();

        if (! $user || ($user->role !== 'developer' && $customer->company_id !== $user->company_id)) {
            abort(403, 'No puedes consultar servicios de este cliente.');
        }

        $orders = Order::query()
            ->where('company_id', $customer->company_id)
            ->where('customer_id', $customer->id)
            ->with('equipment')
            ->latest()
            ->get()
            ->map(function (Order $order): array {
                return [
                    'id' => $order->id,
                    'description' => $order->symptoms ?: 'Servicio sin descripción',
                    'estimated_cost' => (float) $order->estimated_cost,
                    'status' => $order->status,
                    'status_label' => OrderStatus::label($order->status),
                    'equipment' => trim(($order->equipment->brand ?? '').' '.($order->equipment->model ?? '')),
                ];
            });

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
            ],
            'orders' => $orders,
        ]);
    }

    private function authorizeDocument(Request $request, BillingDocument $document): void
    {
        $user = $request->user();

        if (! $user || ($user->role !== 'developer' && $document->company_id !== $user->company_id)) {
            abort(403, 'No puedes acceder a este documento.');
        }
    }
}
