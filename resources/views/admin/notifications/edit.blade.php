<?php
require_once app_path('helper/helperType.php');
 $variablesList = implode(', ', VARIABLES);

?>


<style>
    .settings-container { display: flex; min-height: 100vh; background: #1e1e1e; }
    .settings-sidebar { width: 250px; background: #222; padding: 20px; color: white; }
    .settings-sidebar h4 { text-align: center; color: #ff9800; }
    .settings-menu button { display: block; width: 100%; text-align: right; padding: 12px; background: #333; color: white; border: none; margin-bottom: 5px; cursor: pointer; transition: background 0.3s ease; }
    .settings-menu button:hover, .settings-menu button.active { background: #ff9800; }
    .settings-content { flex-grow: 1; padding: 30px; background: #121212; color: white; }
    .settings-section { display: none; animation: fadeIn 0.5s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="settings-container">
    <nav class="settings-sidebar">
        <h4>تعديل القالب</h4>
        <div class="settings-menu">
            @foreach(Cache::get('languages', ['ar' => 'العربية', 'en' => 'الإنجليزية', 'tr' => 'التركية', 'hi' => 'الهندية']) as $code => $lang)
                <button class="language-btn" onclick="showSection('{{ $code }}', event)">{{ $lang }}</button>
            @endforeach
        </div>
    </nav>

    <div class="settings-content">
        <h2 class="mb-4">تعديل القالب</h2>
        <div style="border: 1px solid var(--primary-hover-alpha);
                padding: 15px;
                border-radius: 6px;
                background-color: var(--box-background-color);
                color: var(--text-secondary-color);
                margin-bottom: 15px;">
        <strong style="font-size: 16px; color: var(--primary-color); display: block; margin-bottom: 10px;">🛠 المتغيرات المتاحة:</strong>

        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            @foreach(explode(',', $variablesList) as $variable)
                <span style="background-color: var(--primary-color);
                             color: white;
                             padding: 8px 12px;
                             border-radius: 5px;
                             font-size: 14px;
                             font-weight: bold;
                             display: inline-block;">
                    {{ trim($variable) }}
                </span>
            @endforeach
        </div>
    </div>

    @foreach(Cache::get('languages', ['ar' => 'العربية', 'en' => 'الإنجليزية', 'tr' => 'التركية', 'hi' => 'الهندية']) as $code => $lang)
    <form id="section-{{ $code }}" action="{{ url('/admin/notification-templates') }}" method="POST" class="settings-section">
        @csrf
                @method('PUT')
                <input type="hidden" name="id" value="{{ $template->id }}">

                <div class="mb-3">
                <label class="form-label fw-bold">المفتاح</label>
                <input type="text" name="key" class="form-control" value="{{ $template->key }}" readonly>
            </div>

                <h3 class="mt-4">{{ $lang }}</h3>

                <div class="mb-3">
                    <label class="form-label">{{ $lang }} - العنوان</label>
                    <input type="text" name="title_{{ $code }}" class="form-control" value="{{ optional($template->translations->where('language', $code)->first())->title }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ $lang }} - الرسالة</label>
                    <textarea name="message_{{ $code }}" class="form-control">{{ optional($template->translations->where('language', $code)->first())->message }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary mt-3">حفظ التعديلات</button>
            </form>
        @endforeach
    </div>
</div>

<script>
    function showSection(sectionId, event) {
        document.querySelectorAll('.settings-section').forEach(section => { section.style.display = 'none'; });
        document.getElementById("section-" + sectionId).style.display = 'block';
        document.querySelectorAll('.language-btn').forEach(btn => { btn.classList.remove('active'); });
        if (event) event.currentTarget.classList.add('active');
    }

    document.addEventListener("DOMContentLoaded", function() {
        let firstSection = document.querySelector('.settings-section');
        if (firstSection) firstSection.style.display = 'block';
        let firstButton = document.querySelector('.language-btn');
        if (firstButton) firstButton.classList.add('active');
    });
</script>
