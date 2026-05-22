<?php

declare(strict_types=1);

namespace App\Actions\Cms\Documentation\Admin;

use App\Repositories\Cms\Documentation\CmsDocumentRepository;
use Illuminate\Support\Facades\DB;

class ReorderDocumentsAction
{
    public function __construct(
        private CmsDocumentRepository $repository
    ) {}

    public function execute(array $orders): void
    {
        DB::transaction(function () use ($orders) {
            $this->repository->reorder($orders);
        });
    }
}
