<?php

namespace Tests\Support;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Runs inside a child process spawned by Concurrency::run(), so it must not
 * depend on the TestCase instance.
 */
class ConfirmRequest
{
    public static function send(int $userId, int $loteId, array $pagoIds): int
    {
        Auth::guard('web')->loginUsingId($userId);

        $request = Request::create(
            "/lotes/{$loteId}/confirmar-aportes",
            'POST',
            ['pago_ids' => $pagoIds],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json']
        );

        return app(Kernel::class)->handle($request)->getStatusCode();
    }
}
