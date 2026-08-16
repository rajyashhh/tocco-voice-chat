<style>
    .settings-sidebar {
        width: 250px;
        background: #222;
        min-height: 400px;
        padding: 20px;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.5);
    }

    .settings-sidebar h2 {
        text-align: center;
        color: #ff9800;
    }

    .settings-sidebar {

        background-color: var(--table-background-color);
        display: inline;
        justify-content: center;
        align-items: center;
        padding: 10px 0;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        color: var(--text-secondary-color);
        overflow-x: auto;
        /* يجعل الشريط قابلاً للتمرير عند الحاجة */
        white-space: nowrap;
        /* يمنع العناصر من النزول لسطر جديد */
        scrollbar-width: thin;
        /* تقليل عرض شريط التمرير */
    }

    .settings-menu {
        display: block;
        gap: 4px;
        color: var(--text-secondary-color);
        overflow-x: auto;
        /* يجعل الشريط قابلاً للتمرير عند الحاجة */
        white-space: nowrap;
        /* يمنع العناصر من النزول لسطر جديد */
        scrollbar-width: thin;
        /* تقليل عرض شريط التمرير */

    }

    .settings-menu button {
        background-color: transparent;
        border: none;
        padding: 10px 15px;
        width: 200px;
        font-size: 16px;
        cursor: pointer;
        transition: color 0.3s ease-in-out;
        color: black !important;
        border: var(--primary-color) solid 2px  !important;
    }

    .active{
        background-color:  var(--primary-color) !important;
        color: var(--text-secondary-color) !important;
    }
</style>


<div class="settings-sidebar">
    <div class="settings-menu">
        @foreach ($tabs as $key => $label)
            @php
                $isActive = request('tab') === $key || (empty(request('tab')) && $key === 'Appsender');
                $url = request()->url() . '?' . http_build_query(array_merge(request()->except('tab'), ['tab' => $key]));
            @endphp
            <button onclick="fire('{{ $url }}')" class="{{ $isActive? 'active' : '' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>
</div>

<script>
    function fire(url){
        location.href = url;
    }
</script>
