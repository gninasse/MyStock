<?php

namespace Modules\Referentiel\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Models\User;

/**
 * Lecture (en cache) et enregistrement des paramètres de l'application.
 * Usage : Parametres::get('mouvement_jours_retroactifs').
 */
class Parametres
{
    protected const CACHE = 'referentiel.parametres';

    public static function get(string $cle): mixed
    {
        return app(self::class)->valeur($cle);
    }

    public function definitions(): array
    {
        return config('referentiel.parametres', []);
    }

    public function valeur(string $cle): mixed
    {
        $definition = $this->definitions()[$cle] ?? throw new InvalidArgumentException("Paramètre inconnu : {$cle}");
        $enregistres = Cache::rememberForever(self::CACHE, fn () => DB::table('parametres')->pluck('valeur', 'cle')->all());

        return array_key_exists($cle, $enregistres)
            ? $this->convertir($definition, $enregistres[$cle])
            : $definition['defaut'];
    }

    public function toutes(): array
    {
        return collect($this->definitions())->mapWithKeys(fn ($d, $cle) => [$cle => $this->valeur($cle)])->all();
    }

    /**
     * Règles de validation Laravel déduites des définitions.
     */
    public function regles(): array
    {
        return collect($this->definitions())->mapWithKeys(function ($d, $cle) {
            $regles = match ($d['type']) {
                'entier' => ['required', 'integer', 'min:'.($d['min'] ?? 0), 'max:'.($d['max'] ?? PHP_INT_MAX)],
                'booleen' => ['required', 'boolean'],
                'choix' => ['required', 'in:'.implode(',', array_keys($d['choix']))],
                default => ['nullable', 'string', 'max:'.($d['max'] ?? 255)],
            };

            return [$cle => $regles];
        })->all();
    }

    /**
     * Enregistre les valeurs et journalise les modifications ; renvoie les clés modifiées.
     */
    public function enregistrer(array $valeurs, User $user): array
    {
        $avant = $this->toutes();
        $modifications = [];

        foreach ($this->definitions() as $cle => $definition) {
            if (! array_key_exists($cle, $valeurs)) {
                continue;
            }
            $nouvelle = $this->convertir($definition, $valeurs[$cle]);
            if ($nouvelle === $avant[$cle]) {
                continue;
            }
            DB::table('parametres')->updateOrInsert(['cle' => $cle], [
                'valeur' => is_bool($nouvelle) ? (int) $nouvelle : $nouvelle,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
            $modifications[$cle] = ['avant' => $avant[$cle], 'apres' => $nouvelle];
        }

        Cache::forget(self::CACHE);

        if ($modifications) {
            activity('referentiel')
                ->causedBy($user)
                ->withProperties(['modifications' => $modifications])
                ->tap(function ($activity) {
                    $activity->module = 'referentiel';
                    $activity->ip_address = request()->ip();
                    $activity->user_agent = request()->userAgent();
                })
                ->log('parametres_modifies');
        }

        return array_keys($modifications);
    }

    protected function convertir(array $definition, mixed $valeur): mixed
    {
        return match ($definition['type']) {
            'entier' => (int) $valeur,
            'booleen' => filter_var($valeur, FILTER_VALIDATE_BOOLEAN),
            default => $valeur === null ? null : trim((string) $valeur),
        };
    }
}
