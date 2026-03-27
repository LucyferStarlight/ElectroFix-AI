@extends('layouts.app')

@section('title', 'Órdenes | ElectroFix-AI')

@section('content')
@php($statusLabels = ['received' => 'Recibido', 'diagnostic' => 'Diagnóstico', 'repairing' => 'Reparación', 'quote' => 'Cotización', 'ready' => 'Listo', 'delivered' => 'Entregado', 'not_repaired' => 'No reparado'])
@php($currentUser = auth()->user())
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
            <span class="badge badge-ui {{ $aiEnabled ? 'badge-ui-success' : 'badge-ui-warning' }}">
                IA: {{ $aiEnabled ? 'habilitada' : 'no disponible en plan '.strtoupper($aiPlan) }}
            </span>
            @if($aiEnabled)
                <span class="badge badge-ui badge-ui-info">Consultas IA {{ $aiQueriesUsed }}/{{ $aiQueryLimit }}</span>
                <span class="badge badge-ui badge-ui-warning">Tokens IA {{ number_format($aiTokensUsed) }}/{{ number_format($aiTokenLimit) }}</span>
            @endif
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
                                        <p class="mb-1"><strong>Teléfono:</strong> {{ $order->customer->phone }}</p>
                                        <p class="mb-1"><strong>Correo:</strong> {{ $order->customer->email ?: 'No registrado' }}</p>
                                        <p class="mb-1"><strong>Equipo:</strong> {{ $order->equipment->brand }} {{ $order->equipment->model }}</p>
                                        <p class="mb-3"><strong>Serie:</strong> {{ $order->equipment->serial_number ?: 'N/A' }}</p>

                                        <h6 class="fw-bold">Tiempos</h6>
                                        <p class="mb-1"><strong>Entrada:</strong> {{ $order->created_at->format('Y-m-d H:i') }}</p>
                                        <p class="mb-0"><strong>Actualizado:</strong> {{ $order->updated_at->format('Y-m-d H:i') }}</p>

                                        <h6 class="fw-bold mt-4">Entrega al cliente</h6>
                                        @if($order->repairOutcome?->delivered_at)
                                            <p class="mb-2"><strong>Entregada:</strong> {{ $order->repairOutcome->delivered_at->format('Y-m-d H:i') }}</p>
                                        @else
                                            <p class="text-muted mb-2">Aún no marcada como entregada.</p>
                                        @endif

                                        @if($order->billingItems->isNotEmpty() && $order->repairOutcome && !$order->repairOutcome->delivered_at)
                                            <form method="post" action="{{ route('worker.orders.deliver', $order) }}" onsubmit="return confirm('¿Confirmar entrega al cliente?')">
                                                @csrf
                                                <button class="btn btn-ui btn-primary-ui btn-sm" type="submit">Marcar como entregada</button>
                                            </form>
                                        @endif
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
                                        @if($order->ai_diagnosed_at)
                                            <p class="mb-1">
                                                <strong>Costo sugerido (mano de obra):</strong>
                                                ${{ number_format((float) ($order->ai_cost_repair_labor ?? 0), 2) }}
                                            </p>
                                            @if($order->ai_requires_parts_replacement)
                                                <p class="mb-1">
                                                    <strong>Costo piezas sugeridas:</strong>
                                                    ${{ number_format((float) ($order->ai_cost_replacement_parts ?? 0), 2) }}
                                                </p>
                                                <p class="mb-1">
                                                    <strong>Costo total con reemplazo:</strong>
                                                    ${{ number_format((float) ($order->ai_cost_replacement_total ?? 0), 2) }}
                                                </p>
                                            @endif
                                            <p class="mb-1"><strong>Tokens estimados usados:</strong> {{ number_format((int) ($order->ai_tokens_used ?? 0)) }}</p>
                                            <p class="mb-1"><strong>Diagnóstico generado:</strong> {{ $order->ai_diagnosed_at?->format('Y-m-d H:i') }}</p>
                                        @endif
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
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable billing-modal-dialog">
        <div class="modal-content card-ui billing-modal-content">
            <form method="post" action="{{ route('worker.orders.store') }}" id="orderCreateForm" class="billing-modal-form">
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
                                    <textarea class="form-control input-ui" rows="4" name="symptoms" id="orderSymptoms" maxlength="600" placeholder="Describe los problemas..."></textarea>
                                    <small class="text-muted d-block mt-1"><span id="symptomsCounter">0</span>/600 caracteres.</small>
                                </div>
                                <div class="col-md-6"><label class="form-label">Costo estimado</label><input class="form-control input-ui" type="number" step="0.01" min="0" name="estimated_cost"></div>
                                <div class="col-md-6">
                                    <label class="form-label">Técnico asignado *</label>
                                    @if($currentUser?->role === 'admin')
                                        <select class="form-select input-ui" name="technician_profile_id" required>
                                            <option value="">Seleccionar técnico...</option>
                                            @foreach($companyTechnicians as $technician)
                                                <option value="{{ $technician->id }}">
                                                    {{ $technician->display_name }}
                                                    ({{ strtoupper($technician->user?->role ?? 'tecnico') }} / {{ strtoupper($technician->status) }})
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif($currentUser?->role === 'worker')
                                        <input class="form-control input-ui" value="{{ $currentUser?->name }}" readonly>
                                        @if($currentUser?->technicianProfile)
                                            <input type="hidden" name="technician_profile_id" value="{{ $currentUser->technicianProfile->id }}">
                                        @endif
                                    @else
                                        <input class="form-control input-ui" name="technician" required>
                                    @endif
                                </div>
                                <div class="col-12">
                                    <div class="card border-0 bg-light-subtle">
                                        <div class="card-body py-3">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" value="1" name="request_ai_diagnosis" id="requestAiDiagnosis" @disabled(!$aiEnabled)>
                                                <label class="form-check-label fw-semibold" for="requestAiDiagnosis">
                                                    Obtener diagnóstico IA al guardar la orden
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        @if($aiEnabled)
                                            Disponible en plan {{ strtoupper($aiPlan) }}. Límite de este mes: {{ $aiQueriesUsed }}/{{ $aiQueryLimit }} consultas.
                                        @else
                                            Esta función no está disponible para tu plan actual.
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                                <div class="card card-ui h-100">
                                    <div class="card-body" id="aiPanel">
                                        <h6 class="fw-bold">Análisis AI</h6>
                                        <p class="text-muted mb-2">Se ejecuta al guardar la orden y solo una vez por orden.</p>
                                        <ul class="mb-0 text-muted">
                                            <li>Obtiene equipo + falla reportada.</li>
                                            <li>Genera diagnóstico preliminar y costos sugeridos.</li>
                                            <li>Si no requiere piezas, muestra solo mano de obra.</li>
                                            <li>Plan Starter: 10 usos por mes.</li>
                                            <li>Plan Pro: 75 usos por mes.</li>
                                            <li>Plan Enterprise: 200 usos por mes.</li>
                                            <li>Los síntomas deben capturarse en máximo 600 caracteres.</li>
                                        </ul>
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
    const symptomsInput = document.getElementById('orderSymptoms');
    const symptomsCounter = document.getElementById('symptomsCounter');

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

    if (symptomsInput && symptomsCounter) {
        const syncCounter = () => {
            const current = symptomsInput.value.length;
            symptomsCounter.textContent = String(current);
        };

        symptomsInput.addEventListener('input', () => {
            if (symptomsInput.value.length > 600) {
                symptomsInput.value = symptomsInput.value.slice(0, 600);
            }
            syncCounter();
        });

        syncCounter();
    }
})();
</script>
@endsection
