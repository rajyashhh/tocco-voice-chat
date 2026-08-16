@php
$tabs = [];
foreach ($categories as $category) {
    $tabs[$category->id] = $category->title[$locale] ?? $category->title['en'] ?? '';
}
$currentCategoryId = $model->gift_category_id ?? '';
$vipLevel = $model->vip_level ?? '';

$labelWinProbability = __('win probability');
$labelMin = __('min percentage') . ' (%)';
$labelMid = __('mid percentage'). ' (%)';
$labelMax = __('max percentage'). ' (%)';
$labelVIP = __('VIP Level');
$scopeText = __('scope for multiplies');
$placeholderVIP = __('Less than 256');
@endphp

<div class="form-group">

    <input type="hidden"
       name="gift_category_id"
       value="{{ request()->route('type') ?? $currentCategoryId }}">
</div>

<div id="extra_fields_container"></div> {{-- dynamic fields container --}}





<script>
$(document).ready(function () {

    const categories = @json($categories); // contains ONLY route category
    const container = $('#extra_fields_container');

    const labels = {
        win: @json($labelWinProbability),
        min: @json($labelMin),
        mid: @json($labelMid),
        max: @json($labelMax),
        vip: @json($labelVIP),
        scope: @json($scopeText),
        vipPlaceholder: @json($placeholderVIP)
    };

    function renderLuckyGift(data = {}) {
        // اقتصاد هدايا الحظ (RTP والمضاعفات) بقى مركزيًا في صفحة «إعداد هدية الحظ»
        // مع محرّك V7. مفيش نسب لكل هدية بعد كده — الحقول القديمة (نسبة الربح/أقل/
        // متوسط/أعلى نسبة) اتشالت لأن المحرّك ماكانش بيقراها أصلًا.
        container.html(`
<div class="alert alert-info" style="margin-top:8px;">
    <i class="fa fa-info-circle"></i> ${@json(__('إعدادات اقتصاد هدايا الحظ (RTP والمضاعفات) تُضبط مركزيًا من صفحة «إعداد هدية الحظ» — لا توجد نسب لكل هدية.'))}
</div>
`);
    }

    function renderVIP(level = '') {
        container.html(`
<div class="form-group">
    <label>${labels.vip}</label>
    <input type="number" name="vip_level" class="form-control"
           placeholder="${labels.vipPlaceholder}"
           value="${level}">
</div>
`);
    }

    // ✅ AUTO RENDER FROM ROUTE CATEGORY
    console.log('[gift-type] categories array:', categories);
    if (categories.length > 0) {
        const category = categories[0];
        console.log('[gift-type] selected category:', category);
        if (category.type === 'lucky_gift') {
            renderLuckyGift();
        } else if (category.type === 'vip') {
            console.log('[gift-type] rendering vip fields', @json($vipLevel));
            renderVIP(@json($vipLevel));
        } else {
            console.log('[gift-type] unsupported category type:', category.type);
        }
    } else {
        console.log('[gift-type] categories array is empty');
    }

});
</script>

