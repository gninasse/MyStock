<?php

namespace Modules\Referentiel\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Referentiel\Models\ResponsableMagasin;

class ResponsableAssigneNotification extends Notification
{
    public function __construct(public ResponsableMagasin $responsable) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $magasin = $this->responsable->magasin;
        $role = $this->responsable->role_responsable === ResponsableMagasin::PRINCIPAL ? 'Principal' : 'Adjoint';

        return (new MailMessage)
            ->subject("Vous êtes responsable du magasin {$magasin->libelle}")
            ->greeting("Bonjour {$notifiable->full_name},")
            ->line("Vous avez été désigné responsable {$role} du magasin {$magasin->libelle} ({$magasin->code}).")
            ->line('Date de début : '.$this->responsable->date_debut->format('d/m/Y'));
    }
}
