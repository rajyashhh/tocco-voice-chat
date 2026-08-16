<?php

namespace App\Tik\Services;

use App\Tik\Repositories\ReelsRepository;
use Illuminate\Support\Facades\Auth;

class ReelsService
{
    public function __construct(
        private readonly ReelsRepository $reelRepository,
    ) {}

    public function index($perPage, $page)
    {
        return $this->reelRepository->all($perPage, $page);
    }

    public function following($perPage = 10, $page = 1)
    {
        return $this->reelRepository->following(Auth::id(), $perPage, $page);
    }

    public function showByUser($id)
    {
        return $this->reelRepository->showByUser($id);
    }

    public function show($id)
    {
        return $this->reelRepository->find($id);
    }

    public function search($id)
    {
        return $this->reelRepository->search($id);
    }

    public function recordView($reelId)
    {
        $this->reelRepository->incrementViews($reelId);
    }

    public function delete($reelId)
    {
        $reel = $this->reelRepository->find($reelId);
        if (!$reel) throw new \Exception('not found');
        $reel->delete();
        return true;
    }
}
