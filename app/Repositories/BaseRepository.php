<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Concerns\HasStoreScoping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use RuntimeException;

/**
 * BaseRepository
 * 
 * Enforces structural isolation for all tenant-scoped repositories.
 */
abstract class BaseRepository
{
    /**
     * The model class this repository operates on.
     */
    abstract protected function modelClass(): string;

    /**
     * Get a scoped query for the model.
     * 
     * Wave 6 Hardening: This method is the "Bottleneck" for data access.
     * It ensures that any query made through a repository is automatically 
     * scoped by the current store context.
     */
    protected function scopedQuery(): Builder
    {
        $model = App::make($this->modelClass());
        $query = $model->newQuery();

        // If the model uses HasStoreScoping, enforce the scope
        if ($this->isTenantScoped($model)) {
            $storeId = $this->getCurrentStoreId();
            
            if ($storeId === null) {
                // Step 5 Hardening: Fail-safe if context is missing
                throw new RuntimeException(sprintf(
                    'Tenant context missing for scoped repository: %s',
                    static::class
                ));
            }

            return $model->scopeForStore($query, $storeId);
        }

        return $query;
    }

    /**
     * Find a record by ID within the current scope.
     */
    public function findScoped(int $id): ?Model
    {
        return $this->scopedQuery()->find($id);
    }

    /**
     * Get the current store ID from the container.
     */
    protected function getCurrentStoreId(): ?int
    {
        return App::bound('storeId') ? (int) App::make('storeId') : null;
    }

    /**
     * Determine if a model is tenant-scoped.
     */
    private function isTenantScoped(Model $model): bool
    {
        return in_array(HasStoreScoping::class, class_uses_recursive($model), true);
    }
}
