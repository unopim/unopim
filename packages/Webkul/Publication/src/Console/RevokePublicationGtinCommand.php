<?php

namespace Webkul\Publication\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Webkul\Publication\Models\PublicationGtinProxy;
use Webkul\Publication\Services\Gs1DigitalLink;

/**
 * Retires a GTIN that was published by mistake (a typo that belongs to another brand, say) so the history
 * fallback stops resolving it to the passport that once carried it.
 *
 * A publication that still carries the GTIN today is left alone: correct it by publishing the right GTIN,
 * then revoke the old one. Revoking is permanent and never touches `publications.gtin`.
 */
#[Description('Retire a mistakenly published GTIN so /01/{gtin} no longer resolves to the publications that carried it.')]
#[Signature('unopim:publication:revoke-gtin
                            {gtin : The GTIN to retire from the publication history}
                            {--publication= : Only revoke the history of the publication with this uuid}')]
class RevokePublicationGtinCommand extends Command
{
    public function handle(Gs1DigitalLink $gs1): int
    {
        $gtin = (string) $this->argument('gtin');

        if (! $gs1->isWellFormed($gtin)) {
            $this->components->error('"'.$gtin.'" is not a well-formed GTIN.');

            return self::FAILURE;
        }

        $rows = PublicationGtinProxy::modelClass()::query()
            ->with('publication')
            ->where('gtin', $gtin)
            ->whereNull('revoked_at')
            ->when($this->option('publication'), fn ($query, $uuid) => $query->whereHas('publication', fn ($publication) => $publication->where('uuid', $uuid)))
            ->get();

        if ($rows->isEmpty()) {
            $this->components->warn('No active history entry matches '.$gtin.'.');

            return self::FAILURE;
        }

        $revoked = 0;

        foreach ($rows as $row) {
            if ($row->publication->gtin === $gtin) {
                $this->components->warn('Publication '.$row->publication->uuid.' still carries '.$gtin.'; publish the corrected GTIN first.');

                continue;
            }

            $row->revoke();
            $revoked++;
        }

        $this->components->info('Revoked '.$revoked.' history '.($revoked === 1 ? 'entry' : 'entries').' for '.$gtin.'.');

        return $revoked > 0 ? self::SUCCESS : self::FAILURE;
    }
}
