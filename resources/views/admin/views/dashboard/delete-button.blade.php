<form action="{{ $url }}" method="POST" style="display:inline-block;">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Are you sure?')">
        <i class="fa fa-trash"></i> Delete
    </button>
</form>
