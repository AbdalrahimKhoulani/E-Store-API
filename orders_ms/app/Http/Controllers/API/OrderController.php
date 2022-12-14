<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $orders = Order::select(['date', 'status', 'id', 'user_id'])
            ->with(['user', 'orderDetails.product'])
            ->get();

        return new JsonResponse([
            'status' => true,
            'message' => 'Orders retrieved successfully',
            'data' => $orders
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'date' => 'required',
            'products' => 'required|array'
        ]);

        if ($validator->fails()) {
            return new JsonResponse([
                'status' => false,
                'message' => $validator->errors()
            ], 500);
        }

        $order = Order::create($request->all() + ['status' => 'PROCESSING']);

        foreach ($request['products'] as $product) {
            // dd($product['id']);
            OrderDetails::create([
                'product_id' => $product['id'],
                //    'user_id'=>Auth::id()
                'user_id' => $request['user_id'],
                'order_id' => $order->id,
                'amount' => $product['amount']
            ]);
        }

        return new JsonResponse([
            'status' => true,
            'message' => 'Order created successfully',
            'data' => $order
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $order = Order::select(['date', 'status', 'id', 'user_id'])
            ->with(['user', 'orderDetails'])
            ->where('id', '=', $id)
            ->first();
        if ($order == null) {
            return new JsonResponse([
                'status' => false,
                'message' => 'No order with id ' . $id,
            ], 404);
        }
        return new JsonResponse([
            'status' => true,
            'message' => 'Order retrieved successfully',
            'data' => $order
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required'
        ]);

        $order = Order::find($id);
        if ($order == null) {
            return new JsonResponse([
                'status' => false,
                'message' => 'No order with id ' . $id,
            ], 404);
        }


        $order->update($request->all());

        return new JsonResponse([
            'status' => true,
            'message' => 'Order updated successfully',
            'data' => $order
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $order = Order::find($id);
        if ($order == null) {
            return new JsonResponse([
                'status' => false,
                'message' => 'No order with id ' . $id,
            ], 404);
        }

        $order->delete();
        OrderDetails::where('order_id', '=', $id)->delete();
        return new JsonResponse([
            'status' => true,
            'message' => 'Order deleted successfully',
        ], 204);
    }
}
