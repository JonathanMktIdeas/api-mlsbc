<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MemberService;
use Exception;

class MemberController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(MemberService $service, Request $request)
    {
        parent::__construct($service, $request);
    }

    public function recover()
    {
        $this->validate($this->request, [
            'email' => 'required|exists:members,email',
        ]);

        try {
            $data = $this->service->recover();
            return response()->json([
                'data' => $data,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function init()
    {
        try {
            $data = $this->service->getPaymentInfo();

            return response()->json([
                'data' => $data
            ]);
        } catch (Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function login()
    {
        $this->validate($this->request, [
            'email' => 'required|email',
            'passwd' => 'required',
        ]);

        try {
            $model = $this->service->login();
            return response()->json([
                'data' => $model,
            ], 200, ['x-access' => $model->jwt()]);

        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function me()
    {
        try {
            $model = $this->service->me();
            return response()->json([
                'data' => $model,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function pay()
    {
        $this->validate($this->request, [
            'members_an' => 'array',
            'members_se' => 'array',
            'holder_name' => 'required|string',
            'card_number' => 'required|min:15|max:16',
            'exp' => 'required',
            'cvv' => 'required|min:3|max:4',
        ]);

        try {
            $model = $this->service->pay();
            return response()->json([
                'data' => $model,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function confirmPay()
    {
        $this->validate($this->request, [
            'id' => 'required|exists:payments,id_transaction',
        ]);

        try {
            $model = $this->service->confirmPay();
            return response()->json([
                'data' => $model,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function url()
    {
        $this->validate($this->request, [
            // 'token' => 'required',
            'members_an' => 'array',
            'members_se' => 'array',
        ]);

        try {
            $model = $this->service->url();
            return response()->json([
                'data' => $model,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function confirm()
    {
        $this->validate($this->request, [
            'charge' => 'required|exists:payments,id_transaction',
        ]);

        try {
            $model = $this->service->confirm();
            return response()->json([
                'data' => $model,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function paginated()
    {
        try {
            $resp = $this->service->paginated();
            return response()->json([
                'data' => $resp,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function kya()
    {
        try {
            $resp = $this->service->kya();
            return response()->json([
                'data' => $resp,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function list()
    {
        try {
            $resp = $this->service->list();
            return response()->json([
                'data' => $resp,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function sync()
    {
        try {
            $resp = $this->service->sync();
            if (!$resp) {
                return response()->json([
                    'data' => NULL,
                ], 400);
            }
            return response()->json([
                'data' => $resp,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function newsletter()
    {
        $this->validate($this->request, ['email' => 'required|email']);

        try {
            $resp = $this->service->newsletter();
            if (!$resp) {
                return response()->json([
                    'data' => NULL,
                ], 400);
            }

            return response()->json([
                'data' => $resp,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function subscribe()
    {
        try {
            $resp = $this->service->subscribe();
            if (!$resp) {
                return response()->json([
                    'data' => NULL,
                ], 400);
            }

            return response()->json([
                'data' => $resp,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function rememberLogin()
    {
        try {
            $resp = $this->service->rememberLogin();
            if (!$resp) {
                return response()->json([
                    'data' => NULL,
                ], 400);
            }

            return response()->json([
                'data' => $resp,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function updatePassword()
    {
        $this->validate($this->request, ['password' => 'required|string']);
        try {
            $resp = $this->service->updatePassword();
            if (!$resp) {
                return response()->json([
                    'data' => NULL,
                ], 400);
            }

            return response()->json([
                'data' => $resp,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function board()
    {
        try {
            $resp = $this->service->board();
            if (!$resp) {
                return response()->json([
                    'data' => NULL,
                ], 400);
            }

            return response()->json([
                'data' => $resp,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    // public function setPasswords()
    // {
    //     try {
    //         $resp = $this->service->setPasswords();
    //
    //         return response()->json([
    //             'data' => $resp,
    //         ]);
    //     } catch(Exception $e) {
    //         return response()->json([
    //             'data' => null,
    //             'message' => $e->getMessage(),
    //             'code' => $e->getCode(),
    //         ], 400);
    //     }
    // }


}
