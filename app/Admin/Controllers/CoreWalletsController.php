<?php

namespace App\Admin\Controllers;

use App\Models\CoreWallets;
use App\Models\CoreWalletTransaction;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Widgets\Box;

use function Laravel\Prompts\error;

class CoreWalletsController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */


    public $permission_name = 'app-wallet';

    // public function index(Content $content)
    // {
    //     return parent::index($content
    //         ->title(trans('Application wallet'))
    //         ->row(function (Row $row) {
    //             $row->column(12, view('admin.grid.common.CoreWallet'));
    //         })
    //         // ->row(function (Row $row) {
    //         //     $row->column(12, $this->grid());
    //         // })
    //     );
    // }

    public function index(Content $content)
    {
        $icons = [
            'app_wallet'             => '🪙',
            'owner_wallet'           => '👔',
            'game_wallet'            => '🎲',
            'lucky_box'              => '📦',
            'host_agency'            => '🏢',
            'agency'                 => '💼',
            'lucky_gifts'            => '🎁',
            'chinese_games'          => '🐉',
            'games'                  => '🎮',
            'shipping_agents'        => '🚚',
            'payment_gateways'       => '💳',
            'mall'                   => '🏪',
            'vip'                    => '👑',
            'ads'                    => '📢',
            'invitation_code_wallet' => '👥',
        ];

        $canTransfer = Admin::user()->can('*') || Admin::user()->can('transfer-switch-app-wallet');

        return parent::index($content
            ->title(__('Application wallet'))
            ->body(view('admin.core_wallets.index', [
                'coreWallets' => CoreWallets::get(),
                'icons' => $icons,
                'canTransfer' => $canTransfer
            ])));
    }

    protected function grid2()
    {
        $form = new Box();

        $form->view('admin.grid.common.CoreWallet');

        return $form;
    }

    protected function submitTransfer(Request $request)
    {
        if (!Admin::user()->can('*') && !Admin::user()->can('transfer-switch-app-wallet')) {
            abort(403);
        }

        $request->validate([
            'from_wallet_id' => 'required',
            'to_wallet_id' => 'required',
            'amount' => 'required|numeric|min:1',
        ]);

        $adminId = auth()->id();

        return DB::transaction(function () use ($request, $adminId) {
            $fromWallet = CoreWallets::lockForUpdate()->find($request->from_wallet_id);
            $toWallet = CoreWallets::lockForUpdate()->find($request->to_wallet_id);

            if (!$fromWallet || !$toWallet) {
                abort(404);
            }

            if ($fromWallet->coins < $request->amount) {
                return response()->json([
                    'status' => 0,
                    'message' => 'plz check coins wallet',
                ]);
            }

            if ($toWallet->is_negative && $toWallet->coins < $request->amount) {
                $allowed = $toWallet->coins;

                throw new \Exception(
                    __('wallet.insufficient_balance', ['amount' => $allowed])
                );
            }

            $fromWallet->coins -= $request->amount;
            $fromWallet->save();

            if ($toWallet->is_negative) {
                $toWallet->coins -= $request->amount;
            } else {
                $toWallet->coins += $request->amount;
            }

            $toWallet->save();

            CoreWalletTransaction::create([
                'from_wallet' => $request->from_wallet_id,
                'to_wallet' => $request->to_wallet_id,
                'amount' => $request->amount,
                'admin_id' => $adminId,
            ]);

            return response()->json([
                'status' => 1,
                'message' => 'Done',
            ]);
        });
    }
}
