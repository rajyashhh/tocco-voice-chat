<div class="box box-solid box-lg">
    <div class="box-header with-border">
        <h3 class="box-title">التاريخ</h3>
    </div>
    <div class="box-body ">
        <ul class="nav nav-pills nav-stacked">
            <div class="row mb-3">
                <form action="">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="start_date">{{__('Start Date')}}</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ old('start_date', request('start_date')) }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="end_date">{{__('End Date')}}</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ old('end_date', request('end_date')) }}">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <button class="btn btn-primary btn-sm">سلم</button>
                        <a href="{{ url('admin/app-earned') }}" class="btn btn-danger btn-sm">تفريغ</a>
                        
                    </div>
                </div>
            </form>
            </div>
        </ul>
    </div>
    <!-- /.box-body -->
</div>
