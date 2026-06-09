<?php

namespace App\Content;

/**
 * Implemented by every content model (CMS/Knowledge/Research) — the integration seam
 * for ContentAccessService (D-038/D-051). Tier-scoped only; NEVER account-scoped (W1-3).
 */
interface ContentAccessible
{
    /** AccessStrategy::HIERARCHICAL (Research) | AccessStrategy::LATERAL (Knowledge). */
    public function accessStrategy(): string;

    /** Access tier 1..5. */
    public function accessTier(): int;

    /** Whether the item is currently published. */
    public function isPublished(): bool;

    /** Owning module: 'cms' | 'knowledge' | 'research'. */
    public function contentModule(): string;
}
