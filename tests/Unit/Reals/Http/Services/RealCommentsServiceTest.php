<?php

namespace Tests\Unit\Reals\Http\Services;

use App\Models\User;
use Modules\Reals\Entities\Real;
use Modules\Reals\Http\Services\RealCommentsService;
use Tests\TestCase;

class RealCommentsServiceTest extends TestCase
{
    public $realCommentService;
    public function __construct(?string $name = null, array $data = [], $dataName = '') { parent::__construct($name, $data, $dataName); $this->realCommentService = new RealCommentsService();}

    public function testAdd()
    {

        $this->realCommentService->add('test', Real::first(), User::first());
    }
}
