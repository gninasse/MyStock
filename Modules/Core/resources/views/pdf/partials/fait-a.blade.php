{{-- « Fait à Ouagadougou, le 8 novembre 2026 », au-dessus des signatures.
     $date facultative (aujourd'hui par défaut) ; $aRemplir = true laisse la date en pointillés. --}}
<div class="fait-a">{{ app(\Modules\Referentiel\Services\EnteteDocuments::class)->faitA($date ?? null, ! empty($aRemplir)) }}</div>
