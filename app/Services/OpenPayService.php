<?php

namespace App\Services;

use Openpay;
use App\Models\Payment;
use App\Models\Member;
use App\Models\MemberPayment;
use App\Services\MemberService;

class OpenPayService
{
    private $openpay;
    private $paymentInfo;

    public function __construct(BaseService $service)
    {
        $this->paymentInfo = $service->getPaymentInfo(true);

        if ($this->paymentInfo['mode'] == 'openpay')
        {
            Openpay::setSandboxMode(!$this->paymentInfo['production']);
            $this->openpay = Openpay::getInstance(
                $this->paymentInfo['key'],
                $this->paymentInfo['priv'],
                'MX'
            );
        }
    }

    public function createCustomer($name, $lastname, $email, $phone)
    {
        $customerData = array(
            'name' => $name,
            'last_name' => $lastname,
            'email' => $email,
            'phone_number' => $phone,
        );

        $customer = $this->openpay->customers->add($customerData);

        return $customer;
    }

    public function createSource($name, $number, $cvv, $exp_m, $exp_y)
    {
        $cardData = array(
            'holder_name' => $name,
            'card_number' => $number,
            'cvv2' => $cvv,
            'expiration_month' => $exp_m,
            'expiration_year' => $exp_y,
        );

        $card = $this->openpay->cards->add($cardData);

        return $card;
    }

    public function makePayment($memberId, $source, $amount, $name, $phone, $email, $ds, $members_an, $members_se)
    {
        $mPayment = Payment::create([
            'member_id' => $memberId,
            'amount' => $amount,
            'status' => 'in_progress',
        ]);

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

        $customerData = [
            'name' => $name,
            'last_name' => '',
            'phone_number' => $phone,
            'email' => $email,
        ];
        $chargeData = array(
            'method' => 'card',
            'source_id' => $source,
            'amount' => $amount,
            // 'capture' => false,
            'customer' => $customerData,
            'device_session_id' => $ds,
            'confirm' => false,
            'description' => $membersStr,
            'order_id' => "orden-{$mPayment->id}",
            'use_3d_secure' => true,
            'redirect_url' => 'https://mlsbcs.com.mx/pay'
        );

        $charge = $this->openpay->charges->create($chargeData);
        // var_dump($charge);
        // $captureData = array('amount' => $amount);
        // $capture = $charge->capture($captureData);

        // $mPayment->status = $capture->status;
        // $mPayment->save();
        //
        // if ($capture->status == 'completed')
        // {
        //     $due = new \DateTime();
        //     $due->modify("+6 months");
        //     foreach ($members_se as $member) {
        //         $mMember = MemberPayment::create([
        //             'payment_id' => $mPayment->id,
        //             'member_id' => $member,
        //             'due' => $due->format('Y-m-d H:i:s'),
        //         ]);
        //     }
        //
        //     $due->modify("+6 months");
        //     foreach ($members_an as $member) {
        //         $mMember = MemberPayment::create([
        //             'payment_id' => $mPayment->id,
        //             'member_id' => $member,
        //             'due' => $due->format('Y-m-d H:i:s'),
        //         ]);
        //     }
        // }

        return ['data' => $mPayment, 'charge' => $charge->payment_method->url];
    }

    public function createUrl($memberId, $token, $amount, $name, $phone, $email, $ds, $members_an, $members_se)
    {
        $mPayment = Payment::create([
            'member_id' => $memberId,
            'amount' => $amount,
            'status' => 'in_progress',
        ]);

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

        $customerData = [
            'name' => $name,
            'last_name' => '',
            'phone_number' => $phone,
            'email' => $email,
        ];
        $chargeData = array(
            'method' => 'card',
            // 'source_id' => $token,
            'amount' => $amount,
            // 'capture' => false,
            'customer' => $customerData,
            'device_session_id' => $ds,
            'confirm' => false,
            'description' => $membersStr,
            'order_id' => "orden-{$mPayment->id}",
            'use_3d_secure' => true,
            'redirect_url' => 'https://mlsbcs.com.mx/pay'
        );

        $charge = $this->openpay->charges->create($chargeData);

        $mPayment->id_transaction = $charge->id;
        $mPayment->save();

        return $charge->payment_method->url;
    }

    public function confirm($transaction)
    {
        // tracvbmdv9x41bojyim9
        $charge = $this->openpay->charges->get($transaction);

        $mPayment = Payment::where('id_transaction', $transaction)->first();

        if ($mPayment)
        {
            $mPayment->status = $charge->status;
            $mPayment->save();
        }
        // $captureData = array('amount' => $charge->amount);
        // $capture = $charge->capture($captureData);
        // $charge->capture(captureData);

        // Cliente
        // $customer = $openpay->customers->get(customerId);
        // $charge = $customer->charges->get(transactionId);
        // $charge->capture(captureData);

        return $charge->status;
    }
}
