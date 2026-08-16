<?php

namespace Modules\Moment\Http\Services;

use App\Helpers\Common;
use App\Jobs\UploadMomentImageJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Moment\Entities\Moment;
use Modules\Moment\Http\Repositories\MomentRepository;
use Nwidart\Modules\Facades\Module;

class MomentService extends MomentBaseModelService
{
    public function __construct(Moment $model, public MomentRepository $momentRepository)
    {
        parent::__construct($model);
    }

    public function getMomentsByType($type, $userId, $page, $currentUser)
    {
        switch ($type) {
            case 1:
                return $this->momentRepository->getUserMoments($userId ?? $currentUser, $page);
            case 2:
                return $this->momentRepository->getLikedMoments($currentUser, $page);
            case 3:
                return $this->momentRepository->getFollowedMoments($currentUser, $page);
            case 4:
                return $this->momentRepository->getAllMoments($currentUser, $page);
            case 5:
                return $this->momentRepository->getNewMoments($currentUser);
            case 6:
                return $this->momentRepository->momentUserFollow($currentUser);
            default:
                return null;
        }
    }

    public function deleteMomentAndReport($momentId, $reportId)
    {
        // Find the moment by ID
        $moment = $this->momentRepository->findMomentById($momentId);
        if (!$moment) {
            return [
                'success' => false,
                'message' => 'Moment not found',
                'status' => 404,
            ];
        }

        // Find the report moment by ID and delete it
        $reportMoment = $this->momentRepository->findReportMomentById($reportId);
        if ($reportMoment) {
            $this->momentRepository->deleteReportMoment($reportMoment);
        }

        // Delete the moment
        $this->momentRepository->deleteMoment($moment);

        return [
            'success' => true,
            'message' => 'Moment and report successfully deleted',
            'status' => 200,
        ];
    }

    public function deleteMomentById($id)
    {
        $moment = $this->momentRepository->findMomentById($id);

        if (!$moment) {
            return [
                'success' => false,
                'message' => 'Item not found',
                'status' => 404,
            ];
        }

        // Perform delete operation
        $this->momentRepository->deleteMoment($moment);

        return [
            'success' => true,
            'message' => 'Success Deleted',
            'status' => 200,
        ];
    }

    public function createMoment($contacts, $request)
    {
        $userId = Auth::id();

        // Prevent posting empty content
        if (empty($contacts) && empty($imgPath)) {
            return [
                'success' => false,
                'message' => 'Not allowed to post empty content',
            ];
        }

        // Create moment
        $moment = $this->momentRepository->createMoment([
            'user_id' => $userId,
            'description' => $contacts,
        ]);

        if (!$moment) {
            return [
                'success' => false,
                'message' => 'Try again',
            ];
        }
        if ($request->hasFile('multi_image')) {
            foreach ($request->file('multi_image') as $file) {

                if ($file && $file->isValid()) {
                    if (!Storage::disk('local')->exists('temp')) {
                        Storage::disk('local')->makeDirectory('temp');
                    }

                    $tempPath = $file->store('temp', 'local');

                    UploadMomentImageJob::dispatch(
                        $moment->id,
                        $tempPath
                    );
                }
            }
        }

        return [
            'success' => true,
            'message' => 'Success',
        ];
    }

    public function getMoment($id, $userId)
    {
        $moment = $this->momentRepository->getMomentById($id, $userId);

        if (!$moment) {
            return [
                'success' => false,
                'message' => __('Moment not found'),
                'data' => null,
                'status' => 402,
            ];
        }

        return [
            'success' => true,
            'message' => '',
            'data' => $moment,
            'status' => 200,
        ];
    }
    public function show(User $user) {}

    public function create(array $data, int $userId) {}


    /*
     * $data is = [file, description, categories ids]
     */



    public function delete(int|Module $moment)
    {
        if (gettype($moment) == 'integer') {
            $moment = Moment::query()->find($moment);
        }

        $moment->delete();
    }
}
