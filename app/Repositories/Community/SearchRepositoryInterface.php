<?php

namespace App\Repositories\Community;



use Illuminate\Database\Eloquent\Collection;

interface SearchRepositoryInterface
{
    public function saveSearchHistory(int $userId, string $keywords): void;
    public function searchRooms(int $userId, string $keywords, int $page = 1): array|Collection;
    public function searchRoomsV2(int $userId, string $keywords,array $blockedUserIds, int $page = 1): array|Collection;
    public function userSearchHand(int $userId, string $keywords, int $page = 1);
    public function userSearchHandV2(int $userId, string $keywords,array $blockedUserIds, int $page = 1);
    public function getUserFriends(int $userId, string $keywords = null, int $perPage = 10, int $currentPage = 1): \Illuminate\Pagination\LengthAwarePaginator;
    public function getSearchList(int $userId): array;
    public function clearUserSearchHistory(int $userId): bool;
    public function getOfficialMessages(int $userId, int $page = 1): array;
    public function getNotifications(int $userId, int $type);

}
