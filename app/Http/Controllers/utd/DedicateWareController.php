<?php

namespace App\Http\Controllers\utd;

use App\Facades\CustomNotification;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Pack;
use App\Models\User;
use App\Models\Ware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Public\Http\Services\UserCounterServices;

class DedicateWareController extends Controller
{
    public function index()
    {

        $search = request('search');
        $type = request('type');
        $perPage = request('per_page') ?? 10;

        $result = Ware::when($search, function ($q) use ($search) {
            $q->where('id', $search);
        })
            ->when($type && $type == 25, function ($q) use ($type) {
                $q->whereNotNull('get_type')->where('type', 25);
            })
            ->when(!$type || $type != 25, function ($q) {
                $q->whereNotNull('get_type')->where('type', '!=', 25);
            })
            ->paginate($perPage);

        $typeTranslations = [
            1 => trans('Gemstone'),
            3 => trans('Card Scroll'),
            4 => trans('Avatar Frame'),
            5 => trans('Bubble Frame'),
            6 => trans('Entering Special Effects'),
            7 => trans('Microphone Aperture'),
            8 => trans('Badge'),
            9 => trans('NoKick'),
            10 => trans('Icon'),
            11 => trans('intro animation'),
            12 => trans('wapel'),
            13 => trans('hide country and last login'),
            14 => trans('vip gifts'),
            15 => trans('no pan'),
            16 => trans('hidden room'),
            17 => trans('anonymous man'),
            18 => trans('colored name'),
            19 => trans('profile visitors hide in'),
            25 => trans('Special Id'),
            21 => trans('sound effect'),
            22 => trans('upload GIF image'),
        ];

        $getTypeTranslations = [
            1 => trans('vip level automatic acquisition'),
            4 => trans('purchase'),
            6 => trans('limited time purchase'),
        ];


        $result->getCollection()->transform(function ($item) use ($typeTranslations, $getTypeTranslations, $type) {
            if ($type != 25) {
                $item->type = $typeTranslations[$item->type] ?? trans('Unknown Type');
            }
            $item->get_type = $getTypeTranslations[$item->get_type] ?? trans('Unknown Get Type');
            return $item;
        });
        return Common::apiResponse(true, 'Success', $result);
    }

    public function store(Request $request)
    {

        $validatedData = $request->validate([
            'get_type' => 'required|integer',
            'type' => 'required|integer',
            'name' => 'required|string',
            'name_en' => 'nullable|string',
            'title' => 'nullable|string',
            'title_en' => 'nullable|string',
            'price' => 'nullable|numeric',
            'enable' => 'nullable|boolean',
            'level' => 'nullable|integer',
            'is_active_for_vip' => 'nullable|boolean',
            'exp' => 'nullable|integer',
            'show_img' => 'nullable|image',
            'img2' => 'nullable|file',
            'image_type1' => 'nullable|string',
            'profile_frame_type' => 'nullable|string',
            'color' => 'nullable|string',
            'expire' => 'nullable|integer',
            'num' => 'nullable|integer',
        ]);

        if ($request->hasFile('show_img')) {

        $validatedData['show_img'] = Common::upload('images', $request->file('show_img'));
        }
        if ($request->hasFile('img2')) {

        $validatedData['img2'] = Common::upload('images', $request->file('img2'));
        }


        $imageType = $request->input('image_type1') ?? $request->input('profile_frame_type');
        if (!$imageType) {
            return Common::apiResponse(false,'الرجاء اختيار نوع الصوره');
        }
        $validatedData['image_type'] = $imageType;

        $ware = Ware::create($validatedData);


        return Common::apiResponse(true, 'Success', $ware);
    }

    public function update_enable(Request $request, $id){
        $request->validate([
            'enable' => 'required',
        ]);
        $ware = Ware::findOrFail($id);


        $ware->update([
            'enable' =>  $request->enable,
        ]);

        return Common::apiResponse(true, 'Success', $ware);
    }

    public function delete_all(Request $request){

        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);

        Ware::whereIn('id', $ids)->delete();

        return Common::apiResponse(true,'Success');
    }


    public function dedicate($id, Request $request)
    {
        $user = User::query()->searchByUuid($request->user_uuid)->first();
        if (!$user) {
            return Common::apiResponse(false,__('dashboard.userNotFound'));
        }
            $ware = Ware::findOrFail($id);
            if ($ware->type == 25){
               $special_id_check= Pack::query()->where('target_id', $ware->id)->first();
               if ($special_id_check){
                return Common::apiResponse(false,__('dashboard.taken'));
               }
            }

            $pack = Pack::query()->where('user_id', $user->id)->where('target_id', $ware->id)->first();
            if ($pack) {
                if ($pack->expire == 0) return Common::apiResponse(false,__('dashboard.chickTaken'));
                if ($pack->expire > now()->timestamp) {
                    if ($ware->expire != 0) {
                        DB::beginTransaction();
                        try {

                            $pack->expire += (($request->days ??$ware->expire) * 86400);
                            $pack->save();
                            if ($ware->type == 25) {
                                $user->special_id = $ware->value;
                                $user->save();
                            }
                            DB::commit();
                            (new UserCounterServices)->eventUser($user,'mybag',1);
                            return Common::apiResponse(true,__('dashboard.successful'));
                        } catch (\Exception $exception) {
                            DB::rollBack();
                            return Common::apiResponse(false,'خطا غير متوقع' );
                        }
                    } else {
                        return Common::apiResponse(false,__('dashboard.chickTaken'));
                    }
                } else {
                    $pack->delete();
                }
            }
            DB::beginTransaction();
            try {
                $arr['user_id'] = $user->id;
                $arr['type'] = $ware->type;
                $arr['get_type'] = $ware->get_type;
                $arr['target_id'] = $ware->id;
                $arr['num'] = 1; //$qty;
                $arr['expire'] = $request->days ? time() + (($request->days ?? $ware->expire) * 86400) : 0;
                $arr['is_read'] = 1;
                $arr['receive_type'] = 'dedicate-wares';

                $enableVipAuto = Common::getConf('enable_vip_auto') ?? "false";
                $arr['is_used'] = $enableVipAuto === "true" ? 1 : 0;

                Pack::query()->create($arr);
                if ($ware->type == 25) {
                    $user->special_id = $ware->value;
                    $user->save();
                }
                DB::commit();
                (new UserCounterServices)->eventUser($user,'mybag',1);
                CustomNotification::wareVip($user, $request->days, $ware->name, $ware->show_img);
                return Common::apiResponse(true,__('dashboard.successful'));
            } catch (\Exception $exception) {
                DB::rollBack();
                return Common::apiResponse(false,'خطا غير متوقع');
            }
    }
}
