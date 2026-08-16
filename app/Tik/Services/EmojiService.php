<?php

namespace App\Tik\Services;

use App\Tik\Repositories\EmojiRepository;

class EmojiService
{
    public function __construct(
        private readonly EmojiRepository $emojiRepository,
    ) {}

    public function index($request)
    {
        return  $this->emojiRepository->all($request);
    }

     public function all($request)
    {
        return  $this->emojiRepository->index($request);
    }

    public function show($id)
    {
        return $this->emojiRepository->findById($id);
    }
}
