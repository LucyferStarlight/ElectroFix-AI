<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTechnicianProfileRequest;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Support\TechnicianStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TechnicianController extends Controller
{
    public function index(Request $request)
    {
        $company = $request->user()->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $query = TechnicianProfile::query()
            ->with('user')
            ->where('company_id', $company->id)
            ->orderBy('display_name');

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search): void {
                $q->where('display_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        $users = User::query()
            ->where('company_id', $company->id)
            ->whereIn('role', ['worker', 'admin'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.technicians.index', [
            'currentPage' => 'admin-technicians',
            'technicians' => $query->paginate(20)->withQueryString(),
            'search' => $search ?? '',
            'users' => $users,
            'statuses' => TechnicianStatus::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'employee_code' => ['required', 'string', 'max:50'],
            'hourly_cost' => ['nullable', 'numeric', 'min:0'],
            'max_concurrent_orders' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', 'string'],
            'specialties' => ['nullable'],
            'can_access_billing' => ['nullable', 'boolean'],
            'can_access_inventory' => ['nullable', 'boolean'],
        ]);

        $companyId = (int) auth()->user()->company_id;
        abort_if($companyId <= 0, 404, 'Empresa no encontrada para este usuario.');

        $tempPassword = Str::password(12);

        DB::transaction(function () use ($request, $companyId, $tempPassword): void {
            $user = User::query()->create([
                'company_id' => $companyId,
                'name' => $request->string('display_name')->toString(),
                'email' => $request->string('email')->toString(),
                'password' => Hash::make($tempPassword),
                'role' => 'worker',
                'is_active' => true,
                'can_access_billing' => $request->boolean('can_access_billing'),
                'can_access_inventory' => $request->boolean('can_access_inventory'),
                'must_change_password' => true,
            ]);

            TechnicianProfile::query()->create([
                'company_id' => $companyId,
                'user_id' => $user->id,
                'employee_code' => $request->string('employee_code')->toString(),
                'display_name' => $request->string('display_name')->toString(),
                'hourly_cost' => (float) $request->input('hourly_cost', 0),
                'specialties' => array_values(array_filter((array) $request->input('specialties', []))),
                'status' => $request->string('status')->toString(),
                'is_assignable' => $request->boolean('is_assignable'),
                'max_concurrent_orders' => (int) $request->input('max_concurrent_orders', 3),
            ]);
        });

        return redirect()
            ->route('admin.technicians.index')
            ->with('success', 'Técnico creado correctamente.')
            ->with('temporary_credentials', [
                'email' => $request->string('email')->toString(),
                'password' => $tempPassword,
            ]);
    }

    public function update(UpdateTechnicianProfileRequest $request, TechnicianProfile $technician): RedirectResponse
    {
        $this->assertCompanyScope($request, $technician);

        $technician->update([
            ...$request->validated(),
            'is_assignable' => $request->boolean('is_assignable'),
        ]);

        $technician->user()?->update([
            'can_access_billing' => $request->boolean('can_access_billing'),
            'can_access_inventory' => $request->boolean('can_access_inventory'),
        ]);

        return back()->with('success', 'Perfil técnico actualizado correctamente.');
    }

    public function deactivate(Request $request, TechnicianProfile $technician): RedirectResponse
    {
        $this->assertCompanyScope($request, $technician);

        $technician->update([
            'status' => TechnicianStatus::INACTIVE,
            'is_assignable' => false,
        ]);

        return back()->with('success', 'Técnico desactivado.');
    }

    private function assertCompanyScope(Request $request, TechnicianProfile $technician): void
    {
        $companyId = $request->user()?->company_id;

        if (! $companyId || $technician->company_id !== $companyId) {
            abort(403, 'No puedes administrar técnicos de otra empresa.');
        }
    }
}
