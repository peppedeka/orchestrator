<?php

namespace App\Console\Commands;

use App\Enums\StoryStatus;
use Illuminate\Console\Command;
use App\Models\Story;
use Carbon\Carbon;
use Spatie\GoogleCalendar\Event;
use Illuminate\Support\Facades\DB;
use App\Enums\StoryType;
use Spatie\GoogleCalendar\GoogleCalendarFactory;

class SyncStoriesWithGoogleCalendar extends Command
{
    protected $signature = 'sync:stories-calendar {developerEmail?}';
    protected $description = 'Sync assigned stories with Google Calendar';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $developerEmail = $this->argument('developerEmail');

        // Se è fornita l'email, trova l'ID del developer usando l'email
        if ($developerEmail) {
            $developer = DB::table('users')->where('email', $developerEmail)->first();
            if (!$developer) {
                $this->warn("Developer with email: {$developerEmail} not found.");
                return;
            }
            $developerId = $developer->id;
        }

        // Ottieni tutte le storie assegnate che non sono chiuse (stato diverso da 'Done', 'Released', 'Rejected' e 'Waiting')
        $query = Story::whereIn('status', [StoryStatus::Progress->value])
            ->whereNotNull('user_id')
            ->whereNotNull('type');

        if (isset($developerId)) {
            $query->where('user_id', $developerId);
        }

        $stories = $query->get();

        // Inizializza l'orario di inizio per il primo evento
        $startTime = Carbon::today()->setTime(0, 1);

        // Raggruppa le storie per developer
        $storiesByDeveloper = $stories->groupBy('user_id');

        foreach ($storiesByDeveloper as $developerId => $stories) {
            // Ottieni il developer
            $developer = DB::table('users')->where('id', $developerId)->first();

            if ($developer) {
                // Usa l'email del developer come calendar ID se esiste
                $calendarId = $developer->email ?? $developerId;

                // Cancella i precedenti eventi creati con questo script
                $this->deletePreviousEvents($calendarId);

                foreach ($stories as $story) {
                    // Definisci l'orario di fine per l'evento
                    $endTime = $startTime->copy()->addHour();

                    // Imposta il colore dell'evento in base al tipo di storia
                    $colorId = '5'; // Default color (Yellow)
                    switch ($story->type) {
                        case StoryType::Bug->value:
                            $colorId = '11'; // Bold Red
                            break;
                        case StoryType::Helpdesk->value:
                            $colorId = '2'; // Green
                            break;
                        case StoryType::Feature->value:
                            $colorId = '1'; // Blue
                            break;
                    }

                    // Crea un singolo evento per la storia
                    $event = new Event;
                    $event->name = "Story ID: {$story->id} - {$story->name}"; // Nome della storia come titolo dell'evento
                    $event->description = "{$story->description}\n\nType: {$story->type}, Status: {$story->status}\nLink: https://orchestrator.maphub.it/resources/developer-stories/{$story->id}";
                    $event->startDateTime = $startTime;
                    $event->endDateTime = $endTime;
                    $event->colorId = $colorId; // Imposta il colore dell'evento

                    // Salva l'evento nel calendario specifico del developer
                    try {
                        $event->save(null, ['calendarId' => $calendarId]);
                        $this->info("Event for story ID: {$story->id} synced to Google Calendar for developer: {$developer->name}");
                    } catch (\Exception $e) {
                        $this->error("Failed to create event for story ID: {$story->id}. Error: " . $e->getMessage());
                    }

                    // Aggiorna l'orario di inizio per il prossimo evento
                    $startTime = $endTime;
                }
            }
        }

        $this->info('All stories have been synced to Google Calendar');
    }

    private function deletePreviousEvents($calendarId)
    {
        // Ottieni tutti gli eventi nel calendario per oggi
        $events = Event::get(Carbon::today(), Carbon::today()->endOfDay(), ['calendarId' => $calendarId]);

        foreach ($events as $event) {
            // Se il nome dell'evento inizia con "Story ID: ", cancellalo
            if (strpos($event->name, 'Story ID:') === 0) {
                try {
                    // Utilizza l'ID dell'evento per cancellarlo
                    $calendar = GoogleCalendarFactory::createForCalendarId($calendarId);
                    $calendar->deleteEvent($event->id);
                    $this->info("Deleted event: {$event->name}");
                } catch (\Exception $e) {
                    $this->error("Failed to delete event: {$event->name}. Error: " . $e->getMessage());
                }
            }
        }
    }
}