<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return match ($request->user()->role) {
            'admin' => redirect()->route('dashboard.admin'),
            'developer' => redirect()->route('dashboard.developer'),
            default => redirect()->route('dashboard.worker'),
        };
    }

    public function worker(Request $request)
    {
        $user = $request->user();
        $company = $user->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $delegatedOrdersQuery = Order::query()
            ->where('company_id', $company->id)
            ->where('technician', $user->name);

        $stats = [
            'orders' => $delegatedOrdersQuery->count(),
            'customers' => Customer::query()->where('company_id', $company->id)->count(),
            'equipments' => Equipment::query()->where('company_id', $company->id)->count(),
        ];

        $delegatedOrders = Order::query()
            ->with(['customer', 'equipment'])
            ->where('company_id', $company->id)
            ->where('technician', $user->name)
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('dashboard.worker', [
            'currentPage' => 'dashboard-worker',
            'stats' => $stats,
            'company' => $company,
            'delegatedOrders' => $delegatedOrders,
        ]);
    }

    public function admin(Request $request)
    {
        $company = $request->user()->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $workersCount = User::query()
            ->where('company_id', $company?->id)
            ->where('role', 'worker')
            ->count();

        return view('dashboard.admin', [
            'currentPage' => 'dashboard-admin',
            'company' => $company,
            'workersCount' => $workersCount,
            'subscription' => $company?->subscription,
        ]);
    }

    public function developer()
    {
        return view('dashboard.developer', [
            'currentPage' => 'dashboard-developer',
            'companiesCount' => Company::query()->count(),
            'activeSubscriptions' => Subscription::query()->where('status', 'active')->count(),
            'usersCount' => User::query()->count(),
        ]);
    }
}
