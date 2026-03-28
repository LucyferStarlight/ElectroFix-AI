<?php

namespace App\Http\Middleware;

use App\Models\Order;
use App\Services\Exceptions\OrderApprovalException;
use App\Services\Exceptions\OrderWorkflowException;
use App\Services\OrderStateMachine;
use App\Services\OrderWorkflowService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrderWorkflowAction
{
    public function __construct(
        private readonly OrderWorkflowService $orderWorkflowService,
        private readonly OrderStateMachine $orderStateMachine
    ) {}

    public function handle(Request $request, Closure $next, string $action): Response
    {
        $order = $request->route('order');
        if (! $order instanceof Order) {
            return $next($request);
        }

        try {
            $this->ensureAction($order, $request, $action);
        } catch (OrderWorkflowException|OrderApprovalException|\InvalidArgumentException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'data' => null,
                    'meta' => [],
                    'error' => [
                        'code' => 'INVALID_ORDER_WORKFLOW_ACTION',
                        'message' => $exception->getMessage(),
                    ],
                ], 422);
            }

            return back()->withErrors(['workflow' => $exception->getMessage()]);
        }

        return $next($request);
    }

    private function ensureAction(Order $order, Request $request, string $action): void
    {
        $action = strtolower(trim($action));

        match ($action) {
            'approve' => $this->orderWorkflowService->ensureCanApprove($order),
            'deliver' => $this->orderWorkflowService->ensureCanDeliver($order),
            'repair' => $this->orderWorkflowService->ensureCanRepair($order),
            'close' => $this->orderWorkflowService->ensureCanClose($order),
            'transition' => $this->ensureTransitionAction($order, $request),
            default => null,
        };
    }

    private function ensureTransitionAction(Order $order, Request $request): void
    {
        $target = (string) $request->input('status');

        if (trim($target) === '') {
            throw OrderWorkflowException::invalidTransitionTarget();
        }

        if (! $this->orderStateMachine->canTransition((string) $order->status, $target)) {
            return;
        }

        $this->orderWorkflowService->ensureCanTransitionTo($order, $target);
    }
}
