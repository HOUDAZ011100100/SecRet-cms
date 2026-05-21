<?php

namespace App\Services\Notifications;

use App\Models\AppNotification;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Laravel\Connection as MongoConnection;
use Traversable;

class NotificationInboxService
{
    private const PER_PAGE = 30;

    /**
     * @return array{
     *     data: list<array<string, mixed>>,
     *     unread_count: int,
     *     meta: array{current_page: int, last_page: int, per_page: int, total: int}
     * }
     */
    public function pageFor(User $user, int $page = 1, bool $unreadOnly = false): array
    {
        $page = max(1, $page);
        $match = ['user_id' => $this->userId($user)];
        $unreadMatch = $this->unreadMatch();

        if ($unreadOnly) {
            $match = array_merge($match, $unreadMatch);
        }

        $facet = $this->facet($match, $unreadMatch, $page);
        $data = is_array($facet['data'] ?? null)
            ? array_map(fn (mixed $document): array => $this->formatNotification($document), $facet['data'])
            : [];
        $total = $this->facetCount($facet, 'meta', 'total');
        $unreadCount = $this->facetCount($facet, 'unread', 'count');

        return [
            'data' => array_values($data),
            'unread_count' => $unreadCount,
            'meta' => [
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / self::PER_PAGE)),
                'per_page' => self::PER_PAGE,
                'total' => $total,
            ],
        ];
    }

    public function unreadCount(User $user): int
    {
        return AppNotification::query()
            ->where('user_id', $this->userId($user))
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @param  array<string, mixed>  $match
     * @param  array<string, mixed>  $unreadMatch
     * @return array<mixed, mixed>
     */
    private function facet(array $match, array $unreadMatch, int $page): array
    {
        /** @var MongoConnection $connection */
        $connection = DB::connection('mongodb');
        $results = $connection
            ->getDatabase()
            ->selectCollection((new AppNotification)->getTable())
            ->aggregate([
                ['$match' => $match],
                ['$sort' => ['created_at' => -1, '_id' => -1]],
                ['$facet' => [
                    'data' => [
                        ['$skip' => ($page - 1) * self::PER_PAGE],
                        ['$limit' => self::PER_PAGE],
                    ],
                    'meta' => [
                        ['$count' => 'total'],
                    ],
                    'unread' => [
                        ['$match' => $unreadMatch],
                        ['$count' => 'count'],
                    ],
                ]],
            ]);

        $facet = $this->plainValue(iterator_to_array($results, false)[0] ?? []);

        return is_array($facet) ? $facet : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function unreadMatch(): array
    {
        return [
            '$or' => [
                ['read_at' => null],
                ['read_at' => ['$exists' => false]],
            ],
        ];
    }

    /**
     * @param  array<mixed, mixed>  $facet
     */
    private function facetCount(array $facet, string $facetKey, string $countKey): int
    {
        $entries = $facet[$facetKey] ?? null;
        if (! is_array($entries)) {
            return 0;
        }

        $first = $entries[0] ?? null;
        if (! is_array($first)) {
            return 0;
        }

        $count = $first[$countKey] ?? 0;

        return is_int($count) ? $count : 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatNotification(mixed $document): array
    {
        $notification = $this->plainValue($document);
        if (! is_array($notification)) {
            return [];
        }

        return [
            'id' => $notification['_id'] ?? null,
            'user_id' => $notification['user_id'] ?? null,
            'type' => $notification['type'] ?? null,
            'title' => $notification['title'] ?? null,
            'message' => $notification['message'] ?? null,
            'data' => $notification['data'] ?? null,
            'read_at' => $notification['read_at'] ?? null,
            'created_at' => $notification['created_at'] ?? null,
            'updated_at' => $notification['updated_at'] ?? null,
        ];
    }

    private function plainValue(mixed $value): mixed
    {
        if ($value instanceof ObjectId) {
            return (string) $value;
        }

        if ($value instanceof UTCDateTime) {
            return $value->toDateTime()->format(DateTimeInterface::ATOM);
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if ($value instanceof Traversable) {
            return $this->plainValue(iterator_to_array($value));
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->plainValue($item), $value);
        }

        return $value;
    }

    private function userId(User $user): string
    {
        $id = $user->getKey();

        return is_int($id) || is_string($id) ? (string) $id : '';
    }
}
