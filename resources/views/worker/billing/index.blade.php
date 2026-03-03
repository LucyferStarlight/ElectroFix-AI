@extends('layouts.app')

@section('title', 'Facturación | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Facturación / Cotizaciones (POS)</h1>
            <p class="text-muted mb-0">Emite documentos de reparación, venta o mixtos con IVA incluido o agregado.</p>
        </div>
        <button class="btn btn-ui btn-primary-ui" data-bs-toggle="modal" data-bs-target="#billingModal">Nuevo Documento</button>
    </div>

    <div class="card card-ui mb-4">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center">
            <span class="badge badge-ui badge-ui-info">IVA empresa: {{ number_format((float) $company->vat_percentage, 2) }}%</span>
            <span class="badge badge-ui badge-ui-success">Clientes registrados: {{ $customers->count() }}</span>
            <span class="badge badge-ui badge-ui-warning">Productos inventario: {{ $inventoryItems->count() }}</span>
        </div>
    </div>

    <div class="card card-ui">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-ui align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="px-3">Documento</th>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Origen</th>
                            <th>IVA</th>
                            <th>Total</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $doc)
                            <tr>
                                <td class="px-3">
                                    <div class="fw-semibold text-monospace">{{ $doc->document_number }}</div>
                                    <small class="text-muted">{{ $doc->issued_at?->format('Y-m-d H:i') ?? $doc->created_at->format('Y-m-d H:i') }}</small>
                                </td>
                                <td>{{ $doc->customerDisplayName() }}</td>
                                <td><span class="badge badge-ui badge-ui-info text-uppercase">{{ $doc->document_type }}</span></td>
                                <td><span class="badge badge-ui badge-ui-warning text-uppercase">{{ $doc->source }}</span></td>
                                <td>{{ strtoupper($doc->tax_mode) }} ({{ number_format((float) $doc->vat_percentage, 2) }}%)</td>
                                <td class="fw-semibold">${{ number_format((float) $doc->total, 2) }}</td>
                                <td class="pe-3 text-end">
                                    <a class="btn btn-ui btn-outline-ui btn-sm" href="{{ route('worker.billing.show', $doc) }}">Ver</a>
                                    <a class="btn btn-ui btn-primary-ui btn-sm" href="{{ route('worker.billing.pdf', $doc) }}">PDF</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">Sin documentos de facturación.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $documents->links() }}</div>
</div>

<div class="modal fade" id="billingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable billing-modal-dialog">
        <div class="modal-content card-ui billing-modal-content">
            <form method="post" action="{{ route('worker.billing.store') }}" id="billingForm" class="billing-modal-form">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Nuevo documento POS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="document_type" id="documentTypeInput" value="quote">
                    <input type="hidden" name="source" id="sourceInput" value="sale">

                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Tipo de documento</label>
                            <div class="btn-group w-100" role="group" aria-label="Tipo de documento">
                                <button class="btn btn-ui btn-outline-ui js-doc-type-btn active" type="button" data-doc-type="quote">Cotización</button>
                                <button class="btn btn-ui btn-outline-ui js-doc-type-btn" type="button" data-doc-type="invoice">Factura</button>
                            </div>
                            <small class="text-muted d-block mt-1" id="serviceStatusHint">Al seleccionar servicios: Cotización actualiza estado a “Cotización”, Factura a “Listo”.</small>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-center">
                                <div class="btn-group" role="group" aria-label="Origen de documento">
                                    <button class="btn btn-ui btn-primary-ui js-source-btn active" type="button" data-source="sale">Venta</button>
                                    <button class="btn btn-ui btn-outline-ui js-source-btn" type="button" data-source="mixed">Mixto</button>
                                    <button class="btn btn-ui btn-outline-ui js-source-btn" type="button" data-source="repair">Reparación</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Modo cliente</label>
                            <select class="form-select input-ui" name="customer_mode" id="customerMode" required>
                                <option value="registered">Cliente registrado</option>
                                <option value="walk_in">Cliente de Mostrador</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Modo IVA</label>
                            <select class="form-select input-ui" name="tax_mode" required>
                                <option value="excluded">Agregar IVA al precio</option>
                                <option value="included">IVA incluido en precio</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="customerSelectWrap">
                            <label class="form-label">Cliente registrado</label>
                            <select class="form-select input-ui" name="customer_id" id="customerSelect">
                                <option value="">Seleccionar...</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 d-none" id="walkInWrap">
                            <label class="form-label">Nombre (mostrador)</label>
                            <input class="form-control input-ui" name="walk_in_name" id="walkInInput" value="Cliente de Mostrador">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas</label>
                            <input class="form-control input-ui" name="notes" placeholder="Notas internas del documento">
                        </div>
                    </div>

                    <div class="card card-ui mb-3" id="customerServicesCard" style="display:none;">
                        <div class="card-body">
                            <h6 class="fw-bold mb-2">Servicios del cliente seleccionado</h6>
                            <p class="text-muted mb-2" id="customerServicesInfo">Selecciona una orden de servicio para usar costo/descripción.</p>
                            <div class="table-responsive">
                                <table class="table table-ui table-sm mb-0" id="customerServicesTable">
                                    <thead>
                                        <tr>
                                            <th>#Orden</th>
                                            <th>Servicio</th>
                                            <th>Costo registrado</th>
                                            <th>Estado</th>
                                            <th>Equipo</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">Partidas</h6>
                        <button class="btn btn-ui btn-outline-ui btn-sm" type="button" id="addItemRowBtn">Agregar partida</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-ui align-middle" id="billingItemsTable">
                            <thead>
                                <tr>
                                    <th class="js-th-kind">Tipo</th>
                                    <th class="js-th-service">Servicio cliente (opcional)</th>
                                    <th class="js-th-product">Producto inventario</th>
                                    <th>Descripción</th>
                                    <th>Cantidad</th>
                                    <th>Precio unitario</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button class="btn btn-ui btn-outline-ui" type="button" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-ui btn-primary-ui" type="submit">Generar documento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="billingItemRowTemplate">
    <tr>
        <td class="js-col-kind">
            <select class="form-select input-ui js-item-kind" name="items[__INDEX__][item_kind]" required>
                <option value="service">Servicio</option>
                <option value="product">Producto</option>
            </select>
        </td>
        <td class="js-col-service">
            <select class="form-select input-ui js-order-select" name="items[__INDEX__][order_id]">
                <option value="">Manual</option>
            </select>
        </td>
        <td class="js-col-product">
            <select class="form-select input-ui js-product-select" name="items[__INDEX__][inventory_item_id]">
                <option value="">Seleccionar producto...</option>
                @foreach($inventoryItems as $item)
                    @if($item->is_sale_enabled)
                        <option
                            value="{{ $item->id }}"
                            data-name="{{ $item->name }}"
                            data-code="{{ $item->internal_code }}"
                            data-stock="{{ (int) $item->quantity }}"
                            data-price="{{ number_format((float) ($item->sale_price ?? 0), 2, '.', '') }}"
                            @disabled((int) $item->quantity <= 0)
                        >
                            {{ $item->name }} ({{ $item->internal_code }}) - Stock: {{ (int) $item->quantity }}
                        </option>
                    @endif
                @endforeach
            </select>
        </td>
        <td><input class="form-control input-ui js-description" name="items[__INDEX__][description]" required></td>
        <td><input class="form-control input-ui js-quantity" type="number" name="items[__INDEX__][quantity]" step="0.01" min="0.01" value="1" required></td>
        <td><input class="form-control input-ui js-unit-price" type="number" name="items[__INDEX__][unit_price]" step="0.01" min="0" value="0" required></td>
        <td><button class="btn btn-sm btn-danger" type="button" data-remove-row>Quitar</button></td>
    </tr>
</template>

<script>
(() => {
    const documentTypeInput = document.getElementById('documentTypeInput');
    const sourceInput = document.getElementById('sourceInput');
    const docTypeButtons = document.querySelectorAll('.js-doc-type-btn');
    const sourceButtons = document.querySelectorAll('.js-source-btn');
    const serviceStatusHint = document.getElementById('serviceStatusHint');
    const customerMode = document.getElementById('customerMode');
    const customerSelectWrap = document.getElementById('customerSelectWrap');
    const customerSelect = document.getElementById('customerSelect');
    const walkInWrap = document.getElementById('walkInWrap');
    const walkInInput = document.getElementById('walkInInput');
    const servicesCard = document.getElementById('customerServicesCard');
    const servicesTableBody = document.querySelector('#customerServicesTable tbody');
    const servicesInfo = document.getElementById('customerServicesInfo');
    const tbody = document.querySelector('#billingItemsTable tbody');
    const addBtn = document.getElementById('addItemRowBtn');
    const tpl = document.getElementById('billingItemRowTemplate');
    const thKind = document.querySelector('.js-th-kind');
    const thService = document.querySelector('.js-th-service');
    const thProduct = document.querySelector('.js-th-product');

    let rowIndex = 0;
    let customerOrders = [];

    const fetchCustomerOrders = async (customerId) => {
        if (!customerId) {
            customerOrders = [];
            renderCustomerOrders();
            renderRowOrderOptions();
            return;
        }

        servicesInfo.textContent = 'Cargando servicios del cliente...';
        servicesCard.style.display = 'block';

        try {
            const response = await fetch(`{{ url('/worker/billing/customers') }}/${customerId}/services`);
            if (!response.ok) throw new Error('No se pudieron cargar los servicios.');
            const data = await response.json();
            customerOrders = data.orders || [];
            renderCustomerOrders(data.customer?.name || 'Cliente');
            renderRowOrderOptions();
        } catch (error) {
            customerOrders = [];
            renderCustomerOrders();
            servicesInfo.textContent = 'No se pudo cargar la información de servicios del cliente.';
            renderRowOrderOptions();
        }
    };

    const renderCustomerOrders = (customerName = '') => {
        servicesTableBody.innerHTML = '';

        if (!customerOrders.length) {
            servicesInfo.textContent = customerName
                ? `No hay servicios previos para ${customerName}.`
                : 'Selecciona un cliente para consultar sus servicios registrados.';
            return;
        }

        servicesInfo.textContent = `Servicios registrados para ${customerName}. Puedes tomarlos como partida de facturación.`;

        customerOrders.forEach((order) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>#${order.id}</td>
                <td>${order.description}</td>
                <td>$${Number(order.estimated_cost || 0).toFixed(2)}</td>
                <td>${order.status_label}</td>
                <td>${order.equipment || 'N/A'}</td>
            `;
            servicesTableBody.appendChild(tr);
        });
    };

    const renderRowOrderOptions = () => {
        document.querySelectorAll('.js-order-select').forEach((select) => {
            const selected = select.value;
            select.innerHTML = '<option value="">Manual</option>';
            customerOrders.forEach((order) => {
                const option = document.createElement('option');
                option.value = order.id;
                option.textContent = `#${order.id} - ${order.status_label} - $${Number(order.estimated_cost).toFixed(2)}`;
                option.dataset.description = order.description || '';
                option.dataset.cost = Number(order.estimated_cost || 0).toFixed(2);
                select.appendChild(option);
            });
            if (selected) select.value = selected;
        });
    };

    const sourceMode = () => sourceInput.value || 'sale';

    const syncHeaderBySource = () => {
        const mode = sourceMode();
        const showKind = mode === 'mixed';
        const showService = mode !== 'sale';
        const showProduct = mode !== 'repair';

        thKind?.classList.toggle('d-none', !showKind);
        thService?.classList.toggle('d-none', !showService);
        thProduct?.classList.toggle('d-none', !showProduct);
    };

    const syncServicesCardVisibility = () => {
        const walkIn = customerMode.value === 'walk_in';
        const mode = sourceMode();
        const shouldShow = !walkIn && mode !== 'sale';
        servicesCard.style.display = shouldShow ? 'block' : 'none';
    };

    const syncStatusHint = () => {
        serviceStatusHint.textContent = documentTypeInput.value === 'quote'
            ? 'Servicios ligados actualizarán su estado a “Cotización”.'
            : 'Servicios ligados actualizarán su estado a “Listo”.';
    };

    const bindRowBehavior = (tr) => {
        const kindSelect = tr.querySelector('.js-item-kind');
        const orderSelect = tr.querySelector('.js-order-select');
        const productSelect = tr.querySelector('.js-product-select');
        const descriptionInput = tr.querySelector('.js-description');
        const quantityInput = tr.querySelector('.js-quantity');
        const unitPriceInput = tr.querySelector('.js-unit-price');
        const kindCell = tr.querySelector('.js-col-kind');
        const serviceCell = tr.querySelector('.js-col-service');
        const productCell = tr.querySelector('.js-col-product');

        const getEffectiveKind = () => {
            const mode = sourceMode();
            if (mode === 'sale') {
                return 'product';
            }
            if (mode === 'repair') {
                return 'service';
            }

            return kindSelect.value;
        };

        const syncKind = () => {
            const mode = sourceMode();
            const effectiveKind = getEffectiveKind();
            const isService = effectiveKind === 'service';

            const showKind = mode === 'mixed';
            const showService = mode !== 'sale';
            const showProduct = mode !== 'repair';

            kindCell?.classList.toggle('d-none', !showKind);
            serviceCell?.classList.toggle('d-none', !showService);
            productCell?.classList.toggle('d-none', !showProduct);

            // Keep enabled so the value is always posted; when mode is not mixed it stays hidden but fixed.
            kindSelect.value = effectiveKind;
            kindSelect.disabled = false;

            orderSelect.disabled = !isService || customerMode.value === 'walk_in';
            productSelect.disabled = isService;
            productSelect.required = !isService;

            if (isService) {
                productSelect.value = '';
                descriptionInput.readOnly = false;
                unitPriceInput.readOnly = false;
                quantityInput.max = '';
                quantityInput.step = '0.01';
                quantityInput.min = '0.01';
            } else {
                orderSelect.value = '';
                descriptionInput.readOnly = true;
                unitPriceInput.readOnly = true;
                quantityInput.step = '1';
                quantityInput.min = '1';
            }
        };

        orderSelect.addEventListener('change', () => {
            const option = orderSelect.selectedOptions[0];
            if (!option || !option.value) return;

            if (!descriptionInput.value.trim()) {
                descriptionInput.value = option.dataset.description || '';
            }

            if (!Number(unitPriceInput.value)) {
                unitPriceInput.value = option.dataset.cost || '0.00';
            }
        });

        productSelect.addEventListener('change', () => {
            const option = productSelect.selectedOptions[0];
            if (!option || !option.value) {
                descriptionInput.value = '';
                unitPriceInput.value = '0.00';
                quantityInput.max = '';
                return;
            }

            const stock = Number(option.dataset.stock || 0);
            const price = option.dataset.price || '0.00';
            const name = option.dataset.name || 'Producto';
            const code = option.dataset.code || '';

            descriptionInput.value = code ? `${name} (${code})` : name;
            unitPriceInput.value = price;
            quantityInput.max = String(stock);
            if (Number(quantityInput.value) > stock) {
                quantityInput.value = String(stock);
            }
        });

        kindSelect.addEventListener('change', syncKind);
        syncKind();
    };

    const addRow = () => {
        const html = tpl.innerHTML.replaceAll('__INDEX__', String(rowIndex++));
        const tr = document.createElement('tr');
        tr.innerHTML = html;
        tbody.appendChild(tr);
        bindRowBehavior(tr);
        renderRowOrderOptions();
    };

    const syncCustomerMode = () => {
        const walkIn = customerMode.value === 'walk_in';
        customerSelectWrap.classList.toggle('d-none', walkIn);
        walkInWrap.classList.toggle('d-none', !walkIn);
        customerSelect.required = !walkIn;
        walkInInput.required = walkIn;

        if (walkIn) {
            customerSelect.value = '';
            customerOrders = [];
            renderRowOrderOptions();
        } else {
            if (sourceMode() !== 'sale') {
                fetchCustomerOrders(customerSelect.value);
            }
        }

        syncServicesCardVisibility();
        document.querySelectorAll('#billingItemsTable tbody tr').forEach((tr) => bindRowBehavior(tr));
    };

    const syncSourceMode = () => {
        syncHeaderBySource();
        syncServicesCardVisibility();
        if (sourceMode() !== 'sale' && customerMode.value === 'registered') {
            fetchCustomerOrders(customerSelect.value);
        } else {
            customerOrders = [];
            renderRowOrderOptions();
            renderCustomerOrders();
        }

        document.querySelectorAll('#billingItemsTable tbody tr').forEach((tr) => bindRowBehavior(tr));
    };

    docTypeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            documentTypeInput.value = button.dataset.docType;
            docTypeButtons.forEach((btn) => btn.classList.toggle('active', btn === button));
            syncStatusHint();
        });
    });

    sourceButtons.forEach((button) => {
        button.addEventListener('click', () => {
            sourceInput.value = button.dataset.source;
            sourceButtons.forEach((btn) => {
                btn.classList.toggle('active', btn === button);
                btn.classList.toggle('btn-primary-ui', btn === button);
                btn.classList.toggle('btn-outline-ui', btn !== button);
            });
            syncSourceMode();
        });
    });

    addBtn?.addEventListener('click', addRow);

    tbody?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove-row]');
        if (!btn) return;
        btn.closest('tr')?.remove();
    });

    customerMode?.addEventListener('change', syncCustomerMode);
    customerSelect?.addEventListener('change', () => fetchCustomerOrders(customerSelect.value));

    addRow();
    syncStatusHint();
    syncSourceMode();
    syncCustomerMode();
})();
</script>
@endsection
