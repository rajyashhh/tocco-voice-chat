<?php

namespace App\Exports;

use App\Models\Agency;
use App\Models\Room;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AgenciesExport implements FromCollection, WithHeadings
{public function collection()
    {
        return Agency::select([
            'id', 'owner_id', 'name', 'notice', 'status', 'phone', 'url', 'img', 'contents', 
            'created_at', 'updated_at', 'old_usd', 'target_usd', 'target_token_usd', 'app_owner_id', 
            'salary',  'agency_manger_id', 'agency_dash_manger_id', 
            'deleted_at', 'monthly_target', 'password', 'coins'
        ])->get();
    }
    
    public function headings(): array
    {
        return [
            'ID', 'Owner ID', 'Name', 'Notice', 'Status', 'Phone', 'URL', 'Image', 'Contents', 
            'Created At', 'Updated At', 'Old USD', 'Target USD', 'Target Token USD', 'App Owner ID', 
            'Salary', 'Shipping Agency', 'Host Agency', 'Agency Manager ID', 'Agency Dashboard Manager ID', 
            'Deleted At', 'Monthly Target', 'Password', 'Coins'
        ];
    }
    
}
