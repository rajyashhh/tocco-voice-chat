<?php

namespace Modules\Vip\Services;

use Modules\Vip\Entities\VipPrivilege;
use App\Models\Ware;
use Encore\Admin\Form;

class VipService
{
    public function handleSaving(Form $form): void
    {
        $privilegs = request('privilegs', []);
        $level = request('level');
    
        Ware::where('level', $level)->update([
            'is_active_for_vip' => false
        ]);
    
        $usedTypes = [];
    
        foreach ($privilegs as $privilegId) {
            $privilege = VipPrivilege::find($privilegId);
    
            if (!$privilege) {
                continue;
            }
    
            $type = $privilege->type;
    
            if (in_array($type, $usedTypes)) {
                admin_error('خـطأ', 'لا يمكن اختيار أكثر من امتياز من نفس النوع: ' . $type);
                return;
            }
    
            $usedTypes[] = $type;
    
            $ware = Ware::where('get_type', 1)
                ->where('level', $level)
                ->where('type', $type)
                ->first();
    
            if (!$ware) {
                $name = self::getWareTypeName($type); 
    
                Ware::create([
                    'get_type'          => 1,
                    'type'              => $type,
                    'name'              => $name,
                    'name_en'           => $name,
                    'title'             => $name,
                    'title_en'          => $name,
                    'level'             => $level,
                    'price'             => 0,
                    'enable'            => 1,
                    'expire'            => 0, 
                    'show_img'          => '1.png',
                    'image_type'        => 'png',
                    'is_active_for_vip' => 1,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            } else {
                $ware->update([
                    'is_active_for_vip' => 1,
                    'enable' => 1,
                ]);
            }
        }
    
        session()->forget('show_alert_vip');
    }
    private static function getWareTypeName(int $type): string
    {
        $types = [
            1 => 'Gemstone',
            3 => 'Card Scroll',
            4 => 'Avatar Frame',
            5 => 'Bubble Frame',
            6 => 'Entering Special Effects',
            7 => 'Microphone Aperture',
            8 => 'Badge',
            9 => 'NoKick',
            10 => 'Icon',
            11 => 'Intro Animation',
            12 => 'Maple',
            13 => 'Hide Country',
            14 => 'VIP Gifts',
            15 => 'No Pan',
            19 => 'Profile Visitors Hide In',
            20 => 'Hide Last Active',
            28 => 'Profile Frame',
            29 => 'Being Kicked',
            30 => 'Anti Ban',
        ];

        return $types[$type] ?? 'Unknown Type';
    }
}
