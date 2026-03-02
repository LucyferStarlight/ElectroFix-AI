@extends('layouts.app')

@section('title', 'Órdenes | ElectroFix-AI')

@section('content')
@php($statusLabels = ['received' => 'Recibido', 'diagnostic' => 'Diagnóstico', 'repairing' => 'Reparación', 'quote' => 'Cotización', 'ready' => 'Listo', 'delivered' => 'Entregado', 'not_repaired' => 'No reparado'])
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Órdenes de Trabajo</h1>
            <p class="text-muted mb-0">Gestión de reparaciones, diagnóstico y estados de servicio.</p>
        </div>
        <button class="btn btn-ui btn-primary-ui" data-bs-toggle="modal" data-bs-target="#orderCreateModal">Nueva Orden</button>
    </div>

    <div class="card card-ui mb-4">
        <div class="card-body d-flex gap-3 flex-wrap align-items-center">
            <form method="get" class="d-flex gap-2 flex-grow-1">
                <input class="form-control input-ui" type="text" name="search" value="{{ $search }}" placeholder="Buscar por ID, técnico, cliente o equipo...">
                <button class="btn btn-ui btn-outline-ui" type="submit">Buscar</button>
            </form>
            <span class="badge badge-ui badge-ui-info">{{ $orders->total() }} órdenes</span>
        </div>
    </div>

    <div class="row g-3">
        @forelse($orders as $order)
            <div class="col-md-6 col-xl-4">
                <div class="card card-ui h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge badge-ui badge-ui-info">#{{ str_pad((string) $order->id, 6, '0', STR_PAD_LEFT) }}</span>
                            <span class="badge badge-ui {{ in_array($order->status, ['ready','delivered'], true) ? 'badge-ui-success' : (in_array($order->status, ['quote','not_repaired'], true) ? 'badge-ui-warning' : 'badge-ui-info') }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                        </div>
                        <h2 class="h5 fw-bold">{{ $order->equipment->brand }} {{ $order->equipment->type }}</h2>
                        <p class="text-muted mb-2">{{ $order->equipment->model ?: 'Modelo no definido' }}</p>
                        <p class="mb-1"><strong>Cliente:</strong> {{ $order->customer->name }}</p>
                        <p class="mb-3"><strong>Técnico:</strong> {{ $order->technician }}</p>
                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <small class="text-muted">{{ $order->created_at->format('Y-m-d') }}</small>
                            <button class="btn btn-ui btn-outline-ui btn-sm" data-bs-toggle="modal" data-bs-target="#orderDetail{{ $order->id }}">Detalles</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="orderDetail{{ $order->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content card-ui">
                        <div class="modal-header border-0">
                            <h5 class="modal-title fw-bold">Orden #{{ str_pad((string) $order->id, 6, '0', STR_PAD_LEFT) }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-4">
                                <div class="col-lg-5">
                                    <div class="card card-ui h-100"><div class="card-body">
                                        <h6 class="fw-bold">Cambiar Estado</h6>
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            @foreach($statuses as $status)
                                                <form method="post" action="{{ route('worker.orders.status', $order) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="{{ $status }}">
                                                    <button class="btn btn-ui btn-sm {{ $order->status === $status ? 'btn-primary-ui' : 'btn-outline-ui' }}" type="submit">{{ $statusLabels[$status] ?? $status }}</button>
                                                </form>
                                            @endforeach
                                        </div>

                                        <h6 class="fw-bold">Cliente y Equipo</h6>
                                        <p class="mb-1"><strong>Cliente:</strong> {{ $order->customer->name }}</p>
                                        <p class="mb-1"><strong>Email:</strong> {{ $order->customer->email }}</p>
                                        <p class="mb-1"><strong>Equipo:</strong> {{ $order->equipment->brand }} {{ $order->equipment->model }}</p>
                                        <p class="mb-3"><strong>Serie:</strong> {{ $order->equipment->serial_number ?: 'N/A' }}</p>

                                        <h6 class="fw-bold">Tiempos</h6>
                                        <p class="mb-1"><strong>Entrada:</strong> {{ $order->created_at->format('Y-m-d H:i') }}</p>
                                        <p class="mb-0"><strong>Actualizado:</strong> {{ $order->updated_at->format('Y-m-d H:i') }}</p>
                                    </div></div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="card card-ui h-100"><div class="card-body">
                                        <h6 class="fw-bold">Reporte de Fallas</h6>
                                        <p class="text-muted">{{ $order->symptoms ?: 'Sin síntomas registrados.' }}</p>

                                        <h6 class="fw-bold mt-4">Presupuesto</h6>
                                        <div class="p-3 rounded bg-dark text-white fw-bold fs-4">${{ number_format((float) $order->estimated_cost, 2) }}</div>

                                        <h6 class="fw-bold mt-4">Análisis AI</h6>
                                        <p class="mb-1"><strong>Causas probables:</strong></p>
                                        <ul>
                                            @forelse($order->ai_potential_causes ?? [] as $cause)
                                                <li>{{ $cause }}</li>
                                            @empty
                                                <li>Sin análisis guardado.</li>
                                            @endforelse
                                        </ul>
                                        <p class="mb-1"><strong>Tiempo estimado:</strong> {{ $order->ai_estimated_time ?: 'N/A' }}</p>
                                        <p class="mb-1"><strong>Repuestos sugeridos:</strong>
                                            @if(!empty($order->ai_suggested_parts))
                                                {{ implode(', ', $order->ai_suggested_parts) }}
                                            @else
                                                N/A
                                            @endif
                                        </p>
                                        <p class="mb-0"><strong>Consejo técnico:</strong> {{ $order->ai_technical_advice ?: 'N/A' }}</p>
                                    </div></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button class="btn btn-ui btn-outline-ui" type="button" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card card-ui"><div class="card-body text-center text-muted py-5">No se encontraron órdenes.</div></div></div>
        @endforelse
    </div>

    <div class="mt-3">{{ $orders->links() }}</div>
</div>

<div class="modal fade" id="orderCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content card-ui">
            <form method="post" action="{{ route('worker.orders.store') }}" id="orderCreateForm">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Nueva Orden de Trabajo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Cliente *</label>
                                    <select class="form-select input-ui" name="customer_id" id="orderCustomer" required>
                                        <option value="">Seleccionar cliente...</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Equipo *</label>
                                    <select class="form-select input-ui" name="equipment_id" id="orderEquipment" required>
                                        <option value="">Seleccionar equipo...</option>
                                        @foreach($equipments as $equipment)
                                            <option value="{{ $equipment->id }}" data-customer-id="{{ $equipment->customer_id }}">{{ $equipment->brand }} - {{ $equipment->model }} ({{ $equipment->type }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Síntomas reportados</label>
                                    <textarea class="form-control input-ui" rows="4" name="symptoms" id="orderSymptoms" placeholder="Describe los problemas..."></textarea>
                                </div>
                                <div class="col-md-6"><label class="form-label">Costo estimado</label><input class="form-control input-ui" type="number" step="0.01" min="0" name="estimated_cost"></div>
                                <div class="col-md-6"><label class="form-label">Técnico asignado *</label><input class="form-control input-ui" name="technician" required></div>
                                <div class="col-12">
                                    <button class="btn btn-ui btn-outline-ui w-100" id="aiDiagnoseBtn" type="button">Consultar Asistente AI</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card card-ui h-100">
                                <div class="card-body" id="aiPanel">
                                    <h6 class="fw-bold">Análisis AI</h6>
                                    <p class="text-muted">Usa el asistente para obtener causas probables, tiempo estimado y repuestos sugeridos.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button class="btn btn-ui btn-outline-ui" type="button" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-ui btn-primary-ui" type="submit">Guardar Orden</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(() => {
    const customerSelect = document.getElementById('orderCustomer');
    const equipmentSelect = document.getElementById('orderEquipment');
    const aiBtn = document.getElementById('aiDiagnoseBtn');
    const aiPanel = document.getElementById('aiPanel');

    if (customerSelect && equipmentSelect) {
        customerSelect.addEventListener('change', function () {
            const customerId = this.value;
            for (const option of equipmentSelect.options) {
                if (!option.value) continue;
                option.hidden = customerId && option.dataset.customerId !== customerId;
            }
            equipmentSelect.value = '';
        });
    }

    if (aiBtn && aiPanel) {
        aiBtn.addEventListener('click', async function () {
            const equipmentId = equipmentSelect.value;
            const symptoms = document.getElementById('orderSymptoms').value;

            if (!equipmentId || !symptoms || symptoms.length < 5) {
                aiPanel.innerHTML = '<h6 class="fw-bold">Análisis AI</h6><p class="text-danger mb-0">Selecciona equipo y describe síntomas (mínimo 5 caracteres).</p>';
                return;
            }

            aiPanel.innerHTML = '<h6 class="fw-bold">Análisis AI</h6><p class="text-muted mb-0">Consultando...</p>';

            try {
                const response = await fetch("{{ route('worker.orders.diagnose') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ equipment_id: equipmentId, symptoms })
                });

                if (!response.ok) throw new Error('No se pudo obtener diagnóstico.');
                const data = await response.json();

                aiPanel.innerHTML = `
                    <h6 class="fw-bold">Análisis AI (${data.equipment})</h6>
                    <p class="mb-1"><strong>Posibles causas:</strong></p>
                    <ul>${(data.potential_causes || []).map(c => `<li>${c}</li>`).join('')}</ul>
                    <p class="mb-1"><strong>Tiempo estimado:</strong> ${data.estimated_time || 'N/A'}</p>
                    <p class="mb-1"><strong>Repuestos sugeridos:</strong> ${(data.suggested_parts || []).join(', ') || 'N/A'}</p>
                    <p class="mb-0"><strong>Consejo técnico:</strong> ${data.technical_advice || 'N/A'}</p>
                `;
            } catch (error) {
                aiPanel.innerHTML = '<h6 class="fw-bold">Análisis AI</h6><p class="text-danger mb-0">Error al consultar el asistente.</p>';
            }
        });
    }
})();
</script>
@endsection
