<?php

namespace Modules\FixedTarget\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Modules\FixedTarget\Entities\FixedTarget;
use Tests\TestCase;

class ExampleTest extends TestCase
{

    public function test_target()
    {
        $input  = [
            "id"           => 1,
            "diamonds"     => 0,
            "hours"        => 0,
            "days"         => 14,
            "count_moment" => 0,
            "count_real"   => 0,
        ];
        $result = $this->findClosestElement($input);
        dd($result);
    }

    public function findClosestElement($input)
    {

        $data = Cache::remember('fixed_target', now()->addMinutes(10), function () {
            return FixedTarget::all();
        });

        $closestElement = null;
        $minDistance    = PHP_INT_MAX;
        $currentId      = null;
        $closestId      = null;

        foreach ($data as $element) {
            $distance    = 0;
            $isAllTarget = true;
            foreach ($element->getAttributes() as $key => $value) {
                if (!in_array($key, [
                    "diamonds",
                    "hours",
                    "days",
                    "count_moment",
                    "count_real",
                ])) {
                    continue;
                }
                if ($key !== 'id' && $input[$key] < $value) {
                    $isAllTarget = false;
                }

                if ($key !== 'id') {
                    $distance += pow($input[$key] - $value, 2);
                }
            }

            if ($isAllTarget) {
                $currentId = $element['id'];

            }

            $distance = sqrt($distance);

            if ($distance < $minDistance) {
                $minDistance    = $distance;
                $closestElement = $element;
                $closestId      = $element->id;
            }

        }

        return [
            'currentId'      => $data->where('id', $currentId)->first(),
            'closestId'      => $closestId,
            'closestElement' => $closestElement,
        ];
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {

        $data = [
            ['id' => 1, 'di' => 0, 'h' => 5, 'd' => 2, 'CM' => 5, 'CR' => 6],
            ['id' => 2, 'di' => 100, 'h' => 10, 'd' => 3, 'CM' => 10, 'CR' => 12],
            ['id' => 3, 'di' => 100, 'h' => 10, 'd' => 30, 'CM' => 20, 'CR' => 120],
        ];

        $input = ['di' => 100, 'h' => 10, 'd' => 30, 'CM' => 10, 'CR' => 120];

        $closestElement = null;
        $minDistance    = PHP_INT_MAX;
        $currentId      = null;
        $closestId      = null;

        foreach ($data as $element) {
            $distance    = 0;
            $isAllTarget = true;
            foreach ($element as $key => $value) {
                if ($key !== 'id' && $input[$key] < $value) {
                    $isAllTarget = false;
                }


                if ($key !== 'id') {
                    $distance += pow($input[$key] - $value, 2);
                }
            }
            if ($isAllTarget) {
                $currentId = $element['id'];

            }
            $distance = sqrt($distance);

            if ($distance < $minDistance) {
                $minDistance    = $distance;
                $closestElement = $element;
                $closestId      = $element['id'];
            }

            // Set the currentId on the first iteration

        }

        // Output the current and closest element IDs
        echo "Current Element ID: " . $currentId . PHP_EOL;
        echo "Closest Element ID: " . $closestId . PHP_EOL;


    }
}
