<?php

declare(strict_types=1);

namespace WeBRTeu\LibraPay;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;
use WeBRTeu\LibraPay\Exceptions\LibraPayException;
use WeBRTeu\Payments\Contracts\Gateway;
use WeBRTeu\Payments\Data\PaymentRedirectData;
use WeBRTeu\Payments\Data\PaymentRequestData;
use WeBRTeu\Payments\Data\PaymentStatusData;
use WeBRTeu\Payments\Data\WebhookData;
use WeBRTeu\Payments\Enums\RedirectType;
use WeBRTeu\Payments\Exceptions\UnsupportedOperationException;
use WeBRTeu\Payments\Support\Money;

/**
 * ⚠️ Construit după protocolul tipic „e-Commerce Gateway”; câmpurile se confirmă cu documentația oficială Libra.
 * Refund / captură (TRTYPE diferit) se adaugă după confirmarea valorilor.
 */
class LibraPayGateway implements Gateway
{
    public function name(): string
    {
        return 'librapay';
    }

    public function createPayment(PaymentRequestData $request): PaymentRedirectData
    {
        $url = Config::get('librapay.url');

        if (! is_string($url) || $url === '') {
            throw new LibraPayException('Lipsește LIBRAPAY_URL (URL-ul formularului din documentația Libra).');
        }

        return new PaymentRedirectData(
            type: RedirectType::Form,
            url: $url,
            fields: RequestFields::make($request, OrderNumber::for($request->orderId), $this->config(), $this->order('request')),
        );
    }

    public function parseWebhook(Request $request): WebhookData
    {
        parse_str($request->getContent(), $parsed);
        $fields = array_map(fn (mixed $value): string => is_scalar($value) ? (string) $value : '', array_filter($parsed, is_string(...), ARRAY_FILTER_USE_KEY));

        ResponseVerifier::verify(
            $fields,
            $this->order('response'),
            Config::string('librapay.key', ''),
            (string) Config::get('librapay.terminal', ''),
            Config::integer('librapay.timestamp_tolerance_minutes', 60),
        );

        $reference = $fields['INT_REF'] ?? '';

        return new WebhookData(
            eventId: $reference.'-'.($fields['TRTYPE'] ?? '').'-'.($fields['ACTION'] ?? ''),
            orderId: OrderNumber::resolve($fields['ORDER'] ?? ''),
            status: LibraPayStatusMap::resolve($fields['ACTION'] ?? '', $fields['RC'] ?? ''),
            providerPaymentId: $reference !== '' ? $reference : null,
            amount: Money::fromDecimal($fields['AMOUNT'] ?? '0'),
            raw: $fields,
        );
    }

    public function webhookResponse(?WebhookData $webhook, bool $accepted): Response
    {
        return new HttpResponse($accepted ? Config::string('librapay.ipn_response', '1') : '0', $accepted ? 200 : 400);
    }

    public function status(string $providerPaymentId): PaymentStatusData
    {
        throw UnsupportedOperationException::for($this->name(), 'status (de confirmat în documentația Libra)');
    }

    /**
     * @return list<string>
     */
    private function order(string $type): array
    {
        return array_values(array_map(strval(...), Config::array("librapay.fields.{$type}", [])));
    }

    /**
     * @return array{terminal: string, merchant: string, merchant_name: string, merchant_url: string, email: string, key: string, country: string, merch_gmt: string, trtype: string, backref: string}
     */
    private function config(): array
    {
        $backref = Config::get('librapay.backref');

        return [
            'terminal' => (string) Config::get('librapay.terminal', ''),
            'merchant' => (string) Config::get('librapay.merchant', ''),
            'merchant_name' => (string) Config::get('librapay.merchant_name', ''),
            'merchant_url' => (string) Config::get('librapay.merchant_url', ''),
            'email' => (string) Config::get('librapay.email', ''),
            'key' => Config::string('librapay.key', ''),
            'country' => Config::string('librapay.country', 'RO'),
            'merch_gmt' => (string) Config::get('librapay.merch_gmt', '+2'),
            'trtype' => (string) Config::get('librapay.trtype.sale', '0'),
            'backref' => is_string($backref) && $backref !== '' ? $backref : URL::route('payments.webhook', ['gateway' => 'librapay']),
        ];
    }
}
