<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithCompanyScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRepairOutcomeFeedbackRequest;
use App\Models\Order;
use App\Services\Exceptions\OutcomeNotFoundException;
use App\Services\RepairOutcomeService;
use Illuminate\Http\Resources\Json\JsonResource;

class RepairOutcomeApiController extends Controller
{
    use ApiResponse;
    use InteractsWithCompanyScope;

    public function __construct(private readonly RepairOutcomeService $repairOutcomeService)
    {
    }

    public function update(UpdateRepairOutcomeFeedbackRequest $request, Order $order)
    {
        $this->assertCompanyAccess($request, $order->company_id);

        try {
            $outcome = $this->repairOutcomeService->updateFeedback($order, $request->validated());
        } catch (OutcomeNotFoundException $exception) {
            return response()->json([
                'ok' => false,
                'data' => null,
                'meta' => [],
                'error' => [
                    'code' => 'OUTCOME_NOT_FOUND',
                    'message' => $exception->getMessage(),
                ],
            ], 404);
        }

        return $this->successResource(JsonResource::make($outcome));
    }
}
