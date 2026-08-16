<?php

namespace App\Exports;

use App\Models\Agency;
use App\Models\GiftLog;
use App\Models\Room;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GiftLogExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $chunkSize = 1000; // حجم الجزء
    
        $giftLogs = collect();
    
        GiftLog::select([
            'id', 'type', 'giftId', 'roomowner_id', 'giftName', 'giftNum', 'giftPrice', 
            'sender_id', 'receiver_id', 'cp_id', 'is_play', 'platform_obtain', 
            'receiver_obtain', 'roomowner_obtain', 'union_id', 'created_at', 
            'updated_at', 'sender_family_id', 'receiver_family_id', 'agency_id', 
            'agency_obtain', 'moent_id', 'pk', 'room_id'
        ])->chunk($chunkSize, function($logs) use (&$giftLogs) {
            $giftLogs = $giftLogs->merge($logs);
        });
    
        return $giftLogs;
    }
    
    public function headings(): array
    {
        return [
            'ID', 'Type', 'Gift ID', 'Room Owner ID', 'Gift Name', 'Gift Num', 'Gift Price', 
            'Sender ID', 'Receiver ID', 'CP ID', 'Is Play', 'Platform Obtain', 
            'Receiver Obtain', 'Room Owner Obtain', 'Union ID', 'Created At', 
            'Updated At', 'Sender Family ID', 'Receiver Family ID', 'Agency ID', 
            'Agency Obtain', 'Moent ID', 'PK', 'Room ID'
        ];
    }
    
    
}
