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
        // Les images passent par un envoi de fichier dédié, jamais par le formulaire
        return collect($this->definitions())->reject(fn ($d) => $d['type'] === 'image')->mapWithKeys(function ($d, $cle) {
            $regles = match ($d['type']) {
                // Liste ou dictionnaire de textes (hiérarchie administrative, libellés des documents)
                'json' => ['nullable', 'array', 'max:'.($d['max'] ?? 20)],
                'entier' => ['required', 'integer', 'min:'.($d['min'] ?? 0), 'max:'.($d['max'] ?? PHP_INT_MAX)],
                'booleen' => ['required', 'boolean'],
                'choix' => ['required', 'in:'.implode(',', array_keys($d['choix']))],
                default => ['nullable', 'string', 'max:'.($d['max'] ?? 255)],
            };

            $regles = [$cle => ['sometimes', ...$regles]];
            if ($d['type'] === 'json') {
                $regles[$cle.'.*'] = ['nullable', 'string', 'max:'.($d['max_element'] ?? 150)];
            }

            // Mise à jour partielle possible : seules les clés envoyées sont validées
            return $regles;
        })->all();
    }

    /**
     * Enregistre les valeurs et journalise les modifications ; renvoie les clés modifiées.
     */
    public function enregistrer(array $valeurs, User $user, string $evenement = 'parametres_modifies'): array
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
                'valeur' => match (true) {
                    is_bool($nouvelle) => (int) $nouvelle,
                    is_array($nouvelle) => json_encode($nouvelle, JSON_UNESCAPED_UNICODE),
                    default => $nouvelle,
                },
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
                ->log($evenement);
        }

        return array_keys($modifications);
    }

    protected function convertir(array $definition, mixed $valeur): mixed
    {
        return match ($definition['type']) {
            'entier' => (int) $valeur,
            'booleen' => filter_var($valeur, FILTER_VALIDATE_BOOLEAN),
            'json' => $this->convertirJson($valeur),
            default => $valeur === null ? null : trim((string) $valeur),
        };
    }

    /**
     * Liste ou dictionnaire de textes : chaînes vides retirées, espaces superflus supprimés.
     */
    protected function convertirJson(mixed $valeur): array
    {
        if (is_string($valeur)) {
            $valeur = json_decode($valeur, true);
        }
        if (! is_array($valeur)) {
            return [];
        }
        $propre = array_filter(array_map(fn ($v) => trim((string) $v), $valeur), fn ($v) => $v !== '');

        return array_is_list($valeur) ? array_values($propre) : $propre;
    }
}
