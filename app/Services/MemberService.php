<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Agency;
use App\Models\Newsletter;
use App\Models\Configuration;
use App\Services\StripeService;
use App\Services\OpenPayService;
use Illuminate\Support\Facades\Hash;
use App\Services\MailService;
use Exception;
use DB;

class MemberService extends BaseService
{
    private $request;
    public function __construct(Member $model, Request $request)
    {
        $this->model = $model;
        $this->request = $request;
        $this->ops = new OpenPayService($this);
        $this->sts = new StripeService($this);
        $this->mailService = new MailService();
    }

    private function randomPassword()  {
        $digits = '1234567890abcdefghijklmnoqrstuvqwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < 10; $i++) {
            $randomString .= $digits[rand(0, strlen($digits) - 1)];
        }

        return $randomString;
    }

    public function recover()
    {
        $email  = $this->request->get('email');
        $member = $this->model->where('email', $email)->first();
        if (!$member) throw new Exception("Member not found", 1);

        $newPassord = $this->randomPassword();
        $password = Hash::make($newPassord);

        $member->password = $password;
        $member->save();

        $data = [
            'name' => $member->name,
            'password' => $newPassord,
        ];
        $send = $this->mailService->send('New password', $member->email, $member->name, 'recover', $data);

        return $send;
    }

    public function getPaymentInfo($withPrivatekeys = false)
    {
        $paymentMode = Configuration::where('key', 'payment')->first();

        if (!$paymentMode) throw new Exception("Error get payment method", 1);

        if ($paymentMode->value === 'openpay' || $paymentMode->value === 'stripe')
        {
            $isProd = false;
            $mode = Configuration::where('key', "{$paymentMode->value}_production")->first();

            $keyId  = "{$paymentMode->value}_id_";
            $apiKey = "{$paymentMode->value}_apikey_";
            $privKey = "{$paymentMode->value}_priv_key_";

            if ($mode && $mode->value === 'true')
            {
                $idProd = true;
                $keyId .= 'production';
                $apiKey .= 'production';
                $privKey .= 'production';
            }
            else
            {
                $keyId .= 'sandbox';
                $apiKey .= 'sandbox';
                $privKey .= 'sandbox';
            }

            $mApiKey = Configuration::where('key', $apiKey)->first();
            $mKeyId  = Configuration::where('key', $keyId)->first();
            $changerate = Configuration::where('key', 'changerate_usd')->first();
            $priceAnual = Configuration::where('key', 'price_anual')->first();
            $priceSem   = Configuration::where('key', 'price_sem')->first();

            $data = [
                'id'  => $mApiKey->value,
                'key' => $mKeyId->value,
                'production' => $isProd,
                'price_anual' => (float)$priceAnual->value,
                'price_sem' => (float)$priceSem->value,
                'mode' => $paymentMode->value,
                'changerate' => (float)$changerate->value,
            ];

            if ($withPrivatekeys)
            {
                $privKey = Configuration::where('key', $privKey)->first();

                if ($privKey)
                {
                    $data['priv'] = $privKey->value;
                }
            }

            return $data;
        }
        else
        {
            throw new Exception("stripe not available yet", 2);
        }
    }

    private function verifyOpenPayCustomer(Member $model)
    {
        if (!$model->customer_op)
        {
            $customer = $this->ops->createCustomer($model->name, '', $model->email, '');
            $model->customer_op = $customer->id;
            $model->save();

            return $customer->id;
        }

        return $model->customer_op;
    }

    public function login()
    {
        $email    = $this->request->get('email');
        $password = $this->request->get('passwd');

        $model = $this->model->where('email', $email)->first();

        if (!$model) {
            throw new Exception('User not found', 404);
        }

        $paymentInfo = $this->getPaymentInfo(true);

        if($paymentInfo['mode'] === 'openpay')
        {
            $this->verifyOpenPayCustomer($model);
        }

        // if (!$model->status) {
        //     throw new Exception($co['msg'], $co['code']);
        // }

        $model->last_auth = date('Y-m-d');
        $model->save();

        if (!Hash::check($password, $model->password)) {
            throw new Exception('Wrong password', 100);
        }

        return $model;
    }

    public function me()
    {
        $model = $this->model->find($this->request->auth->id);

        if (!$model) {
            throw new Exception('User not found', 404);
        }

        return $model->load('agency');
    }

    public function url()
    {
        $model   = $this->model->find($this->request->auth->id);
        // $token   = $this->request->get('token');
        $pAmount = $this->request->get('amount');

        $paymentInfo = $this->getPaymentInfo(true);
        $members_an = $this->request->get('members_an');
        $members_se = $this->request->get('members_se');
        // $customerId = $this->verifyOpenPayCustomer($model);
        $changerate = $paymentInfo['changerate'];
        // $name = $this->request->get('holder_name');
        $name = $model->name;
        // $number = $this->request->get('card_number');
        // $cvv = $this->request->get('cvv');
        // $exp = $this->request->get('exp');
        // $expSplitted = explode('/', $exp);
        // $exp_m = $expSplitted[0];
        // $exp_y = $expSplitted[1];
        $amount_an = $paymentInfo['price_anual'] * $changerate;
        $amount_se = $paymentInfo['price_sem'] * $changerate;
        $amount_an *= count($members_an);
        $amount_se *= count($members_se);
        $amount = number_format((float)($amount_an + $amount_se), 2, '.', '');

        if ($pAmount && $amount != $pAmount) throw new Exception("The changerate has been changed during the transaction, try again, {$amount},{$pAmount}", 10);

        $srv = NULL;

        if ($paymentInfo['mode'] == 'openpay')
        {
            $srv = $this->ops;
            // $card = $this->ops->createSource($name, $number, $cvv, $exp_m, $exp_y);
            // $resp = $this->ops->makePayment($model->id, $card->id, $amount, $name, $model->phone, $model->email, $this->request->get('session'), $members_an, $members_se);
            // $card = $srv->createSource($name, $number, $cvv, $exp_m, $exp_y);
            // $resp = $srv->makePayment($model->id, $card->id, $amount, $name, $model->phone, $model->email, $this->request->get('session'), $members_an, $members_se);
            $resp = $srv->createUrl($model->id, null, $amount, $name, $model->phone, $model->email, $this->request->get('session'), $members_an, $members_se);
        }

        // $srv = $this->ops;
        // $srv->createUrl();
        return $resp;
    }

    public function confirmPay()
    {
        $srv = $this->sts;
        $resp = $srv->confirmPyament($this->request->get('id'));

        return $resp;
    }

    public function pay()
    {
        // $changerate = 19.9510000;
        $model   = $this->model->find($this->request->auth->id);
        $pAmount = $this->request->get('amount');

        if (!$model) {
            throw new Exception('User not found', 404);
        }

        $paymentInfo = $this->getPaymentInfo(true);
        $members_an = $this->request->get('members_an');
        $members_se = $this->request->get('members_se');
        // $customerId = $this->verifyOpenPayCustomer($model);
        $changerate = $paymentInfo['changerate'];
        $name = $this->request->get('holder_name');
        // $name = $model->name;
        $number = $this->request->get('card_number');
        $cvv = $this->request->get('cvv');
        $exp = $this->request->get('exp');
        $expSplitted = explode('/', $exp);
        $exp_m = $expSplitted[0];
        $exp_y = $expSplitted[1];
        $amount_an = $paymentInfo['price_anual'] * $changerate;
        $amount_se = $paymentInfo['price_sem'] * $changerate;
        $amount_an *= count($members_an);
        $amount_se *= count($members_se);
        $amount = number_format((float)($amount_an + $amount_se), 2, '.', '');

        if ($pAmount && $amount != $pAmount) throw new Exception("The changerate has been changed during the transaction, try again, {$amount},{$pAmount}", 10);

        $srv = NULL;

        if ($paymentInfo['mode'] == 'openpay')
        {
            $srv = $this->ops;
            // $card = $this->ops->createSource($name, $number, $cvv, $exp_m, $exp_y);
            // $resp = $this->ops->makePayment($model->id, $card->id, $amount, $name, $model->phone, $model->email, $this->request->get('session'), $members_an, $members_se);
            // $card = $srv->createSource($name, $number, $cvv, $exp_m, $exp_y);
            // $resp = $srv->makePayment($model->id, $card->id, $amount, $name, $model->phone, $model->email, $this->request->get('session'), $members_an, $members_se);
        }
        else if ($paymentInfo['mode'] == 'stripe')
        {
            $amount = (string)(int)($amount * 100);
            $srv = $this->sts;
            $card = $srv->createSource($name, $number, $cvv, $exp_m, $exp_y);
            $resp = $srv->makePayment($model->id, $card['id'], $amount, $name, $model->phone, $model->email, $members_an, $members_se);
        }

        return $resp;
    }

    public function confirm()
    {
        $charge = $this->request->get('charge');
        $srv    = $this->ops;

        $data = $srv->confirm($charge);

        return $data;
    }

    public function paginated()
    {
    	$search    = !is_null($this->request->get('search')) ? $this->request->get('search') : NULL;
        $paginated = !is_null($this->request->get('paginated')) ? (bool)$this->request->get('paginated') : false;
        $limit     = !is_null($this->request->get('limit')) ? (int)$this->request->get('limit') : 25;
        $offset    = !is_null($this->request->get('offset')) ? (int)$this->request->get('offset') : 1;
        $sort      = !is_null($this->request->get('sort')) ? $this->request->get('sort') : NULL;
        $sortBy    = !is_null($this->request->get('sortBy')) ? $this->request->get('sortBy') : NULL;
        $travel_id = !is_null($this->request->get('travel_id')) ? $this->request->get('travel_id') : NULL;
        $name      = !is_null($this->request->get('name')) ? $this->request->get('name') : NULL;
        $company   = !is_null($this->request->get('company')) ? $this->request->get('company') : NULL;

        if ($offset == 0)
        {
            $offset = 1;
        }

        $now   = date('Y-m-d H:i:s');
        $data  = $this->model->select('members.*')->with([
            'agency',
            'membership' => function($query) use($now) {
                $query
                    ->where('due', '>=', $now)
                    ->orderBy('due', 'DESC')
                    ->first();
            }
        ]);
        $total = $data->count();
        $take  = ($offset - 1) * $limit;

        if (!is_null($name) && !empty($name)){
            $names = explode(' ', $name);
            $data  = $data
                ->where('ignore_flex', 0)->where(function($q) use($names) {
                    foreach ($names as $name) {
                        $q->orWhere('name', 'LIKE', "%$name%");
                    }
                });
        }

        if (!is_null($company) && !empty($company)){
            $data  = $data->join('agencies', 'agencies.id', '=', 'members.agency_id')->where('agencies.name', 'LIKE', "%$company%");
        }


        $total = $data->count();

        if ($paginated)
        {
            $data = $data->offset($take)->limit($limit);

            $response = [
                'offset' => $offset,
                'limit'  => $limit,
                'total'  => $total,
                'search' => $search
            ];

            if (!is_null($sort) && !is_null($sortBy) && ($sort == 'asc' || $sort == 'desc'))
            {
                $data = $data->orderBy($sortBy, $sort);
                $response['sort'] = $sort;
                $response['sortBy'] = $sortBy;
            }

            $response['data'] = $data->get();

            return $response;
        }
        else
        {
            return $data->get();
        }
    }

    public function kya()
    {
    	$lastname = $this->request->get('lastname');
        $names = explode(' ', $lastname);

        $now   = date('Y-m-d H:i:s');
        $data  = $this->model->select("members.*")->with([
            'agency',
            // 'membership' => function($query) use($now) {
            //     $query
            //         ->where('due', '>=', $now)
            //         ->orderBy('due', 'DESC')
            //         ->first();
            // }
        ])->where('ignore_flex', 0)->where(function($q) use($names) {
            foreach ($names as $name) {
                $q->orWhere('name', 'LIKE', "%$name%");
            }
        });

        return $data->get();
    }

    public function list()
    {
        $id = $this->request->auth->id;
        $model = $this->model->find($id);

        if (!$model) {
            throw new Exception("Member no exist", 404);
        }

    	$search    = !is_null($this->request->get('search')) ? $this->request->get('search') : NULL;
        $paginated = !is_null($this->request->get('paginated')) ? (bool)$this->request->get('paginated') : false;
        $limit     = !is_null($this->request->get('limit')) ? (int)$this->request->get('limit') : 25;
        $offset    = !is_null($this->request->get('offset')) ? (int)$this->request->get('offset') : 1;
        $sort      = !is_null($this->request->get('sort')) ? $this->request->get('sort') : NULL;
        $sortBy    = !is_null($this->request->get('sortBy')) ? $this->request->get('sortBy') : NULL;
        $travel_id = !is_null($this->request->get('travel_id')) ? $this->request->get('travel_id') : NULL;

        if ($offset == 0)
        {
            $offset = 1;
        }

        $now   = date('Y-m-d H:i:s');
        $data  = $this->model->where('ignore_flex', 0)->with([
            'agency',
            'membership' => function($query) use($now) {
                $query
                    ->where('due', '>=', $now)
                    ->orderBy('due', 'DESC')
                    ->first();
            }
        ]);
        $total = $data->count();
        $take  = ($offset - 1) * $limit;

        if (!is_null($search) && !empty($search)){
            $names = explode(' ', $search);
            $data  = $data
                ->where('ignore_flex', 0)->where(function($q) use($names) {
                    foreach ($names as $name) {
                        $q->orWhere('name', 'LIKE', "%$name%");
                    }
                });
        }
        else
        {
            $data = $data->where('agency_id', $model->agency_id);
            $total = $data->count();
        }

        if ($paginated)
        {
            $data = $data->offset($take)->limit($limit);

            $response = [
                'offset' => $offset,
                'limit'  => $limit,
                'total'  => $total,
                'search' => $search
            ];

            if (!is_null($sort) && !is_null($sortBy) && ($sort == 'asc' || $sort == 'desc'))
            {
                $data = $data->orderBy($sortBy, $sort);
                $response['sort'] = $sort;
                $response['sortBy'] = $sortBy;
            }

            $response['data'] = $data->get();

            return $response;
        }
        else
        {
            return $data->get();
        }
    }

    public function sync()
    {
        // @odata.count
        $data = $this->syncFlex(1000);
        $this->deleteMembers();

        return $data;
    }

    private function syncFlex($to, $nextLink = null)
    {
        $url = 'https://replication.sparkapi.com/Reso/OData/Member?$top=1000';
        if (!is_null($nextLink)) $url = $nextLink;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer 5lsfac7l94210oyejm174vqbs',
        ));

        $response = curl_exec ($ch);
        $err = curl_error($ch);
        curl_close ($ch);
        $json  = json_decode($response, true);

        if (isset($json['error'])) {
            return null;
        }

        $data  = $json['value'];

        foreach ($data as $flexData) {
            $agency = $this->validateAgency($flexData);
            $member = $this->validateMember($flexData, $agency);
        }

        return 1;
    }

    private function deleteMembers()
    {
        $today = date('Y-m-d');

        Member::where('ignore_flex', 0)->where(function($query) {
            $query->where('sync_date', '<', $today)
                ->orWhere('sync_date', '=', null);
        })->delete();
    }

    private function validateMember($flexData, $agency)
    {
        $today = date('Y-m-d');
        $found = Member::where('email', $flexData['MemberEmail'])->withTrashed()->first();

        if ($found && !is_null($found->deleted_at)) $found->restore();

        $key = $flexData['MemberKey'];
        $url = "https://replication.sparkapi.com/Reso/OData/Member('$key')/Media";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer 5lsfac7l94210oyejm174vqbs',
        ));

        $response = curl_exec ($ch);
        $err = curl_error($ch);
        curl_close ($ch);
        $json  = json_decode($response, true);

        if (isset($json['error'])) {
            return null;
        }

        $data  = $json['value'];

        $photo = NULL;

        foreach ($data as $d) {
            if ($d['Type'] === 'Photo')
            {
                $photo = $d['Uri'];
            }
        }

        if (!$found)
        {
            $found = Member::create([
                'name'      => $flexData['MemberFullName'],
                'phone'     => $flexData['MemberPreferredPhone'],
                'mobile'    => $flexData['MemberMobilePhone'],
                'email'     => $flexData['MemberEmail'],
                'photo'     => $photo,
                'password'  => null,
                'agency_id' => $agency->id,
                'sync_date' => $today,
            ]);
        }
        else
        {
            $found->agency_id = $agency->id;
            $found->name      = $flexData['MemberFullName'];
            $found->phone     = $flexData['MemberPreferredPhone'];
            $found->mobile    = $flexData['MemberMobilePhone'];
            $found->photo     = $photo;
            $found->sync_date = $today;
            $found->save();
        }

        return $found;
    }

    private function validateAgency($flexData)
    {
        $found = Agency::where('name', $flexData['OfficeName'])->first();

        if (!$found)
        {
            $found = Agency::create([
                'name'   => $flexData['OfficeName'],
                'phone'  => $flexData['MemberOfficePhone'],
                'mobile' => null,
                'email'  => null,
            ]);
        }
        else
        {
            $found->phone  = $flexData['MemberOfficePhone'];
            $found->mobile = null;
            $found->email  = null;
        }

        return $found;
    }

    public function newsletter()
    {
        $email = $this->request->get('email');

        $newsletter = Newsletter::where('email', $email)->first();

        if (!$newsletter) {
            Newsletter::create([
                'email' => $email
            ]);
        }

        return true;
    }

    public function subscribe()
    {
        $image = $this->request->file('image');

        return $this->mailService->subscribe($image);
    }

    public function updatePassword()
    {
        try {
            $password = Hash::make($this->request->get('password'));
            $model    = $this->model->find($this->request->auth->id);

            $model->password = $password;
            $model->password_updated = date('Y-m-d');

            $model->save();

            return true;
        } catch(Exception $e) {
            return false;
        }
    }

    public function rememberLogin()
    {
        $data = $this->model->where('password', NULL)->whereRaw('DATEDIFF(last_email, current_date) > 2')->get();

        foreach ($data as $d) {
            $newPassord = $this->randomPassword();
            $password = Hash::make($newPassord);

            $d->password = $password;
            $d->save();

            $data = [
                'name' => $d->name,
                'password' => $newPassord,
            ];

            $this->mailService->send("Don't forget us", $member->email, $member->name, 'remember', $data);
        }

        return true;
    }

    public function board()
    {
        return $this->model->where('board', true)->orderBy('board_sort')->with('agency')->get();
    }

    // public function setPasswords()
    // {
    //     $password = Hash::make("MLSBCSPASSWD");
    //     $members  = DB::table('members')->update(array('password' => $password));
    //
    //     return $members;
    // }
}
