<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SaleInvoiceResource;
use App\Models\Picker;
use App\Models\SaleInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PreparerSaleInvoiceController extends BaseController
{
    /**
     * Display a listing of approved invoices ready for preparation.
     */
    public function index(Request $request): JsonResponse
    {
        $employee = $request->user();
        
        $query = SaleInvoice::where('request_status', 'approved')
            ->whereIn('delivery_status', ['not_prepared', 'preparing'])
            ->with(['customer', 'representative', 'items']);

        if ($request->has('delivery_status')) {
            $query->where('delivery_status', $request->get('delivery_status'));
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->paginate($request->get('per_page', 15));

        return $this->successResponse(SaleInvoiceResource::collection($invoices));
    }

    /**
     * Display the specified invoice.
     */
    public function show(SaleInvoice $invoice): JsonResponse
    {
        if ($invoice->request_status !== 'approved') {
            return $this->errorResponse('Only approved invoices can be viewed', 403);
        }

        $invoice->load(['customer', 'representative', 'items.product', 'creator']);
        return $this->successResponse(new SaleInvoiceResource($invoice));
    }

    /**
     * Start preparing an invoice.
     */
    public function startPreparing(SaleInvoice $invoice): JsonResponse
    {
        if ($invoice->request_status !== 'approved') {
            return $this->errorResponse('Only approved invoices can be prepared', 422);
        }

        if ($invoice->delivery_status !== 'not_prepared') {
            return $this->errorResponse('Invoice is already being prepared or prepared', 422);
        }

        try {
            $employee = request()->user();
            
            if (!$invoice->changeDeliveryStatus('preparing', $employee->emp_id, 'employee')) {
                return $this->errorResponse('Cannot change delivery status', 422);
            }

            return $this->successResponse(
                new SaleInvoiceResource($invoice->load(['customer', 'representative', 'items.product'])),
                'Preparing started successfully'
            );
        } catch (\Exception $e) {
            Log::error('Start preparing error: ' . $e->getMessage());
            return $this->errorResponse('Failed to start preparing', 500);
        }
    }

    /**
     * Complete preparing an invoice.
     */
    public function completePreparing(SaleInvoice $invoice): JsonResponse
    {
        if ($invoice->request_status !== 'approved') {
            return $this->errorResponse('Only approved invoices can be prepared', 422);
        }

        if ($invoice->delivery_status !== 'preparing') {
            return $this->errorResponse('Invoice is not in preparing status', 422);
        }

        try {
            $employee = request()->user();
            
            if (!$invoice->changeDeliveryStatus('prepared', $employee->emp_id, 'employee')) {
                return $this->errorResponse('Cannot change delivery status', 422);
            }

            return $this->successResponse(
                new SaleInvoiceResource($invoice->load(['customer', 'representative', 'items.product'])),
                'Preparing completed successfully'
            );
        } catch (\Exception $e) {
            Log::error('Complete preparing error: ' . $e->getMessage());
            return $this->errorResponse('Failed to complete preparing', 500);
        }
    }

    /**
     * Assign invoice to a driver.
     */
    public function assignToDriver(Request $request, SaleInvoice $invoice): JsonResponse
    {
        if ($invoice->request_status !== 'approved') {
            return $this->errorResponse('Only approved invoices can be assigned', 422);
        }

        if ($invoice->delivery_status !== 'prepared') {
            return $this->errorResponse('Invoice must be prepared before assigning to driver', 422);
        }

        $validated = $request->validate([
            'driver_id' => ['required', 'exists:pickers,picker_id'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $driver = Picker::findOrFail($validated['driver_id']);
            
            if (!$invoice->assignToDriver($validated['driver_id'])) {
                return $this->errorResponse('Cannot assign invoice to driver', 422);
            }

            if ($validated['notes'] ?? null) {
                $invoice->notes = ($invoice->notes ? $invoice->notes . "\n" : '') . "Preparer notes: " . $validated['notes'];
                $invoice->save();
            }

            return $this->successResponse(
                new SaleInvoiceResource($invoice->load(['customer', 'representative', 'driver', 'items.product'])),
                'Invoice assigned to driver successfully'
            );
        } catch (\Exception $e) {
            Log::error('Assign driver error: ' . $e->getMessage());
            return $this->errorResponse('Failed to assign invoice to driver', 500);
        }
    }
}
