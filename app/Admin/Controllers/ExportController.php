<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Admin\Extensions\UserExporter;
use App\Admin\Extensions\AgencyExporter;
use App\Admin\Extensions\FamilyExporter;
use App\Admin\Extensions\WalletExportUser;
use App\Admin\Extensions\FamilyLevelExport;
use App\Admin\Extensions\WalletExportAgency;
use App\Admin\Extensions\AgencyMangerExporter;
use App\Admin\Extensions\ChargeAgencyExporter;
use App\Admin\Extensions\ExchangeChargeExport;
use App\Admin\Extensions\ExchangeCoinExporter;
use App\Admin\Extensions\ExchangeDiamondExporter;


class ExportController extends Controller
{
    public function usersSallaryTargets()
    {
        $export = new UserExporter();
        $fileName = 'users_target_salary.csv';

        return Excel::download($export, $fileName);
    }

    public function usersAgencyTargets()
    {
        $month = request('month');
        $year = request('year');
        $id = request('id');

        return Excel::download(
            new AgencyExporter($id, $month, $year),
            'agency_report.csv'
        );
    }

    public function agencyMangerExport()
    {
        $userId = request('user_id'); // استقبلناه بشكل مسطح من الرابط

        $export = new AgencyMangerExporter($userId);
        $fileName = 'agency_manger_export_' . now()->format('Y_m_d_His') . '.csv';

        return Excel::download($export, $fileName);
    }

    public function chargeAgencies()
    {
        $uuid = request('owner')['uuid'] ?? null;
        $id = request('id') ?? null;

        $export = new ChargeAgencyExporter($id, $uuid);
        $fileName = 'charge_agencies_' . now()->format('Y_m_d_His') . '.csv';

        return Excel::download($export, $fileName);
    }

    public function walletExportUser()
    {
        $id = request('uuid');
        $month = request('month');
        $year = request('year');
        $export = new WalletExportUser($id, $month, $year);
        $fileName = 'wallet_users.csv';

        return Excel::download($export, $fileName);
    }

    public function walletExportAgency()
    {
        $id = request('id');
        $month = request('month');
        $year = request('year');

        $export = new WalletExportAgency($id, $month, $year);
        $fileName = 'wallet_agencies.csv';

        return Excel::download($export, $fileName);
    }

    public function exchangeDiamondExcel()
    {
        $from_date = request('from_date');
        $to_date = request('to_date');
        $uuid = request('uuid');

        return Excel::download(
            new ExchangeDiamondExporter($uuid, $from_date, $to_date),
            'exchange_diamonds.csv'
        );
    }

    public function exchangeCoinExcel()
    {
        $from_date = request('from_date');
        $to_date = request('to_date');
        $uuid = request('uuid');
        $trx = request('trx');
        $status = request('status');
        $method = request('method');

        return Excel::download(
            new ExchangeCoinExporter($uuid, $from_date, $to_date, $trx, $status, $method),
            'exchange_coins.csv'
        );
    }

    public function exchangeChargeExcel()
    {
        $from_date = request('from_date');
        $to_date = request('to_date');
        $filtering = request('filtering');
        $name = request('name');
        $type = request('filter_type');

        return Excel::download(
            new ExchangeChargeExport($name, $from_date, $to_date, $filtering, $type),
            'exchange_charges.csv'
        );
    }

    public function familyLevelExcel()
    {
        return Excel::download(
            new FamilyLevelExport(),
            'family_levels.csv'
        );
    }

    public function familiesExcel()
    {
        $date = request('date');
        $id = request('id');
        $uuid = request('uuid');
   
        return Excel::download(
            new FamilyExporter($date, $id, $uuid),
            'families.csv'
        );
    }
}
