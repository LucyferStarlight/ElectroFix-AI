<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipmentRequest;
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
        $customer = Customer::query()->findOrFail($request->integer('customer_id'));

        if ($request->user()->role !== 'developer' && $customer->company_id !== $request->user()->company_id) {
            abort(403, 'Cliente no pertenece a tu empresa.');
        }

        Equipment::query()->create([
            ...$request->validated(),
            'company_id' => $customer->company_id,
        ]);

        return back()->with('success', 'Equipo registrado exitosamente.');
    }
}
