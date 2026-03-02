<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCompanyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function edit(Request $request)
    {
        abort_if(! $request->user()->company, 404, 'Empresa no encontrada para este usuario.');

        return view('admin.company.edit', [
            'currentPage' => 'admin-company',
            'company' => $request->user()->company,
        ]);
    }

    public function update(UpdateCompanyRequest $request): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');
        $company->update($request->validated());

        return back()->with('success', 'Datos de empresa actualizados.');
    }
}
