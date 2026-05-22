<?php

declare(strict_types=1);

namespace App\Repositories\Cms\Documentation;

use App\Models\Cms\CmsDocumentSection;
use Illuminate\Database\Eloquent\Collection;

class CmsDocumentSectionRepository
{
    public function findById(int $id): ?CmsDocumentSection
    {
        return CmsDocumentSection::find($id);
    }

    public function getAll(): Collection
    {
        return CmsDocumentSection::orderBy('sort_order')
            ->get();
    }

    public function create(array $data): CmsDocumentSection
    {
        return CmsDocumentSection::create($data);
    }

    public function update(CmsDocumentSection $section, array $data): bool
    {
        return $section->update($data);
    }

    public function delete(CmsDocumentSection $section): bool
    {
        return $section->delete();
    }
}
