<?php

namespace Tests\Unit\Http;

use App\Http\Resources\Api\V1\MusicResource;
use App\Models\Music;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Music list "name" contract (pure unit, NO app boot / NO database): clients
 * display whatever this resource returns. Uploads send the real song title
 * (stored in music.name); legacy rows have no stored name and must fall back
 * to the storage filename.
 */
class MusicResourceNameTest extends TestCase
{
    private function resourceArray(array $attributes): array
    {
        $music = new Music($attributes);
        // Pre-load the relation so toArray() never builds a query.
        $music->setRelation('user', null);

        return (new MusicResource($music))->toArray(new Request());
    }

    public function test_uses_stored_name_when_present(): void
    {
        $data = $this->resourceArray([
            'url' => 'music/file_1749700000000_123456.mp3',
            'name' => 'My Song',
        ]);

        $this->assertSame('My Song', $data['name']);
    }

    public function test_falls_back_to_storage_filename_for_legacy_rows(): void
    {
        $data = $this->resourceArray([
            'url' => 'music/file_1749700000000_123456.mp3',
        ]);

        $this->assertSame('file_1749700000000_123456.mp3', $data['name']);
    }
}
