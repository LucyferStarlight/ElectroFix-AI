<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTechnicianProfileRequest;
use App\Http\Requests\UpdateTechnicianProfileRequest;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Support\TechnicianStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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

    public function store(Request $request)
    {
        $request->validate([
            'display_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'employee_code' => 'required|string|max:50',
            'hourly_rate' => 'nullable|numeric',
            'specialties' => 'nullable|string'
        ]);

        DB::transaction(function () use ($request) {

            // Crear usuario del sistema
            $user = User::create([
                'name' => $request->display_name,
                'email' => $request->email,
                'password' => Hash::make(Str::random(12)),
                'role' => 'worker'
            ]);

            // Crear perfil técnico
            TechnicianProfile::create([
                'company_id' => auth()->user()->company_id,
                'user_id' => $user->id,
                'employee_code' => $request->employee_code,
                'display_name' => $request->display_name,
                'hourly_rate' => $request->hourly_rate,
                'specialties' => $request->specialties,
                'status' => 'active',
                'max_concurrent_orders' => 3
            ]);
        });

        return redirect()->route('admin.technicians.index')
            ->with('success', 'Técnico creado correctamente');
    }

    public function update(UpdateTechnicianProfileRequest $request, TechnicianProfile $technician): RedirectResponse
    {
        $this->assertCompanyScope($request, $technician);

        $technician->update([
            ...$request->validated(),
            'is_assignable' => $request->boolean('is_assignable'),
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

