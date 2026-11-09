<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de sécurité HTTP posés sur toutes les réponses web : pas d'intégration dans un
 * site tiers (l'aperçu PDF en iframe reste possible, même origine), pas de détection de
 * type MIME, pas de fuite d'URL vers d'autres sites, pas d'indexation par les moteurs.
 */
class EntetesSecurite
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $entetes = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'same-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
            'X-Robots-Tag' => 'noindex, nofollow',
        ];
        foreach ($entetes as $nom => $valeur) {
            if (! $response->headers->has($nom)) {
                $response->headers->set($nom, $valeur);
            }
        }
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
