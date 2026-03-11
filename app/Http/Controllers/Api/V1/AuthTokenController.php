<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AiPlanPolicyService;
use App\Support\ApiAbility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthTokenController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AiPlanPolicyService $aiPlanPolicyService)
    {
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'ok' => false,
                'data' => null,
                'meta' => [],
                'error' => ['code' => 'AUTH_INVALID', 'message' => 'Credenciales inválidas.'],
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'ok' => false,
                'data' => null,
                'meta' => [],
                'error' => ['code' => 'AUTH_DISABLED', 'message' => 'La cuenta está desactivada.'],
            ], 403);
        }

        $abilities = $this->abilitiesForUser($user);
        $token = $user->createToken($data['device_name'], $abilities);

        return $this->success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'abilities' => $abilities,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'company_id' => $user->company_id,
            ],
        ], status: 201);
    }

    private function abilitiesForUser(User $user): array
    {
        if (in_array($user->role, ['admin', 'developer'], true)) {
            return ApiAbility::all();
        }

        $abilities = [
            ApiAbility::ORDERS_READ,
            ApiAbility::ORDERS_WRITE,
        ];

        if ($user->can_access_inventory) {
            $abilities[] = ApiAbility::INVENTORY_WRITE;
        }

        if ($user->can_access_billing) {
            $abilities[] = ApiAbility::BILLING_WRITE;
        }

        $plan = (string) ($user->company?->subscription?->plan ?? 'starter');
        if ($this->aiPlanPolicyService->supportsAi($plan)) {
            $abilities[] = ApiAbility::AI_USE;
        }

        return array_values(array_unique($abilities));
    }
}

