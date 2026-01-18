<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SaleInvoiceResource;
use App\Models\Customer;
use App\Models\CustomerBalance;
use App\Models\SaleInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DriverSaleInvoiceController extends BaseController
{
    /**
     * Display a listing of driver's assigned invoices.
     */
    public function index(Request $request): JsonResponse
    {
        $driver = $request->user();
        
        $query = SaleInvoice::where('assigned_to_driver', $driver->picker_id)
            ->whereIn('delivery_status', ['assigned_to_driver', 'in_delivery'])
            ->with(['customer', 'representative', 'items']);

        if ($request->has('delivery_status')) {
            $query->where('delivery_status', $request->get('delivery_status'));
        }

        $invoices = $query->orderBy('assigned_at', 'desc')->paginate($request->get('per_page', 15));

        return $this->successResponse(SaleInvoiceResource::collection($invoices));
    }

    /**
     * Display the specified invoice with customer location.
     */
    public function show(SaleInvoice $invoice): JsonResponse
    {
        $driver = request()->user();
        
        if (!$invoice->isAssignedToMe($driver->picker_id)) {
            return $this->errorResponse('Invoice not assigned to you', 404);
        }

        $invoice->load(['customer', 'representative', 'items.product', 'driver']);
        
        // Add customer location info
        $customer = $invoice->customer;
        $locationInfo = null;
        if ($customer) {
            $locationInfo = [
                'address' => $customer->address,
                'location_lat' => $customer->location_lat,
                'location_lng' => $customer->location_lng,
            ];
        }

        $resource = new SaleInvoiceResource($invoice);
        $data = $resource->toArray(request());
        $data['customer_location'] = $locationInfo;

        return $this->successResponse($data);
    }

    /**
     * Start delivery.
     */
    public function startDelivery(SaleInvoice $invoice): JsonResponse
    {
        $driver = request()->user();
        
        if (!$invoice->isAssignedToMe($driver->picker_id)) {
            return $this->errorResponse('Invoice not assigned to you', 404);
        }

        if ($invoice->delivery_status !== 'assigned_to_driver') {
            return $this->errorResponse('Invoice is not assigned to driver', 422);
        }

        try {
            if (!$invoice->changeDeliveryStatus('in_delivery', $driver->picker_id, 'driver')) {
                return $this->errorResponse('Cannot change delivery status', 422);
            }

            return $this->successResponse(
                new SaleInvoiceResource($invoice->load(['customer', 'representative', 'items.product'])),
                'Delivery started successfully'
            );
        } catch (\Exception $e) {
            Log::error('Start delivery error: ' . $e->getMessage());
            return $this->errorResponse('Failed to start delivery', 500);
        }
    }

    /**
     * Mark invoice as delivered.
     */
    public function markAsDelivered(SaleInvoice $invoice): JsonResponse
    {
        $driver = request()->user();
        
        if (!$invoice->isAssignedToMe($driver->picker_id)) {
            return $this->errorResponse('Invoice not assigned to you', 404);
        }

        if ($invoice->delivery_status !== 'in_delivery') {
            return $this->errorResponse('Invoice is not in delivery status', 422);
        }

        try {
            DB::beginTransaction();

            if (!$invoice->markAsDelivered($driver->picker_id)) {
                return $this->errorResponse('Cannot mark as delivered', 422);
            }

                // Update inventory if not posted yet (if posted was skipped, we need to post now)
                if ($invoice->status === 'draft' || $invoice->status === 'pending') {
                    // Post the invoice (update inventory, customer balance, etc.)
                    // This should ideally be done when manager approves, but we can do it here too
                    foreach ($invoice->items as $item) {
                        $product = $item->product;
                        $quantityInPieces = $item->getQuantityInPieces();
                        if ($product && $product->current_stock >= $quantityInPieces) {
                            $product->updateStock(-$quantityInPieces, 'sale');
                        }
                    }

                // Update customer balance if credit
                if ($invoice->buyer_type === 'customer' && $invoice->payment_method === 'credit' && $invoice->status !== 'paid') {
                    $customer = $invoice->customer;
                    if ($customer) {
                        $balance = CustomerBalance::getOrCreate($customer->customer_id);
                        $balance->recordTransaction(
                            'invoice',
                            $invoice->remaining_amount,
                            "فاتورة بيع: {$invoice->invoice_number}",
                            'sale_invoice',
                            $invoice->invoice_id,
                            null // No manager for driver actions
                        );

                        $customer->total_debt += $invoice->remaining_amount;
                        $customer->save();
                    }
                }
            }

            DB::commit();

            return $this->successResponse(
                new SaleInvoiceResource($invoice->load(['customer', 'representative', 'items.product'])),
                'Invoice marked as delivered successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mark delivered error: ' . $e->getMessage());
            return $this->errorResponse('Failed to mark as delivered: ' . $e->getMessage(), 500);
        }
    }
}
