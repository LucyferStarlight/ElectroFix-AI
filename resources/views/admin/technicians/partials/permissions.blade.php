<div class="form-check">
    <input class="form-check-input" type="checkbox" name="can_access_inventory" id="{{ $inventoryId }}" value="1" @checked($canAccessInventory)>
    <label class="form-check-label" for="{{ $inventoryId }}">
        Acceso a inventario
    </label>
</div>

<div class="form-check">
    <input class="form-check-input" type="checkbox" name="can_access_billing" id="{{ $billingId }}" value="1" @checked($canAccessBilling)>
    <label class="form-check-label" for="{{ $billingId }}">
        Acceso a facturación
    </label>
</div>
