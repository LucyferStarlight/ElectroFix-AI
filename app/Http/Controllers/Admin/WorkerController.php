<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkerRequest;
use App\Http\Requests\UpdateWorkerRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class WorkerController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $workers = User::query()
            ->withTrashed()
            ->where('company_id', $companyId)
            ->where('role', 'worker')
            ->orderBy('name')
            ->get();

        return view('admin.workers.index', [
            'currentPage' => 'admin-workers',
            'workers' => $workers,
        ]);
    }

    public function store(StoreWorkerRequest $request): RedirectResponse
    {
        User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'role' => 'worker',
            'company_id' => $request->user()->company_id,
            'is_active' => true,
            'can_access_billing' => $request->boolean('can_access_billing'),
            'can_access_inventory' => $request->boolean('can_access_inventory'),
        ]);

        return back()->with('success', 'Worker creado correctamente.');
    }

    public function update(UpdateWorkerRequest $request, User $user): RedirectResponse
    {
        $this->assertWorkerBelongsToCompany($request->user(), $user);

        $payload = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'can_access_billing' => $request->boolean('can_access_billing'),
            'can_access_inventory' => $request->boolean('can_access_inventory'),
        ];

        if ($request->filled('password')) {
            $payload['password'] = Hash::make($request->validated('password'));
        }

        $user->update($payload);

        return back()->with('success', 'Worker actualizado.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $this->assertWorkerBelongsToCompany($request->user(), $user);

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('success', 'Estado de worker actualizado.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->assertWorkerBelongsToCompany($request->user(), $user);
        $user->forceDelete();

        return back()->with('success', 'Worker eliminado definitivamente.');
    }

    private function assertWorkerBelongsToCompany(User $admin, User $worker): void
    {
        if ($admin->company_id !== $worker->company_id || $worker->role !== 'worker') {
            abort(403, 'No puedes administrar este usuario.');
        }
    }
}
