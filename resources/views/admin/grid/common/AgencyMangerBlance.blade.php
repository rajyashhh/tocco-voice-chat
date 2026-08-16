<div class="box box-solid">
    <div class="box-header with-border">
        <h3 class="box-title">الحقول</h3>

        <div class="box-tools">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="box-body no-padding">
        <ul class="nav nav-pills nav-stacked">
            {{-- <li><a href="?name=users" class="charge_action"><i class="fa fa-arrow-right text-red"></i>{{__('users report')}}</a></li> --}}
            <li><a href="" class="charge_action">
            @php
           

                 $loggedInUserId =  Encore\Admin\Facades\Admin::user()->app_id;
                 
                // $fromconfig=App\Models\Config::where('name','agency_manager_prersnteege')->first();
                // $AgencyMangerPullingOut = App\Models\AgencyMangerPullingOut::where('agency_manger_id',$loggedInUserId)->first();
                // $pulling_out            = $AgencyMangerPullingOut->amount??0;
                // $result                 = 5 * ( intval($fromconfig->value) / 100);
                // $endResult              = $result - intval($pulling_out);
                $common                 = App\Helpers\Common::AgencyMangerCash();
                // $end =$common;
                // return $endResult;
            @endphp
            {{ $common  }}
               
            </a></li>
        </ul>
    </div>
    <!-- /.box-body -->
</div>

