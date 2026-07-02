<?php

namespace App\Services;

use App\Models\Profesional;
use App\Models\Cita;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect_uri'));
        $this->client->addScope(Calendar::CALENDAR_EVENTS);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
    }

    /**
     * Genera la URL de autorización para que el profesional inicie el flujo de Google OAuth.
     */
    public function generateAuthUrl(int $profesionalId): string
    {
        $this->client->setState(encrypt($profesionalId));
        return $this->client->createAuthUrl();
    }

    /**
     * Intercambia el código de autorización por el access token y refresh token, y los guarda.
     */
    public function exchangeCodeAndSaveTokens(string $code, Profesional $profesional): void
    {
        $accessToken = $this->client->fetchAuthCodeWithRefreshToken($code);
        
        if (isset($accessToken['error'])) {
            throw new \RuntimeException('Error de Google OAuth: ' . ($accessToken['error_description'] ?? $accessToken['error']));
        }

        $profesional->update([
            'google_calendar_token' => json_encode($accessToken),
        ]);

        Log::info("GoogleCalendarService: Tokens guardados correctamente para el profesional #{$profesional->id}");
    }

    /**
     * Devuelve una instancia configurada y autenticada de Google Client.
     * Si el access token ha expirado, utiliza el refresh token para obtener uno nuevo de forma transparente.
     */
    private function getAuthenticatedClient(Profesional $profesional): ?Client
    {
        if (!$profesional->google_calendar_token) {
            return null;
        }

        $tokens = json_decode($profesional->google_calendar_token, true);
        $this->client->setAccessToken($tokens);

        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $newTokens = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                
                // Si la respuesta no contiene un nuevo refresh token, conservar el antiguo
                if (!isset($newTokens['refresh_token']) && isset($tokens['refresh_token'])) {
                    $newTokens['refresh_token'] = $tokens['refresh_token'];
                }
                
                $profesional->update([
                    'google_calendar_token' => json_encode($newTokens),
                ]);
                $this->client->setAccessToken($newTokens);
            } else {
                Log::error("GoogleCalendarService: Refresh token no disponible para profesional #{$profesional->id}");
                return null;
            }
        }

        return $this->client;
    }

    /**
     * Sincroniza (crea o actualiza) una cita de CitasPro en el Google Calendar del profesional.
     */
    public function syncCitaToGoogle(Cita $cita): void
    {
        $profesional = $cita->profesional;
        $client = $this->getAuthenticatedClient($profesional);

        if (!$client) {
            return; // El profesional no tiene Google Calendar configurado
        }

        $service = new Calendar($client);
        $calendarId = $profesional->google_calendar_id ?: 'primary';

        $eventData = [
            'summary' => "Cita: " . $cita->servicio->nombre,
            'description' => "Cliente: " . $cita->cliente->nombre_completo . "\nTeléfono: " . $cita->cliente->telefono . "\nRef: " . $cita->codigo_referencia,
            'start' => [
                'dateTime' => $cita->fecha->format('Y-m-d') . 'T' . $cita->hora_inicio,
                'timeZone' => config('app.timezone', 'UTC'),
            ],
            'end' => [
                'dateTime' => $cita->fecha->format('Y-m-d') . 'T' . $cita->hora_fin,
                'timeZone' => config('app.timezone', 'UTC'),
            ],
        ];

        try {
            if ($cita->google_event_id) {
                // Actualizar evento
                try {
                    $event = $service->events->get($calendarId, $cita->google_event_id);
                    $event->setSummary($eventData['summary']);
                    $event->setDescription($eventData['description']);
                    $event->setStart(new Calendar\EventDateTime($eventData['start']));
                    $event->setEnd(new Calendar\EventDateTime($eventData['end']));
                    
                    $service->events->update($calendarId, $cita->google_event_id, $event);
                    Log::info("GoogleCalendarService: Evento actualizado para cita #{$cita->id}");
                } catch (\Exception $ex) {
                    // Si el evento fue borrado de Google Calendar, lo volvemos a crear
                    if ($ex->getCode() == 410 || $ex->getCode() == 404) {
                        $event = new Event($eventData);
                        $newEvent = $service->events->insert($calendarId, $event);
                        $cita->update(['google_event_id' => $newEvent->getId()]);
                        Log::info("GoogleCalendarService: Evento recreado (estaba eliminado de Google) para cita #{$cita->id}");
                    } else {
                        throw $ex;
                    }
                }
            } else {
                // Crear evento
                $event = new Event($eventData);
                $newEvent = $service->events->insert($calendarId, $event);
                $cita->update(['google_event_id' => $newEvent->getId()]);
                Log::info("GoogleCalendarService: Nuevo evento creado para cita #{$cita->id} con Google Event ID: " . $newEvent->getId());
            }
        } catch (\Exception $e) {
            Log::error("GoogleCalendarService: Error al sincronizar cita #{$cita->id} con Google Calendar: " . $e->getMessage());
        }
    }

    /**
     * Elimina el evento correspondiente de Google Calendar si la cita es cancelada o eliminada.
     */
    public function deleteGoogleEvent(Profesional $profesional, string $eventId): void
    {
        $client = $this->getAuthenticatedClient($profesional);
        if (!$client) {
            return;
        }

        $service = new Calendar($client);
        $calendarId = $profesional->google_calendar_id ?: 'primary';

        try {
            $service->events->delete($calendarId, $eventId);
            Log::info("GoogleCalendarService: Evento de Google {$eventId} eliminado para el profesional #{$profesional->id}");
        } catch (\Exception $e) {
            // Si ya no existe, ignorar el error
            if ($e->getCode() != 404 && $e->getCode() != 410) {
                Log::error("GoogleCalendarService: Error al eliminar evento {$eventId} de Google: " . $e->getMessage());
            }
        }
    }
}
