<?php

namespace App\Services\Events;

use App\Models\Event;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EventListingService
{
    private const DEFAULT_PER_PAGE = 30;

    private const PUBLIC_PER_PAGE = 20;

    /**
     * Liste paginée des événements pour l'espace admin global.
     *
     * @return LengthAwarePaginator<int, Event>
     */
    public function adminIndex(?string $search = null): LengthAwarePaginator
    {
        $query = Event::query()
            ->with(['organizer', 'eventRequest', 'creator:id,name,role'])
            ->orderBy('created_at', 'desc');

        $this->applySearch($query, $search);

        return $query->paginate(self::DEFAULT_PER_PAGE);
    }

    /**
     * Événements gérés ou créés par l'utilisateur courant.
     *
     * @return LengthAwarePaginator<int, Event>
     */
    public function managedBy(User $user): LengthAwarePaginator
    {
        return Event::query()
            ->where(function (Builder $query) use ($user): void {
                $query->where('organizer_id', $user->id)
                    ->orWhere('created_by', $user->id);
            })
            ->with(['eventRequest', 'organizer'])
            ->orderBy('created_at', 'desc')
            ->paginate(self::DEFAULT_PER_PAGE);
    }

    /**
     * Événements issus de l'espace organisateur, visibles par l'admin.
     *
     * @return LengthAwarePaginator<int, Event>
     */
    public function organizerSpace(): LengthAwarePaginator
    {
        return Event::query()
            ->where(function (Builder $query): void {
                $query->whereHas('organizer', fn (Builder $query): Builder => $query->where('role', User::ROLE_ORGANIZER))
                    ->orWhereHas('creator', fn (Builder $query): Builder => $query->where('role', User::ROLE_ORGANIZER));
            })
            ->with(['organizer', 'eventRequest', 'creator:id,name,role'])
            ->orderBy('created_at', 'desc')
            ->paginate(self::DEFAULT_PER_PAGE);
    }

    /**
     * Événements assignés à l'administrateur connecté.
     *
     * @return LengthAwarePaginator<int, Event>
     */
    public function assignedToAdmin(User $admin): LengthAwarePaginator
    {
        return $this->managedBy($admin);
    }

    /**
     * Événements publics consultables par les utilisateurs authentifiés.
     *
     * @return LengthAwarePaginator<int, Event>
     */
    public function published(?string $search = null): LengthAwarePaginator
    {
        $query = Event::query()
            ->where('status', Event::STATUS_PUBLISHED)
            ->where('start_at', '>=', now()->subDay())
            ->with(['organizer', 'eventRequest'])
            ->orderBy('start_at', 'asc');

        $this->applySearch($query, $search);

        return $query->paginate(self::PUBLIC_PER_PAGE);
    }

    /**
     * @param  Builder<Event>  $query
     */
    private function applySearch(Builder $query, ?string $search): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $term = '%'.$search.'%';

        $query->where(function (Builder $query) use ($term): void {
            $query->where('title', 'like', $term)
                ->orWhere('description', 'like', $term)
                ->orWhere('location', 'like', $term);
        });
    }
}
