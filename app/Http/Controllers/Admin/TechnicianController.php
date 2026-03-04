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

    public function store(StoreTechnicianProfileRequest $request): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $data = $request->validated();

        if (! empty($data['user_id'])) {
            $user = User::query()
                ->where('company_id', $company->id)
                ->where('id', (int) $data['user_id'])
                ->whereIn('role', ['worker', 'admin', 'developer'])
                ->where('is_active', true)
                ->first();

            if (! $user) {
                abort(422, 'El usuario seleccionado no puede ser técnico en esta empresa.');
            }
        }

        TechnicianProfile::query()->create([
            'company_id' => $company->id,
            'user_id' => $data['user_id'] ?? null,
            'employee_code' => $data['employee_code'],
            'display_name' => $data['display_name'],
            'specialties' => $data['specialties'] ?? [],
            'status' => $data['status'],
            'max_concurrent_orders' => $data['max_concurrent_orders'],
            'hourly_cost' => $data['hourly_cost'] ?? 0,
            'is_assignable' => $request->boolean('is_assignable', true),
        ]);

        return back()->with('success', 'Perfil técnico creado correctamente.');
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

