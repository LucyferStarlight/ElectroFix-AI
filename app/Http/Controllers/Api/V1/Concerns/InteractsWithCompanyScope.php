<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait InteractsWithCompanyScope
{
    protected function scopedCompanyId(Request $request): ?int
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        if ($user->role === 'developer') {
            return $request->integer('company_id') ?: null;
        }

        return $user->company_id;
    }

    protected function applyCompanyScope(Builder $query, Request $request, string $column = 'company_id'): Builder
    {
        $companyId = $this->scopedCompanyId($request);
        if (! $companyId) {
            return $query;
        }

        return $query->where($column, $companyId);
    }

    protected function assertCompanyAccess(Request $request, int $companyId): void
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            abort(401, 'No autenticado.');
        }

        if ($user->role !== 'developer' && $user->company_id !== $companyId) {
            abort(403, 'No puedes acceder a datos de otra empresa.');
        }
    }
}

