<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustInventoryStockRequest;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless($user && $user->company_id, 404, 'No se encontró empresa activa para este usuario.');

        $query = InventoryItem::query()
            ->where('company_id', $user->company_id)
            ->orderBy('name');

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('internal_code', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate(20)->withQueryString();

        $movements = InventoryMovement::query()
            ->with(['inventoryItem', 'user'])
            ->where('company_id', $user->company_id)
            ->latest()
            ->limit(12)
            ->get();

        $lowStockItems = $this->inventoryService->lowStockItemsForCompany($user->company_id);

        $unreadLowStockNotifications = $user->unreadNotifications()
            ->where('type', \App\Notifications\LowStockInventoryNotification::class)
            ->latest()
            ->limit(8)
            ->get();

        return view('worker.inventory.index', [
            'currentPage' => 'worker-inventory',
            'items' => $items,
            'movements' => $movements,
            'lowStockItems' => $lowStockItems,
            'search' => $search ?? '',
            'unreadLowStockNotifications' => $unreadLowStockNotifications,
        ]);
    }

    public function store(StoreInventoryItemRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user && $user->company, 404, 'No se encontró empresa activa para este usuario.');

        $this->inventoryService->createItem($user->company, $user, $request->validated());

        return back()->with('success', 'Producto de inventario creado correctamente.');
    }

    public function adjustStock(AdjustInventoryStockRequest $request, InventoryItem $item): RedirectResponse
    {
        $this->assertItemBelongsToUserCompany($request, $item);

        $this->inventoryService->adjustStock($item, $request->user(), $request->validated());

        return back()->with('success', 'Stock actualizado correctamente.');
    }

    public function destroy(Request $request, InventoryItem $item): RedirectResponse
    {
        $this->assertItemBelongsToUserCompany($request, $item);

        $item->delete();

        return back()->with('success', 'Producto eliminado del inventario.');
    }

    private function assertItemBelongsToUserCompany(Request $request, InventoryItem $item): void
    {
        $user = $request->user();

        if (! $user || $item->company_id !== $user->company_id) {
            abort(403, 'No puedes gestionar productos de otra empresa.');
        }
    }
}
