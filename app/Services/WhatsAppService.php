<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $sid;
    protected $token;
    protected $from;

    public function __construct()
    {
        $this->sid = env('TWILIO_SID');
        $this->token = env('TWILIO_AUTH_TOKEN');
        $this->from = env('TWILIO_WHATSAPP_FROM');
    }

    public function sendPlanning(string $to, array $planningData): bool
    {
        if (!$this->sid || !$this->token) {
            Log::error("Twilio credentials missing.");
            return false;
        }

        $formattedMessage = $this->formatPlanningMessage($planningData);

        try {
            $twilio = new Client($this->sid, $this->token);
            
            // Si le numéro n'a pas whatsapp:, l'ajouter
            if (!str_starts_with($to, 'whatsapp:')) {
                // S'assurer qu'il a le format international, ex: +336...
                if (!str_starts_with($to, '+')) {
                    $to = '+' . $to;
                }
                $to = 'whatsapp:' . $to;
            }

            $message = $twilio->messages->create(
                $to,
                [
                    "from" => $this->from,
                    "body" => $formattedMessage
                ]
            );

            return $message->sid ? true : false;

        } catch (\Exception $e) {
            Log::error("Erreur Twilio WhatsApp: " . $e->getMessage());
            return false;
        }
    }

    protected function formatPlanningMessage(array $planningData): string
    {
        $message = "📅 *Votre Planning Eatwise de la semaine* 🥗\n\n";

        if (!isset($planningData['week'])) {
            return $message . "Aucun planning trouvé.";
        }

        $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        
        foreach ($jours as $jour) {
            if (isset($planningData['week'][$jour])) {
                $message .= "🗓️ *" . ucfirst($jour) . "*\n";
                $meals = $planningData['week'][$jour]['meals'] ?? [];
                
                if (empty($meals)) {
                    $message .= "Aucun repas prévu.\n";
                } else {
                    foreach ($meals as $index => $meal) {
                        $type = "Repas " . ($index + 1);
                        if ($index === 0) $type = "Petit-déj";
                        if ($index === 1) $type = "Déjeuner";
                        if ($index === 2) $type = "Dîner";

                        $title = $meal['title'] ?? 'Inconnu';
                        $message .= "🔹 *$type* : $title\n";
                    }
                }
                $message .= "\n";
            }
        }
        
        $message .= "Bon appétit ! 🚀";
        return $message;
    }
}
