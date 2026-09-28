<?php

namespace App\Services;

use App\Models\MedicineGenericInfo;
use App\Models\MedicineMaster;

/**
 * Keeps the health chat able to answer about medicines a pharmacy adds
 * later, without anyone hand-writing an entry first.
 *
 * Knowledge is held per GENERIC, so:
 *   - A new BRAND of a generic we already describe (another paracetamol)
 *     is answerable the moment it is added — nothing to write.
 *   - A new GENERIC gets a class-level entry worked out from its name stem
 *     by DrugClassGuesser ("-floxacin" is an antibiotic), marked
 *     class_inferred + needs_review so an admin can confirm or correct it.
 *   - A generic whose name matches no known stem is stored as 'unknown', and
 *     the chat says it does not know rather than inventing something.
 */
class MedicineInfoService
{
    public function __construct(private DrugClassGuesser $guesser)
    {
    }

    /** The info for a catalog medicine, creating the generic entry on first sight. */
    public function forMedicine(MedicineMaster $medicine): MedicineGenericInfo
    {
        return $this->ensureFor($medicine->generic_name);
    }

    /** Ensures a generic has an info row, and returns it. Existing rows are never overwritten. */
    public function ensureFor(string $genericName): MedicineGenericInfo
    {
        $key = mb_strtolower(trim($genericName));

        $existing = MedicineGenericInfo::where('generic_name', $key)->first();
        if ($existing) {
            return $existing;
        }

        $guess = $this->guesser->guess($key);

        return MedicineGenericInfo::create($guess === null
            ? ['generic_name' => $key, 'source' => 'unknown', 'needs_review' => true]
            : array_merge($guess, ['generic_name' => $key, 'source' => 'class_inferred', 'needs_review' => true]));
    }

    /**
     * Fills in any generic in the catalog that has no info row yet — used by
     * the medicines:sync-info command, so medicines added by a seeder or
     * straight into the database are covered too.
     *
     * @return array{created: int, inferred: int, unknown: int}
     */
    public function syncCatalog(): array
    {
        $known = MedicineGenericInfo::pluck('generic_name')->all();
        $result = ['created' => 0, 'inferred' => 0, 'unknown' => 0];

        foreach (MedicineMaster::pluck('generic_name')->unique() as $generic) {
            if (in_array(mb_strtolower(trim($generic)), $known, true)) {
                continue;
            }

            $info = $this->ensureFor($generic);
            $result['created']++;
            $result[$info->source === 'class_inferred' ? 'inferred' : 'unknown']++;
        }

        return $result;
    }
}
