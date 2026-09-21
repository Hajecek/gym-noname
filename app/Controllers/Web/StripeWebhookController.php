<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\Billing\CheckoutService;
use App\Services\Billing\StripeGateway;

final class StripeWebhookController extends Controller
{
    public function handle(Request $request): never
    {
        $secret = trim((string) env_value('STRIPE_WEBHOOK_SECRET', ''));
        try {
            $event = StripeGateway::fromConfig()->parseWebhook(
                $request->rawBody(),
                (string) ($request->header('Stripe-Signature') ?? ''),
                $secret
            );
            CheckoutService::make($this->app->db())->handleWebhook($event);
        } catch (HttpException $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], $e->status);
        }
        Response::json(['success' => true], 200);
    }
}
