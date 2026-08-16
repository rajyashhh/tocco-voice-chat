<div class="card" style="padding: 28px">
    <div class="card-header">
        <h4>{{__('Edit Image and Description')}}</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.app-setting') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="image">{{__('Image')}}</label>
                <input type="file" name="logo" class="form-control" id="image" required>
            </div>
            <div class="form-group">
                <label for="desc">{{__('Description')}}</label>
                <textarea name="desc" class="form-control" id="desc" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">{{__('Save Changes')}}</button>
        </form>
    </div>
</div>
