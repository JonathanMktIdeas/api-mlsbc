<?php

namespace App\Services;

use \Stripe\StripeClient;
use App\Models\PaymentIntent;
use App\Models\StripeRefund;
use App\Models\Payment;
use App\Models\MemberPayment;
use App\Models\Member;

class StripeService
{
    private $model;

    function __construct(BaseService $service)
    {
        $this->paymentInfo = $service->getPaymentInfo(true);
        if ($this->paymentInfo['mode'] == 'stripe')
        {
            $this->model = new StripeClient($this->paymentInfo['priv']);
        }
    }

    public function createSource($name, $number, $cvv, $exp_m, $exp_y)
    {
        try {
            $paymentMethod = $this->model->paymentMethods->create([
                'type' => 'card',
                'card' => [
                    'number'    => $number,
                    'exp_month' => $exp_m,
                    'exp_year'  => $exp_y,
                    'cvc'       => $cvv,
                ],
            ]);

            return $paymentMethod;
        } catch(\Stripe\Exception\CardException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function confirmPyament($id)
    {
        $mPayment = Payment::where('id_transaction', $id)->first();

        if (!$mPayment) throw new Exception("Transaction not found", 1);

        $payment_intent = $this->model->paymentIntents->retrieve($id);

        // if ($payment_intent->status === 'requires_payment_method') {
        //     throw new \Exception("Something happen, please try again with another payment method.", 1);
        // }
        // var_dump(json_encode($payment_intent));
        $capture = $payment_intent->capture();

        // var_dump('aqui');
        $mPayment->status = $capture['status'];
        $mPayment->save();

        return $mPayment;
    }

    public function makePayment($memberId, $source, $amount, $name, $phone, $email, $members_an, $members_se)
    {
        $mPayment = Payment::create([
            'member_id' => $memberId,
            'amount' => $amount,
            'status' => 'in_progress',
        ]);

        $payments = [];
        $membersStr = "Cargo por membresia | ";
        foreach ($members_an as $key => $id) {
            $member = Member::find($id);
            if ($member)
            {
                $membersStr .= "ANUAL:{$member->name}, ";
            }
        }

        foreach ($members_se as $key => $id) {
            $member = Member::find($id);
            if ($member)
            {
                $membersStr .= "SEM:{$member->name}, ";
            }
        }

        $mMember = Member::find($memberId);

        // $this->model->paymentMethods->attach($paymentMethod->id, ['customer' => $customer_id]);
        $intent = $this->model->paymentIntents->create([
            'amount'   => $amount,
            'currency' => 'mxn',
            // 'customer' => $customer,
            'description' => $membersStr,
            'confirm'  => true,
            'receipt_email' => $mMember->email,
            'return_url' => 'https://mlsbcs.com.mx/pay',
            'payment_method' => $source,
            'capture_method' => 'manual',
            'payment_method_types' => ['card'],
            "metadata" => [
                'member' => $mMember->name,
                'details' => $membersStr,
                'changerate' => $this->paymentInfo['changerate'],
                'cost_anual' => $this->paymentInfo['price_anual'],
                'cost_sem'   => $this->paymentInfo['price_sem'],
            ],
        ]);

        if ($intent['status'] == 'requires_payment_method') {
            throw new \Exception("Something happen, please try again with another payment method.", 1);
        }

        if ($intent['status'] == 'requires_action' || $intent['status'] == 'requires_source_action') {
            $mPayment->id_transaction = $intent['id'];
            $mPayment->save();
            return $intent;
        }

        // return $paymentMethod;

        // $intent = $this->model->paymentIntents->create([
        //     'amount'   => $amount,
        //     'currency' => 'mxn',
        //     // 'customer' => $customer,
        //     'description' => $membersStr,
        //     'confirm'  => true,
        //     'payment_method' => $source,
        //     'capture_method' => 'manual',
        //     'payment_method_types' => ['card'],
        //     'request_three_d_secure' => true,
        //     "metadata" => [
        //         'member' => $mMember->name,
        //         'details' => $membersStr,
        //         'changerate' => $this->paymentInfo['changerate'],
        //         'cost_anual' => $this->paymentInfo['price_anual'],
        //         'cost_sem'   => $this->paymentInfo['price_sem'],
        //     ],
        // ]);

        // var_dump($intent);
        $intentCapture = $intent->capture();

        if ($intentCapture['status'] === 'succeeded')
        {
            $mPayment->status = 'completed';
            $mPayment->save();

            $due = new \DateTime();
            $due->modify("+6 months");
            foreach ($members_se as $member) {
                $mMember = MemberPayment::create([
                    'payment_id' => $mPayment->id,
                    'member_id' => $member,
                    'due' => $due->format('Y-m-d H:i:s'),
                ]);
            }

            $due->modify("+6 months");
            foreach ($members_an as $member) {
                $mMember = MemberPayment::create([
                    'payment_id' => $mPayment->id,
                    'member_id' => $member,
                    'due' => $due->format('Y-m-d H:i:s'),
                ]);
            }
        }

        return $intentCapture;
    }

    function createIntent(int $service, float $amount, string $customer, string $paymentMethod)
    {
        try {
            $intent = $this->model->paymentIntents->create([
                'amount'   => $amount,
                'currency' => 'mxn',
                'customer' => $customer,
                'confirm'  => true,
                'payment_method' => $paymentMethod,
                'capture_method' => 'manual',
                // 'payment_method_types' => ['card'],
                "metadata" => ["order_id" => $service]
            ]);
            $this->updateOrCreateIntent($intent, $service);
        } catch (\Stripe\Exception\CardException $e) {
            // Error code will be authentication_required if authentication is needed
            $payment_intent_id = $e->getError()->payment_intent->id;
            $payment_intent = $this->model->paymentIntents->retrieve($payment_intent_id);
            echo 'Error code is:' . $e->getError()->code;
            var_dump($e->getError());
            var_dump('-------------------');
            var_dump($e->getMessage());
            die;
        }
    }

    public function cancelIntent(string $intent_id)
    {
        $payment_intent = $this->model->paymentIntents->retrieve($intent_id);
        if ($payment_intent) {
            $intent = $payment_intent->cancel();
            $this->updateOrCreateIntent($intent);
            return true;
        }
        return false;
    }

    public function confirmIntentByService(int $travelId)
    {
        $paymentIntent = PaymentIntent::where('service_id', $travelId)->orderBy('created_at', 'DESC')->first();

        if($paymentIntent)
        {
            return $this->confirmIntent($paymentIntent->stripe_id);
        }

        return false;
    }

    public function cancelIntentByService(int $travelId)
    {
        $paymentIntent = PaymentIntent::where('service_id', $travelId)->orderBy('created_at', 'DESC')->first();

        if($paymentIntent)
        {
            return $this->cancelIntent($paymentIntent->stripe_id);
        }

        return false;
    }

    public function confirmIntent(string $intent_id)
    {
        $payment_intent = $this->model->paymentIntents->retrieve($intent_id);
        if ($payment_intent) {
            // $intent = $payment_intent->confirm();
            $intentCapture = $payment_intent->capture();
            $this->updateOrCreateIntent($intentCapture);
            return true;
        }

        return false;
    }

    private function updateOrCreateIntent($intent, $travelId = null)
    {
        $charge = count($intent['charges']['data']) > 0 ? $intent['charges']['data'][0] : [];
        $outcome = isset($charge['outcome']) ? $charge['outcome'] : [];
        $paymentMethod = isset($charge['payment_method_details']) ? $charge['payment_method_details'] : [];

        $paymentIntent = PaymentIntent::where('stripe_id', $intent['id'])->first();
        if ($paymentIntent) {
            $paymentIntent->amount_received = $intent['amount_received'];
            $paymentIntent->charge_id = $charge['id'];
            $paymentIntent->customer_id = $charge['customer'];
            $paymentIntent->failure_message = $charge['failure_message'];
            $paymentIntent->failure_code = $charge['failure_code'];
            $paymentIntent->network_status = $outcome['network_status'];
            $paymentIntent->reason = $outcome['reason'];
            $paymentIntent->risk_level = $outcome['risk_level'];
            $paymentIntent->risk_score = $outcome['risk_score'];
            $paymentIntent->seller_message = $outcome['seller_message'];
            $paymentIntent->type = $outcome['type'];
            $paymentIntent->paid = $charge['paid'];
            $paymentIntent->payment_method_country = $paymentMethod['country'];
            $paymentIntent->payment_method_brand = $paymentMethod['card']['brand'];
            $paymentIntent->payment_method_exp_month = $paymentMethod['exp_month'];
            $paymentIntent->payment_method_exp_year = $paymentMethod['exp_year'];
            $paymentIntent->payment_method_exp_last4 = $paymentMethod['last4'];
            $paymentIntent->status = $intent['status'];
            $paymentIntent->save();

            if ($paymentIntent->status === 'succeeded')
            {
                $pm = new PaymentMethod();
                $payment = Payment::create([
                    'auth' => $paymentMethod['fingerprint'],
                    'last_4' => $paymentMethod['last4'],
                    'amount' => $intent['amount_received'] / 100,
                    'factured' => 0,
                    'travel_id' => (int)$charge['metadata']['order_id'],
                    'payment_method_id' => $pm->getPaymentMethodCard()->id,
                ]);
            }
        } else {
            PaymentIntent::create([
                'stripe_id' => $intent['id'],
                'amount'    => $intent['amount'],
                'amount_capturable' => $intent['amount_capturable'],
                'amount_received'   => $intent['amount_received'],
                'capture_method'    => $intent['capture_method'],
                'charge_id'   => isset($charge['id']) ? $charge['id'] : null,
                'customer_id' => isset($charge['customer']) ? $charge['customer'] : null,
                'failure_message' => isset($charge['failure_message']) ? $charge['failure_message'] : null,
                'failure_code'    => isset($charge['failure_code']) ? $charge['failure_code'] : null,
                'network_status'  => isset($outcome['network_status']) ? $outcome['network_status'] : null,
                'reason'     => isset($outcome['reason']) ? $outcome['reason'] : null,
                'risk_level' => isset($outcome['risk_level']) ? $outcome['risk_level'] : null,
                'risk_score' => isset($outcome['risk_score']) ? $outcome['risk_score'] : null,
                'seller_message'  => isset($outcome['seller_message']) ? $outcome['seller_message'] : null,
                'type' => isset($outcome['type']) ? $outcome['type'] : null,
                'paid' => isset($charge['paid']) ? $charge['paid'] : null,
                'payment_method_country' => isset($paymentMethod['country']) ? $paymentMethod['country'] : null,
                'payment_method_brand'   => isset($paymentMethod['card']['brand']) ? $paymentMethod['card']['brand'] : null,
                'payment_method_exp_month' => isset($paymentMethod['exp_month']) ? $paymentMethod['exp_month'] : null,
                'payment_method_exp_year'  => isset($paymentMethod['exp_year']) ? $paymentMethod['exp_year'] : null,
                'payment_method_exp_last4' => isset($paymentMethod['last4']) ? $paymentMethod['last4'] : null,
                'status' => $intent['status'],
                'service_id' => $travelId,
            ]);
        }


    }

    function refund($intent, $charge)
    {
        try {
            $refund = $this->model->refunds->create([
                'charge' => $charge,
            ]);

            StripeRefund::create([
                'intent_id' => $intent,
                'reason' => $refund['reason'],
                'status' => $refund['status'],
            ]);
        } catch(Exception $e) {
            throw $e;
        }
    }

    function deletePaymentMethod($id)
    {
        return $this->model->paymentMethods->detach($id, []);
    }

    function getPaymentMethods($customer_id)
    {
        return $this->model->paymentMethods->all([
            'customer' => $customer_id,
            'type' => 'card',
        ]);
    }

}
