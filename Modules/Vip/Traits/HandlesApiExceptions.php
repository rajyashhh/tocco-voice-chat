<?php
namespace Modules\Vip\Traits;

use Exception;
use App\Helpers\Common;

trait HandlesApiExceptions
{
    public function wrap(callable $callback)
    {
        try {
            return $callback();
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 400);
        }
    }
}
