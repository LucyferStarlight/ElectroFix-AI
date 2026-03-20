<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WorkerController extends Controller
{
    public function index(Request $request)
    {
        $company = $request->user()->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $workers = User::query()
            ->where('company_id', $company->id)
            ->where('role', 'worker')
            ->orderBy('name')
            ->get();

        if (view()->exists('admin.workers.index')) {
            return view('admin.workers.index', [
                'currentPage' => 'admin-workers',
                'workers' => $workers,
            ]);
        }

        return redirect()
            ->route('dashboard.admin')
            ->with('warning', 'El módulo de workers aún no cuenta con interfaz disponible.');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
            'can_access_billing' => ['nullable', 'boolean'],
            'can_access_inventory' => ['nullable', 'boolean'],
        ]);

        $companyId = (int) $request->user()->company_id;
        abort_if($companyId <= 0, 404, 'Empresa no encontrada para este usuario.');

        $password = $request->filled('password')
            ? $request->string('password')->toString()
            : Str::password(12);

        $worker = User::query()->create([
            'company_id' => $companyId,
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => Hash::make($password),
            'role' => 'worker',
            'is_active' => true,
            'can_access_billing' => $request->boolean('can_access_billing'),
            'can_access_inventory' => $request->boolean('can_access_inventory'),
            'must_change_password' => ! $request->filled('password'),
        ]);

        return redirect()
            ->route('admin.workers.index')
            ->with('success', 'Worker creado correctamente.')
            ->with('temporary_credentials', $worker->must_change_password ? [
                'email' => $worker->email,
                'password' => $password,
            ] : null);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->assertCompanyScope($request, $user);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'can_access_billing' => ['nullable', 'boolean'],
            'can_access_inventory' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'can_access_billing' => $request->boolean('can_access_billing'),
            'can_access_inventory' => $request->boolean('can_access_inventory'),
        ]);

        return back()->with('success', 'Worker actualizado correctamente.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $this->assertCompanyScope($request, $user);

        $user->update(['is_active' => false]);

        return back()->with('success', 'Worker desactivado.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->assertCompanyScope($request, $user);

        $user->delete();

        return back()->with('success', 'Worker eliminado.');
    }

    private function assertCompanyScope(Request $request, User $user): void
    {
        $companyId = $request->user()?->company_id;

        if (! $companyId || $user->company_id !== $companyId) {
            abort(403, 'No puedes administrar workers de otra empresa.');
        }
    }
}
