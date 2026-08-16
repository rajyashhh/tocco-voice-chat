<?php

namespace Tests\Feature;

use App\Services\Gifts\LuckyStrategyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{

    public function testLucky()
    {
        $result = (new LuckyStrategyService())->getThresholds();
//        $result = (new LuckyStrategyService())->calculateMid();
        dd($result);
    }
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {
        $numbers = [50, 100, 200,5000000,621321354,20,400, 500, 800, 1000, 2000, 10000, 8000, 20000];
        $input = implode(' ', $numbers);
        $pythonScript = base_path('app/Tik/Python/categorize_numbers.py');
        $command = "python3 {$pythonScript} {$input}";

        $output = [];
        exec($command, $output);
        $result = json_decode(implode('', $output), true);
        dd($result);
    }


    public function categorizeNumbers( $numbers)
    {

        // Calculate thresholds
        $threshold1 = $this->calculatePercentile($numbers, 33.33);
        $threshold2 = $this->calculatePercentile($numbers, 66.67);

        // Categorize the numbers
        $small = array_filter($numbers, function ($num) use ($threshold1) {
            return $num <= $threshold1;
        });

        $medium = array_filter($numbers, function ($num) use ($threshold1, $threshold2) {
            return $num > $threshold1 && $num <= $threshold2;
        });

        $large = array_filter($numbers, function ($num) use ($threshold2) {
            return $num > $threshold2;
        });

        // Return the categorized numbers as JSON response
        return [
            '$threshold1' =>$threshold1,
            '$threshold2' =>$threshold2,
            'small' => $small,
            'medium' => $medium,
            'large' => $large,
        ];
    }

    private function calculatePercentile($numbers, $percentile)
    {
        sort($numbers); // Sort the numbers in ascending order
        $index = ceil((floatval($percentile) / 100) * count($numbers)) - 1;
        return $numbers[$index];
    }
}
