<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Subscription;

class CompanyInsightsController extends Controller
{
    public function index()
    {
        $companies = Company::query()
            ->with(['subscription', 'users'])
            ->orderBy('name')
            ->get();

        return view('developer.companies.index', [
            'currentPage' => 'developer-companies',
            'companies' => $companies,
        ]);
    }

    public function show(Company $company)
    {
        $company->load(['subscription', 'users']);

        return view('developer.companies.show', [
            'currentPage' => 'developer-companies',
            'company' => $company,
        ]);
    }

    public function subscriptions()
    {
        return view('developer.companies.index', [
            'currentPage' => 'developer-subscriptions',
            'companies' => Company::query()->with('subscription')->orderBy('name')->get(),
        ]);
    }

    public function testCompany()
    {
        $company = Company::query()
            ->where('name', 'ElectroFix Developer Lab')
            ->with(['subscription', 'users'])
            ->firstOrFail();

        return view('developer.companies.show', [
            'currentPage' => 'developer-test-company',
            'company' => $company,
        ]);
    }
}
