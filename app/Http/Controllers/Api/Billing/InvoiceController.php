<?php

namespace App\Http\Controllers\Api\Billing;

use App\Enums\ErrorCode;
use App\Http\Controllers\Controller;
use App\Policies\Billing\InvoicePolicy;
use App\Repositories\Billing\BillingAccountRepository;
use App\Repositories\Billing\InvoiceRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private BillingAccountRepository $billingAccountRepo,
        private InvoiceRepository $invoiceRepo,
    ) {}

    /**
     * Get paginated invoice history.
     * 
     * GET /api/v1/merchant/billing/invoices
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $billingAccount = $this->billingAccountRepo->findByUserAccess($user);

        if (!$billingAccount) {
            return $this->error(
                message: 'Billing account not found',
                errorCode: ErrorCode::BIL_001->value,
                statusCode: 404
            );
        }

        $this->authorize('viewAny', [InvoicePolicy::class, $billingAccount]);

        $perPage = $request->integer('per_page', 15);
        $invoices = $this->invoiceRepo->getPaginatedForAccount($billingAccount->id, $perPage);

        return $this->paginated($invoices, \App\Http\Resources\Billing\InvoiceResource::collection($invoices));
    }

    /**
     * Get single invoice details.
     * 
     * GET /api/v1/merchant/billing/invoices/{invoice}
     */
    public function show(Request $request, int $invoice): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $billingAccount = $this->billingAccountRepo->findByUserAccess($user);

        if (!$billingAccount) {
            return $this->error(
                message: 'Billing account not found',
                errorCode: ErrorCode::BIL_001->value,
                statusCode: 404
            );
        }

        $this->authorize('viewAny', [InvoicePolicy::class, $billingAccount]);

        $invoiceModel = $this->invoiceRepo->findForAccount($invoice, $billingAccount->id);

        if (!$invoiceModel) {
            return $this->error(
                message: 'Invoice not found',
                errorCode: ErrorCode::BIL_002->value,
                statusCode: 404
            );
        }

        $this->authorize('view', [InvoicePolicy::class, $invoiceModel]);

        return $this->success(new \App\Http\Resources\Billing\InvoiceResource($invoiceModel));
    }

    /**
     * Download invoice PDF (redirect to Stripe hosted URL).
     * 
     * GET /api/v1/merchant/billing/invoices/{invoice}/download
     */
    public function download(Request $request, int $invoice): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $billingAccount = $this->billingAccountRepo->findByUserAccess($user);

        if (!$billingAccount) {
            return $this->error(
                message: 'Billing account not found',
                errorCode: ErrorCode::BIL_001->value,
                statusCode: 404
            );
        }

        $this->authorize('viewAny', [InvoicePolicy::class, $billingAccount]);

        $invoiceModel = $this->invoiceRepo->findForAccount($invoice, $billingAccount->id);

        if (!$invoiceModel) {
            return $this->error(
                message: 'Invoice not found',
                errorCode: ErrorCode::BIL_002->value,
                statusCode: 404
            );
        }

        $this->authorize('download', [InvoicePolicy::class, $invoiceModel]);

        if (!$invoiceModel->invoice_pdf_url) {
            return $this->error(
                message: 'Invoice PDF not available',
                errorCode: ErrorCode::BIL_002->value,
                statusCode: 404
            );
        }

        return $this->success([
            'download_url' => $invoiceModel->invoice_pdf_url,
        ]);
    }
}
