<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignEquipmentCustomerRequest;
use App\Http\Requests\StoreEquipmentRequest;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $equipments = Equipment::query()->with('customer')->orderByDesc('created_at');
        $customers = Customer::query()->orderBy('name');

        if ($request->user()->role !== 'developer') {
            $companyId = $request->user()->company_id;
            $equipments->where('company_id', $companyId);
            $customers->where('company_id', $companyId);
        }

        if ($search = trim((string) $request->query('search'))) {
            $equipments->where(function ($q) use ($search): void {
                $q->where('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search): void {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return view('worker.equipments.index', [
            'currentPage' => 'worker-equipments',
            'equipments' => $equipments->paginate(18)->withQueryString(),
            'customers' => $customers->get(),
            'search' => $search ?? '',
        ]);
    }

    public function store(StoreEquipmentRequest $request): RedirectResponse
    {
        $customer = $request->input('customer_mode') === 'walk_in'
            ? $this->resolveWalkInCustomer($request)
            : Customer::query()->findOrFail($request->integer('customer_id'));

        if ($request->user()->role !== 'developer' && $customer->company_id !== $request->user()->company_id) {
            abort(403, 'Cliente no pertenece a tu empresa.');
        }

        Equipment::query()->create([
            ...collect($request->validated())->except(['customer_mode'])->all(),
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
        ]);

        return back()->with('success', 'Equipo registrado exitosamente.');
    }

    public function assignCustomer(
        AssignEquipmentCustomerRequest $request,
        Equipment $equipment
    ): RedirectResponse {
        if ($request->user()->role !== 'developer' && $equipment->company_id !== $request->user()->company_id) {
            abort(403, 'No puedes modificar este equipo.');
        }

        $customer = Customer::query()->findOrFail($request->integer('customer_id'));
        if ($customer->company_id !== $equipment->company_id) {
            abort(422, 'El cliente seleccionado no pertenece a la misma empresa que el equipo.');
        }

        $equipment->update([
            'customer_id' => $customer->id,
        ]);

        return back()->with('success', 'Equipo vinculado correctamente al cliente seleccionado.');
    }

    private function resolveWalkInCustomer(Request $request): Customer
    {
        $company = $request->user()?->company;

        if (! $company && $request->user()?->role === 'developer' && $request->filled('company_id')) {
            $company = Company::query()->findOrFail((int) $request->input('company_id'));
        }

        abort_unless($company, 422, 'No se encontró una empresa activa para registrar el equipo de mostrador.');

        return Customer::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Cliente de Mostrador',
            ],
            [
                'phone' => $company->billing_phone ?: $company->owner_phone ?: 'PENDIENTE',
                'email' => null,
                'address' => 'Mostrador',
            ]
        );
    }
}
