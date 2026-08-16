<style>
    #importJsonModal .modal-content {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 25px 60px rgba(0,0,0,0.15);
    }
    #importJsonModal .modal-header {
        background: linear-gradient(135deg, #059669 0%, #10b981 50%, #34d399 100%);
        padding: 20px 24px;
        border: none;
    }
    #importJsonModal .modal-header .modal-title {
        color: #fff;
        font-size: 18px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    #importJsonModal .modal-header .close {
        color: #fff;
        opacity: 0.8;
        text-shadow: none;
        font-size: 24px;
        transition: opacity 0.2s;
    }
    #importJsonModal .modal-header .close:hover {
        opacity: 1;
    }
    #importJsonModal .modal-body {
        padding: 28px 24px;
        background: #f8fafb;
    }
    #importJsonModal .form-section {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
        border: 1px solid #e5e7eb;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    #importJsonModal .form-section:hover {
        border-color: #10b981;
        box-shadow: 0 2px 12px rgba(16,185,129,0.08);
    }
    #importJsonModal .form-section:focus-within {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
    }
    #importJsonModal .section-label {
        font-weight: 700;
        font-size: 14px;
        color: #1f2937;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    #importJsonModal .section-label .icon-circle {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
    }
    #importJsonModal .icon-green { background: #d1fae5; color: #059669; }
    #importJsonModal .icon-blue { background: #dbeafe; color: #2563eb; }
    #importJsonModal .icon-purple { background: #ede9fe; color: #7c3aed; }
    #importJsonModal .section-hint {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 6px;
    }
    #importJsonModal .or-divider {
        text-align: center;
        position: relative;
        margin: 20px 0;
    }
    #importJsonModal .or-divider::before {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        top: 50%;
        height: 1px;
        background: #e5e7eb;
    }
    #importJsonModal .or-divider span {
        background: #f8fafb;
        padding: 0 16px;
        position: relative;
        color: #9ca3af;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    #importJsonModal .form-control {
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        transition: border-color 0.2s, box-shadow 0.2s;
        font-size: 14px;
    }
    #importJsonModal .form-control:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
    }
    #importJsonModal textarea.form-control {
        font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace;
        font-size: 12.5px;
        line-height: 1.6;
        background: #fafafa;
        resize: vertical;
    }
    #importJsonModal textarea.form-control:focus {
        background: #fff;
    }
    #importJsonModal .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid #f0f0f0;
        background: #fff;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    #importJsonModal .btn-cancel {
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        color: #6b7280;
        padding: 10px 22px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.2s;
    }
    #importJsonModal .btn-cancel:hover {
        background: #e5e7eb;
        color: #374151;
    }
    #importJsonModal .btn-import {
        background: linear-gradient(135deg, #059669, #10b981);
        border: none;
        color: #fff;
        padding: 10px 28px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(16,185,129,0.3);
    }
    #importJsonModal .btn-import:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(16,185,129,0.4);
    }
    #importJsonModal .btn-import:active {
        transform: translateY(0);
    }
    #importJsonModal .btn-import:disabled {
        opacity: 0.7;
        transform: none;
        box-shadow: none;
    }
    #importJsonModal .validation-msg {
        margin-top: 10px;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        display: none;
    }
    #importJsonModal .validation-msg.success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    #importJsonModal .validation-msg.error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
    #importJsonModal .validation-msg.warning {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
    }
    #importJsonModal select.form-control {
        appearance: none;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23374151' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 14px;
        padding-right: 40px;
    }
</style>

<div class="modal fade" id="importJsonModal" tabindex="-1" role="dialog" aria-labelledby="importJsonModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="importJsonForm" method="POST" action="{{ $importUrl }}">
                @csrf
                {{-- Header --}}
                <div class="modal-header">
                    <h4 class="modal-title" id="importJsonModalLabel">
                        <i class="fa fa-cloud-download"></i>
                        Import Games from JSON
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                {{-- Body --}}
                <div class="modal-body">
                    {{-- Option 1: URL --}}
                    <div class="form-section">
                        <div class="section-label">
                            <span class="icon-circle icon-green"><i class="fa fa-link"></i></span>
                            Import from URL
                        </div>
                        <input type="url" name="json_url" class="form-control"
                               value=""
                               placeholder="https://example.com/games.json">
                        <div class="section-hint">
                            <i class="fa fa-info-circle"></i>
                            Enter the URL that returns a JSON array of game objects
                        </div>
                    </div>

                    {{-- OR Divider --}}
                    <div class="or-divider">
                        <span>OR</span>
                    </div>

                    {{-- Option 2: Paste JSON --}}
                    <div class="form-section">
                        <div class="section-label">
                            <span class="icon-circle icon-blue"><i class="fa fa-code"></i></span>
                            Paste JSON Data
                        </div>
                        <textarea name="json_data" id="jsonDataInput" class="form-control" rows="10"
                                  placeholder='[
  {
    "gameId": "101",
    "name": "Roulette",
    "title": "转盘2",
    "ver": 12,
    "full_url": "https://...",
    "hd_url": "https://...",
    "half_url": "https://..."
  }
]'></textarea>
                        <div class="validation-msg" id="jsonValidationMsg"></div>
                    </div>

                    {{-- Game Provider Type --}}
                    <div class="form-section">
                        <div class="section-label">
                            <span class="icon-circle icon-purple"><i class="fa fa-gamepad"></i></span>
                            Game Provider Type
                        </div>
                        <select name="game_type" class="form-control">
                            <option value="4">UTD Games</option>
                        </select>
                        <div class="section-hint">
                            <i class="fa fa-info-circle"></i>
                            Select which game provider these games belong to
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" data-dismiss="modal">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                    <button type="submit" id="importJsonSubmitBtn" class="btn btn-import">
                        <i class="fa fa-cloud-download"></i> Import Games
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var textarea = document.getElementById('jsonDataInput');
    var msgDiv = document.getElementById('jsonValidationMsg');

    if (textarea) {
        textarea.addEventListener('input', function() {
            var val = this.value.trim();
            if (!val) {
                msgDiv.style.display = 'none';
                msgDiv.className = 'validation-msg';
                return;
            }
            try {
                var parsed = JSON.parse(val);
                if (!Array.isArray(parsed)) {
                    showMsg('error', '<i class="fa fa-times-circle"></i> JSON must be an array of objects');
                    return;
                }
                if (parsed.length === 0) {
                    showMsg('warning', '<i class="fa fa-exclamation-triangle"></i> Array is empty — nothing to import');
                    return;
                }
                var requiredFields = ['gameId', 'name', 'full_url'];
                var missing = [];
                requiredFields.forEach(function(f) {
                    if (!(f in parsed[0])) missing.push(f);
                });
                if (missing.length > 0) {
                    showMsg('error', '<i class="fa fa-times-circle"></i> Missing required fields in first object: <strong>' + missing.join(', ') + '</strong>');
                    return;
                }
                showMsg('success', '<i class="fa fa-check-circle"></i> Valid JSON — <strong>' + parsed.length + ' games</strong> ready to import');
            } catch (e) {
                showMsg('error', '<i class="fa fa-times-circle"></i> Invalid JSON: ' + e.message);
            }
        });
    }

    function showMsg(type, html) {
        msgDiv.innerHTML = html;
        msgDiv.className = 'validation-msg ' + type;
        msgDiv.style.display = 'block';
    }

    var form = document.getElementById('importJsonForm');
    if (form) {
        form.addEventListener('submit', function() {
            var btn = document.getElementById('importJsonSubmitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Importing...';
        });
    }
});
</script>
