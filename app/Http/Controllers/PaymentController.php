<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\TryCatch;
use Stripe\StripeClient;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['users'] = User::all();
        return view('payment.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|gt:0',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            $data['status'] = false;
            $data['message'] = "Validation failed!";
            $data['errors'] =  $validator->errors();
            return response()->json($data, 422);
        }

        DB::beginTransaction();
        try {

            $transactionId = uniqid('txn_');

            $transaction = new Transaction();
            $transaction->user_id = $request->user_id;
            $transaction->amount = $request->amount;
            $transaction->transaction_id = $transactionId;
            $transaction->status = 'open';

            if($transaction->save()){
                $stripe = new StripeClient(config('stripe.stipe_sk'));
                $stripe_response =  $stripe->checkout->sessions->create([
                    'line_items' => [[
                        # Provide the exact Price ID (e.g. pr_1234) of the product you want to sell
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => $transaction->transaction_id,
                            ],
                            'unit_amount' => $transaction->amount

                        ],
                        'quantity' => 1,
                      ]],
                    'mode' => 'payment',
                    'success_url' => route('payment.success').'?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('payment.failed'),
                ]);

                Session::flash('transaction_id', $transaction->transaction_id);

                $data['stripe_response'] = $stripe_response;
                $data['transaction'] = $transaction;
            }

            $data['status'] = true;
            $data['message'] = "Order placed and processing your payment!";
            DB::commit();
            return response()->json($data, 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            $data['status'] = false;
            $data['message'] = "Sorry, failed to process your payment!";
            $data['errors'] = $th;
            return response()->json($data, 500);
        }
    }

    public function success(Request $request)
    {

        $stripe = new StripeClient(config('stripe.stipe_sk'));
        $stripe_response =  $stripe->checkout->sessions->retrieve($request->session_id);

        $transaction_id = Session::get('transaction_id');
        if($transaction_id){
            $transaction = Transaction::where('transaction_id', $transaction_id)->first();
            if ($transaction) {
                try {
                    $transaction->status = 'success';
                    $transaction->stripe_response = json_encode($stripe_response);
                    $transaction->save();
                    Session::flash('success_message', 'Your payment has been successfully done!');
                } catch (\Throwable $th) {
                    Session::flash('success_message', 'Payment successful. Contact support for any query!');
                }
            }
        }

        return redirect()->route('payment.create');
    }

    public function failed()
    {
        $transaction_id = Session::get('transaction_id');
        if($transaction_id){
            $transaction = Transaction::where('$transaction_id', $transaction_id)->first();
            if ($transaction) {
                try {
                    $transaction->status = 'failure';
                    $transaction->save();
                    Session::flash('error_message', 'Failed to process your payment');
                } catch (\Throwable $th) {
                    Session::flash('error_message', 'Failed to process your order');
                }
            }
        }

        return redirect()->route('payment.create');
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
