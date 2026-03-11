<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()->orderByDesc('created_at');

        if ($request->user()->role !== 'developer') {
            $query->where('company_id', $request->user()->company_id);
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return view('worker.customers.index', [
            'currentPage' => 'worker-customers',
            'customers' => $query->paginate(20)->withQueryString(),
            'search' => $search ?? '',
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        Customer::query()->create([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
        ]);

        return back()->with('success', 'Cliente registrado exitosamente.');
    }
}
