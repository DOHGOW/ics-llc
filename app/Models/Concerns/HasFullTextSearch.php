<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Shared full-text search (D-038). Phase 1 = MySQL FULLTEXT; the driver is
 * config-driven (`ics.search.driver`) so a Phase 2 swap to Scout/Meilisearch is
 * config-only (D-037). Implementing models declare their searchable columns.
 */
trait HasFullTextSearch
{
    /** @return array<int,string> columns covered by the FULLTEXT index */
    abstract public function toSearchableColumns(): array;

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $driver = (string) config('ics.search.driver', 'fulltext');

        if ($driver === 'fulltext') {
            $columns = implode(',', $this->toSearchableColumns());

            return $query->whereRaw(
                "MATCH({$columns}) AGAINST (? IN NATURAL LANGUAGE MODE)",
                [$term]
            );
        }

        // Fallback (also the path when a Phase-2 search engine is not configured).
        return $query->where(function (Builder $sub) use ($term) {
            foreach ($this->toSearchableColumns() as $column) {
                $sub->orWhere($column, 'like', '%'.$term.'%');
            }
        });
    }
}
