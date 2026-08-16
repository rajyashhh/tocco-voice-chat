<?php

namespace App\Admin\Controllers;

use App\Models\Coin;
use Firebase\JWT\JWT;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Http;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;

class CoinController extends MainController
{
    use HasResourceActions;

    public $permission_name = 'coins';
    public $hiddenColumns = [];
       public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('coins'))
            ->body($this->grid()));
    }

   
    

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('coins'))
            ->body($this->form()));
    }

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return $this->form()->store();
    }



    protected function grid()
    {
        $paymentGatwayId = request('paymentGatwayId');
        $grid = new Grid(new Coin);
        $grid->model()->where("payment_gateway_id", $paymentGatwayId);
        $grid->id(__('ID'));
        $grid->column('usd', __('usd'))->display(function ($usd) {
            $image = asset('images/dollar.jpg'); // Adjust path as needed
            return "<div style='display: flex; align-items: center; '>

                        <span>{$usd}</span>
                          <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });
        $grid->column('coin', __('coin'))->display(function ($usd) {

            $image = asset('images/coin.png'); // تأكد من أن الصورة موجودة

            return "<div style='display: flex; align-items: center; gap: 5px;'>
                        <span>{$usd}</span>
                        <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });
        $this->extendGrid($grid);
        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->tools(function (Grid\Tools $tools) {
            $url = url('admin/payment-coins');
            $customButtonHTML = <<<HTML
                     <div style="display: contents; align-items: center;">
                        <a href="{$url}" class="btn btn-sm btn-info" style="margin-right: 10px;">
                            <i class="fa fa-arrow-left"></i> الرجوع
                        </a>
                    </div>
                HTML;
            $tools->append($customButtonHTML);
        });
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Coin::findOrFail($id));

        $show->id('ID');
        $show->usd('usd');
        $show->coin('coin');
        $this->extendShow($show);
        return $show;
    }


    public function edit($id, Content $content)
    {
        $id = request()->route('id');
        $form = $this->form()->edit($id);
        return parent::edit($id, $content
            ->header(trans('admin.edit'))
            ->description(trans('admin.description'))
            ->body($form));
    }
    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('coins'))
            ->body($this->detail($id)));
    }

    public function update($id)
    {
        Permission::check('edit-' . $this->permission_name);

        $id = request()->route('id');
        return $this->form()->update($id);
    }

    protected function form()
    {
        $form = new Form(new Coin);

        $form->display(__('ID'));
        $form->hidden('payment_gateway_id')->value(request('paymentGatwayId'));
        $form->text('usd', __('usd'));
        $form->text('coin', __('coin'));
        return $form;
    }

    protected function fetchData()
    {
        return

            $response = Http::get('https://api.appstoreconnect.apple.com/v1/inAppPurchases/{id}', [
                'apiKey' => 'J27XVDV4X5',
            ]);
    }

    protected function createCoin(Request $request)
    {
        //$unique_id = $data['apple_id'];
        $teamId = config('apple.apple_team_id'); // Use the correct environment variable name
        $keyId = config('apple.apple_key_id');//"GNDGZ4LFR4"/*config('apple.apple_key_id')*/; // Use the correct environment variable name
        $redirectUri = config('apple.apple_redirect_uri'); // Use the correct environment variable name
        $iat = strtotime('now');
        $exp = strtotime('+60days');
        $keyContent = file_get_contents(config('apple.apple_service_file'));
        $token = JWT::encode([
            'iss' => '732cdce3-47cc-4d3d-ba88-8d2a84701f77',
            'iat' => $iat,
            'exp' => $exp,
            'aud' => 'https://appleid.apple.com',

        ], $keyContent, 'ES256', $keyId);

        // $paymentData = $request->input('paymentData');
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post('https://api.appstoreconnect.apple.com/v2/inAppPurchases', [
            'data' => [
                "type"       => "inAppPurchases",
                'attributes' => [
                    "name"                        => "Seattle Neighborhood Coffee Map",
                    "productId"                   => "MAPNEIGHBORHOODS",
                    "inAppPurchaseType"           => "CONSUMABLE",
                    "reviewNote"                  => "This is a neighborhood map for helping to find awesome coffee shops.",
                    "availableInAllTerritories"   => true,

                ],
                'relationships' => [
                    'app'       => [
                        'data' => [
                            "type"  => "apps",
                            'id' => "6446148572",
                        ]
                    ]
                ]
            ]
        ]);
        return Common::apiResponse(true, '', $response, 200);
    }

    protected function setPrice(Request $request): void
    {
        $keyId = config('apple.apple_key_id');//"GNDGZ4LFR4"/*config('apple.apple_key_id')*/; // Use the correct environment variable name
        $iat = strtotime('now');
        $exp = strtotime('+60days');
        $keyContent = file_get_contents(config('apple.apple_service_file'));
        $token = JWT::encode([
            'iss' => '732cdce3-47cc-4d3d-ba88-8d2a84701f77',
            'iat' => $iat,
            'exp' => $exp,
            'aud' => 'https://appleid.apple.com',

        ], $keyContent, 'ES256', $keyId);
        $response = Http::withHeaders([
            'Authorization' => $token,
            'Content-Type' => 'application/json',
        ])->post('https://api.appstoreconnect.apple.com/v2/inAppPurchases', [

            "data" => [
                "type"          => "inAppPurchases",
                "id"            => $request['id'],
                "attributes"    => [],
                "relationships" => [
                    "prices"      => [
                        "data"      => [
                            [
                                "type"  => "inAppPurchasePrices",
                                "id"    => "${price1}"
                            ]
                        ]
                    ]
                ]
            ],
            "included"       => [
                [
                    "type"       => "inAppPurchasePrices",
                    "id"         => "${price1}",
                    "attributes" => [
                        "startDate" => null
                    ],
                    "relationships"    => [
                        "inAppPurchaseV2" => [
                            "data"         => [
                                "type"       => "inAppPurchasesV2",
                                "id"         => $request['id']
                            ]
                        ],
                        "inAppPurchasePricePoint"     => [
                            "data"        => [
                                "type"      => "inAppPurchasePricePoints",
                                "id"        => "NjQ0NjQ1MjYxNV91c181"
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }
}
