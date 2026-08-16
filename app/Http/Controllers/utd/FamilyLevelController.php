<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\FamilyLevel;
use App\Tik\Services\FamilyLevelService;
use Illuminate\Http\Request;
use Exception;

class FamilyLevelController extends Controller
{
    public function __construct(private FamilyLevelService $FamilyLevelService) {}
    public function index(Request $request)
    {

        // $FamilyLevel = $this->FamilyLevelService->index();
        $perPage = request('per_page')?? 10;
        $FamilyLevel = FamilyLevel::paginate($perPage);
        return response()->json([
            'status' => 'success',
            'message' => 'FamilyLevels returned successfully',
            'data' => $FamilyLevel
        ]);
    }
    public function show( $id,Request $request)
    {
        $FamilyLevel = FamilyLevel::find($id);

        return response()->json([
            'status' => 'success',
            'message' => 'FamilyLevel returned successfully',
            'data' => $FamilyLevel
        ]);
    }


    public function store(Request $request)
    {
        try {
            $FamilyLevel =   $this->FamilyLevelService->create( $request);
        }catch (\Exception $e) {

            return response()->json([
                'message' => 'failed',
                'status' => 'failed',
                'data' => null
            ]);

        }

        return response()->json([
            'message' => 'FamilyLevel created successfully',
            'status' => 'success',
            'data' => $FamilyLevel
        ]);
    }




    public function update($id ,Request $request)
    {

        try {
            $family = $this->FamilyLevelService->update( $request, $id);
        } catch (Exception $e) {

            return response()->json([
                'message' => 'failed',
                'status' => 'failed',
                'data' => null
            ]);
         }

        return response()->json([
            'message' => 'FamilyLevel updated successfully',
            'status' => 'success',
        ]);
    }


    public function destroy($id)
    {


        FamilyLevel::findOrFail($id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'FamilyLevel deleted successfully',
        ]);
    }

}
