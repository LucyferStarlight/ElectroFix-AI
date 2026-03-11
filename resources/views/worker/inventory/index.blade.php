@extends('layouts.app')

@section('title', 'Inventario | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Inventario de Piezas y Refacciones</h1>
            <p class="text-muted mb-0">Control de stock para venta directa y uso en reparaciones.</p>
        </div>
        <button class="btn btn-ui btn-primary-ui" data-bs-toggle="modal" data-bs-target="#newItemModal">Agregar Producto</button>
    </div>

    @if($lowStockItems->isNotEmpty())
        <div class="card card-ui mb-4 border-warning-subtle">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 fw-bold mb-0">Alertas por escasez</h2>
                    <span class="badge badge-ui badge-ui-warning">{{ $lowStockItems->count() }} producto(s)</span>
                </div>
                <p class="text-muted mb-2">Se notificó automáticamente a administradores y trabajadores autorizados.</p>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($lowStockItems as $stockItem)
                        <span class="badge badge-ui badge-ui-warning">
                            {{ $stockItem->name }} ({{ $stockItem->internal_code }}) - {{ $stockItem->quantity }} und.
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if($unreadLowStockNotifications->isNotEmpty())
        <div class="card card-ui mb-4">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">Notificaciones de inventario</h2>
                <ul class="mb-0 ps-3">
                    @foreach($unreadLowStockNotifications as $notification)
                        <li class="mb-1">{{ data_get($notification->data, 'message', 'Alerta de escasez detectada.') }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="card card-ui mb-4">
        <div class="card-body d-flex gap-3 flex-wrap align-items-center">
            <form method="get" class="d-flex gap-2 flex-grow-1">
                <input class="form-control input-ui" type="text" name="search" value="{{ $search }}" placeholder="Buscar por nombre o identificador interno...">
                <button class="btn btn-ui btn-outline-ui" type="submit">Buscar</button>
            </form>
            <span class="badge badge-ui badge-ui-info">{{ $items->total() }} productos</span>
        </div>
    </div>

    <div class="card card-ui mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-ui align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="px-3">Producto</th>
                            <th>Identificador</th>
                            <th>Stock</th>
                            <th>Venta</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td class="px-3">
                                    <div class="fw-semibold">{{ $item->name }}</div>
                                    <small class="text-muted">Umbral mínimo: {{ $item->low_stock_threshold }} und.</small>
                                </td>
                                <td><span class="badge badge-ui badge-ui-info text-monospace">{{ $item->internal_code }}</span></td>
                                <td>
                                    <span class="fw-semibold {{ $item->isLowStock() ? 'text-danger' : '' }}">{{ $item->quantity }}</span>
                                    @if($item->isLowStock())
                                        <span class="badge badge-ui badge-ui-warning ms-1">Bajo</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->is_sale_enabled)
                                        <span class="badge badge-ui badge-ui-success">Venta habilitada</span>
                                        <div class="small mt-1">${{ number_format((float) $item->sale_price, 2) }}</div>
                                    @else
                                        <span class="badge badge-ui badge-ui-info">Solo refacción</span>
                                    @endif
                                </td>
                                <td class="pe-3">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button class="btn btn-ui btn-outline-ui btn-sm" data-bs-toggle="modal" data-bs-target="#stockModal{{ $item->id }}">Ajustar stock</button>
                                        <form method="post" action="{{ route('worker.inventory.destroy', $item) }}" onsubmit="return confirm('¿Eliminar este producto del inventario?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-ui btn-sm btn-danger" type="submit">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="stockModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content card-ui">
                                        <form method="post" action="{{ route('worker.inventory.stock', $item) }}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="modal-header border-0">
                                                <h5 class="modal-title fw-bold">Ajustar stock: {{ $item->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label">Movimiento</label>
                                                        <select class="form-select input-ui" name="movement_type" required>
                                                            <option value="addition">Agregar unidades</option>
                                                            <option value="removal">Retirar unidades</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Cantidad</label>
                                                        <input class="form-control input-ui" type="number" name="quantity" min="1" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Stock actual</label>
                                                        <input class="form-control input-ui" value="{{ $item->quantity }}" readonly>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Nota (opcional)</label>
                                                        <input class="form-control input-ui" name="notes" maxlength="255" placeholder="Motivo del ajuste">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button class="btn btn-ui btn-outline-ui" type="button" data-bs-dismiss="modal">Cancelar</button>
                                                <button class="btn btn-ui btn-primary-ui" type="submit">Guardar ajuste</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-5">Sin productos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $items->links() }}</div>

    <div class="card card-ui">
        <div class="card-body p-0">
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                <h2 class="h6 fw-bold mb-0">Movimientos recientes de stock</h2>
                <span class="badge badge-ui badge-ui-info">Últimos {{ $movements->count() }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-ui align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="px-3">Fecha</th>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Stock</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($movements as $movement)
                        <tr>
                            <td class="px-3">{{ $movement->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ $movement->inventoryItem->name ?? 'Producto eliminado' }}</td>
                            <td>
                                @if($movement->movement_type === 'addition')
                                    <span class="badge badge-ui badge-ui-success">Entrada</span>
                                @else
                                    <span class="badge badge-ui badge-ui-warning">Salida</span>
                                @endif
                            </td>
                            <td>{{ $movement->quantity }}</td>
                            <td>{{ $movement->stock_before }} → {{ $movement->stock_after }}</td>
                            <td>{{ $movement->user?->name ?? 'Sistema' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Sin movimientos registrados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="newItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content card-ui">
            <form method="post" action="{{ route('worker.inventory.store') }}">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Nuevo producto de inventario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nombre *</label><input class="form-control input-ui" name="name" required></div>
                        <div class="col-md-6"><label class="form-label">Identificador interno *</label><input class="form-control input-ui" name="internal_code" required placeholder="REF-001"></div>
                        <div class="col-md-4"><label class="form-label">Cantidad inicial *</label><input class="form-control input-ui" type="number" min="0" name="quantity" value="0" required></div>
                        <div class="col-md-4"><label class="form-label">Umbral de escasez</label><input class="form-control input-ui" type="number" min="0" name="low_stock_threshold" value="5"></div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="1" id="saleEnabled" name="is_sale_enabled">
                                <label class="form-check-label" for="saleEnabled">Habilitar venta</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Precio de venta</label>
                            <input class="form-control input-ui" type="number" step="0.01" min="0" name="sale_price" id="salePriceInput" placeholder="0.00" disabled>
                            <small class="text-muted">Solo aplica cuando venta está habilitada.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button class="btn btn-ui btn-outline-ui" type="button" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-ui btn-primary-ui" type="submit">Guardar producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(() => {
    const saleEnabled = document.getElementById('saleEnabled');
    const salePriceInput = document.getElementById('salePriceInput');

    if (!saleEnabled || !salePriceInput) return;

    const togglePrice = () => {
        salePriceInput.disabled = !saleEnabled.checked;
        if (salePriceInput.disabled) {
            salePriceInput.value = '';
        }
    };

    saleEnabled.addEventListener('change', togglePrice);
    togglePrice();
})();
</script>
@endsection
