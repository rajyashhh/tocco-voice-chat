

<style>
    /* تصميم السايد بار */
    .settings-container {
        display: flex;
        min-height: 100vh;
        background: #1e1e1e;
    }

    .settings-sidebar {
        width: 250px;
        background: #222;
        padding: 20px;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.5);
        color: white;
    }

    .settings-sidebar h4 {
        text-align: center;
        color: #ff9800;
    }

    .settings-menu button {
        display: block;
        width: 100%;
        text-align: right;
        padding: 12px;
        background: #333;
        color: white;
        border: none;
        margin-bottom: 5px;
        cursor: pointer;
        font-size: 16px;
        transition: background 0.3s ease;
    }

    .settings-menu button:hover,
    .settings-menu button.active {
        background: #ff9800;
    }

    /* المحتوى الرئيسي */
    .settings-content {
        flex-grow: 1;
        padding: 30px;
        background: #121212;
        color: white;
    }

    .settings-section {
        display: none;
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="settings-container">
    
    <!-- السايد بار -->
    <nav class="settings-sidebar">
        <h4>إعدادات القوالب</h4>
        <div class="settings-menu">
            @foreach(Cache::get('languages', ['ar' => 'العربية', 'en' => 'الإنجليزية', 'tr' => 'التركية', 'hi' => 'الهندية']) as $code => $lang)
                <button class="language-btn" onclick="showSection('{{ $code }}')">
                    {{ $lang }}
                </button>
            @endforeach
        </div>
    </nav>

    <!-- المحتوى الرئيسي -->
    <div class="settings-content">
        <class="mb-4">  {{ __('create') }}</h2>

        @foreach(Cache::get('languages', ['ar' => 'العربية', 'en' => 'الإنجليزية', 'tr' => 'التركية', 'hi' => 'الهندية']) as $code => $lang)
            <form id="section-{{ $code }}" action="{{ url('/admin/notification-templates') }}" method="POST" class="settings-section">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('key') }}</label>
                    <input type="text" name="key" class="form-control">
                </div>

                <h3 class="mt-4">{{ $lang }}</h3>
                
                <div class="mb-3">
                    <label class="form-label">{{ __("$lang titel") }}</label>
                    <input type="text" name="title_{{ $code }}" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __("$lang Message") }}</label>
                    <textarea name="message_{{ $code }}" class="form-control"></textarea>
                </div>

                <button type="submit" class="btn btn-primary mt-3">إنشاء</button>
            </form>
        @endforeach
    </div>
</div>

<!-- JavaScript لاختيار اللغة -->
<script>
    function showSection(sectionId) {
        document.querySelectorAll('.settings-section').forEach(section => {
            section.style.display = 'none';
        });

        document.getElementById("section-" + sectionId).style.display = 'block';

        document.querySelectorAll('.language-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        event.currentTarget.classList.add('active');
    }

    // عرض القسم الأول افتراضيًا
    document.addEventListener("DOMContentLoaded", function() {
        let firstSection = document.querySelector('.settings-section');
        if (firstSection) firstSection.style.display = 'block';
        
        let firstButton = document.querySelector('.language-btn');
        if (firstButton) firstButton.classList.add('active');
    });
</script>





<form  method="POST">
