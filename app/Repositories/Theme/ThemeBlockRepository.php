<?php

namespace App\Repositories\Theme;

use App\Models\Theme\ThemeBlock;
use Illuminate\Database\Eloquent\Collection;

class ThemeBlockRepository
{
    /**
     * Find block by ID
     */
    public function find(int $id): ?ThemeBlock
    {
        return ThemeBlock::find($id);
    }

    /**
     * Get all blocks for a section
     */
    public function getAllForSection(int $sectionId): Collection
    {
        return ThemeBlock::where('section_id', $sectionId)
            ->orderBy('position')
            ->get();
    }

    /**
     * Create a new block
     */
    public function create(array $data): ThemeBlock
    {
        return ThemeBlock::create($data);
    }

    /**
     * Update block
     */
    public function update(ThemeBlock $block, array $data): ThemeBlock
    {
        $block->update($data);
        return $block->fresh();
    }

    /**
     * Delete block
     */
    public function delete(ThemeBlock $block): bool
    {
        return $block->delete();
    }

    /**
     * Reorder blocks within a section
     */
    public function reorder(array $blockIds): void
    {
        foreach ($blockIds as $position => $blockId) {
            ThemeBlock::where('id', $blockId)
                ->update(['position' => $position]);
        }
    }

    /**
     * Get next position for a section
     */
    public function getNextPosition(int $sectionId): int
    {
        $maxPosition = ThemeBlock::where('section_id', $sectionId)
            ->max('position');

        return ($maxPosition ?? -1) + 1;
    }

    /**
     * Find block by handle for a section
     */
    public function findByHandle(int $sectionId, string $handle): ?ThemeBlock
    {
        return ThemeBlock::where('section_id', $sectionId)
            ->where('handle', $handle)
            ->first();
    }

    /**
     * Duplicate block
     */
    public function duplicate(ThemeBlock $block, int $newSectionId = null): ThemeBlock
    {
        $newBlock = $block->replicate();
        
        if ($newSectionId) {
            $newBlock->section_id = $newSectionId;
        }
        
        // Get next position
        $newBlock->position = $this->getNextPosition($newBlock->section_id);
        $newBlock->save();

        return $newBlock;
    }
}
