<div id="chargeSettings" class="settings-section">
    <div class="box-body">
        <div class="section-header mb-4">
            <h4>{{ __('Charge settings') }}</h4>
            <p class="text-muted">{{ __('Control charging permissions for different transfer scenarios') }}</p>
        </div>

        <form action="{{ route('admin.charge.transfer.settings.save') }}" method="POST" class="settings-form">
            @csrf

            <div class="charge-transfer-grid">
                <!-- User to User Transfer -->
                <div class="charge-transfer-card">
                    <div class="charge-transfer-header">
                        <div class="charge-transfer-icon user-to-user">
                            <i class="fas fa-user"></i>
                            <i class="fas fa-arrow-right arrow-icon"></i>
                            <i class="fas fa-user"></i>
                        </div>
                        <h5>{{ __('User to User Transfer') }}</h5>
                    </div>
                    <div class="charge-transfer-body">
                        <div class="form-group switch-group">
                            <label class="switch-label">{{ __('Allow Charging') }}</label>
                            <div class="switch-wrapper">
                                <label class="switch">
                                    <input type="hidden" name="charge_user_to_user" value="0">
                                    <input type="checkbox" name="charge_user_to_user" value="1"
                                        {{ ($settings['charge_user_to_user'] ?? 1) == 1 ? 'checked' : '' }}
                                        onchange="updateStatus(this)">
                                    <span class="slider round"></span>
                                </label>
                                <span class="switch-status {{ ($settings['charge_user_to_user'] ?? 1) == 1 ? 'active' : 'inactive' }}">
                                    {{ ($settings['charge_user_to_user'] ?? 1) == 1 ? __('Active') : __('Inactive') }}
                                </span>
                            </div>
                            <small class="text-muted d-block">{{ __('When enabled, users can charge other users') }}</small>
                        </div>
                    </div>
                </div>

                <!-- User to Charging Agent Transfer -->
                <div class="charge-transfer-card">
                    <div class="charge-transfer-header">
                        <div class="charge-transfer-icon user-to-agent">
                            <i class="fas fa-user"></i>
                            <i class="fas fa-arrow-right arrow-icon"></i>
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h5>{{ __('User to Charging Agent Transfer') }}</h5>
                    </div>
                    <div class="charge-transfer-body">
                        <div class="form-group switch-group">
                            <label class="switch-label">{{ __('Allow Charging') }}</label>
                            <div class="switch-wrapper">
                                <label class="switch">
                                    <input type="hidden" name="charge_user_to_agent" value="0">
                                    <input type="checkbox" name="charge_user_to_agent" value="1"
                                        {{ ($settings['charge_user_to_agent'] ?? 1) == 1 ? 'checked' : '' }}
                                        onchange="updateStatus(this)">
                                    <span class="slider round"></span>
                                </label>
                                <span class="switch-status {{ ($settings['charge_user_to_agent'] ?? 1) == 1 ? 'active' : 'inactive' }}">
                                    {{ ($settings['charge_user_to_agent'] ?? 1) == 1 ? __('Active') : __('Inactive') }}
                                </span>
                            </div>
                            <small class="text-muted d-block">{{ __('When enabled, users can charge charging agents') }}</small>
                        </div>
                    </div>
                </div>

                <!-- User to Self Transfer -->
                <div class="charge-transfer-card">
                    <div class="charge-transfer-header">
                        <div class="charge-transfer-icon user-to-self">
                            <i class="fas fa-user"></i>
                            <i class="fas fa-arrow-right arrow-icon"></i>
                            <i class="fas fa-user"></i>
                        </div>
                        <h5>{{ __('User to Self Transfer') }}</h5>
                    </div>
                    <div class="charge-transfer-body">
                        <div class="form-group switch-group">
                            <label class="switch-label">{{ __('Allow Charging') }}</label>
                            <div class="switch-wrapper">
                                <label class="switch">
                                    <input type="hidden" name="charge_user_to_self" value="0">
                                    <input type="checkbox" name="charge_user_to_self" value="1"
                                        {{ ($settings['charge_user_to_self'] ?? 1) == 1 ? 'checked' : '' }}
                                        onchange="updateStatus(this)">
                                    <span class="slider round"></span>
                                </label>
                                <span class="switch-status {{ ($settings['charge_user_to_self'] ?? 1) == 1 ? 'active' : 'inactive' }}">
                                    {{ ($settings['charge_user_to_self'] ?? 1) == 1 ? __('Active') : __('Inactive') }}
                                </span>
                            </div>
                            <small class="text-muted d-block">{{ __('When enabled, users can charge themselves') }}</small>
                        </div>
                    </div>
                </div>

                <!-- Charging Agent to User Transfer -->
                <div class="charge-transfer-card">
                    <div class="charge-transfer-header">
                        <div class="charge-transfer-icon agent-to-user">
                            <i class="fas fa-bolt"></i>
                            <i class="fas fa-arrow-right arrow-icon"></i>
                            <i class="fas fa-user"></i>
                        </div>
                        <h5>{{ __('Charging Agent to User Transfer') }}</h5>
                    </div>
                    <div class="charge-transfer-body">
                        <div class="form-group switch-group">
                            <label class="switch-label">{{ __('Allow Charging') }}</label>
                            <div class="switch-wrapper">
                                <label class="switch">
                                    <input type="hidden" name="charge_agent_to_user" value="0">
                                    <input type="checkbox" name="charge_agent_to_user" value="1"
                                        {{ ($settings['charge_agent_to_user'] ?? 1) == 1 ? 'checked' : '' }}
                                        onchange="updateStatus(this)">
                                    <span class="slider round"></span>
                                </label>
                                <span class="switch-status {{ ($settings['charge_agent_to_user'] ?? 1) == 1 ? 'active' : 'inactive' }}">
                                    {{ ($settings['charge_agent_to_user'] ?? 1) == 1 ? __('Active') : __('Inactive') }}
                                </span>
                            </div>
                            <small class="text-muted d-block">{{ __('When enabled, charging agents can charge users') }}</small>
                        </div>
                    </div>
                </div>

                <!-- Charging Agent to Charging Agent Transfer -->
                <div class="charge-transfer-card">
                    <div class="charge-transfer-header">
                        <div class="charge-transfer-icon agent-to-agent">
                            <i class="fas fa-bolt"></i>
                            <i class="fas fa-arrow-right arrow-icon"></i>
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h5>{{ __('Charging Agent to Charging Agent Transfer') }}</h5>
                    </div>
                    <div class="charge-transfer-body">
                        <div class="form-group switch-group">
                            <label class="switch-label">{{ __('Allow Charging') }}</label>
                            <div class="switch-wrapper">
                                <label class="switch">
                                    <input type="hidden" name="charge_agent_to_agent" value="0">
                                    <input type="checkbox" name="charge_agent_to_agent" value="1"
                                        {{ ($settings['charge_agent_to_agent'] ?? 1) == 1 ? 'checked' : '' }}
                                        onchange="updateStatus(this)">
                                    <span class="slider round"></span>
                                </label>
                                <span class="switch-status {{ ($settings['charge_agent_to_agent'] ?? 1) == 1 ? 'active' : 'inactive' }}">
                                    {{ ($settings['charge_agent_to_agent'] ?? 1) == 1 ? __('Active') : __('Inactive') }}
                                </span>
                            </div>
                            <small class="text-muted d-block">{{ __('When enabled, charging agents can charge other agents') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions mt-4">
                <button type="submit" class="btn btn-primary btn-save">
                    <i class="fas fa-save"></i> {{ __('Save Settings') }}
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.charge-transfer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 24px;
    margin-top: 24px;
    padding: 0 10px;
}

.charge-transfer-card {
    background: linear-gradient(145deg, var(--gray-800) 0%, var(--gray-700) 100%);
    border-radius: var(--border-radius);
    padding: 24px;
    border: 1px solid var(--gray-600);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.charge-transfer-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-color) 0%, var(--green-color) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.charge-transfer-card:hover {
    border-color: var(--primary-color);
    box-shadow: 0 8px 24px rgba(37, 99, 235, 0.15);
    transform: translateY(-4px);
}

.charge-transfer-card:hover::before {
    opacity: 1;
}

.charge-transfer-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}

.charge-transfer-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 14px;
    border-radius: var(--border-radius);
    background: var(--gray-700);
    min-width: 60px;
    height: 60px;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
}

.charge-transfer-icon i {
    font-size: 20px;
}

.charge-transfer-icon .arrow-icon {
    font-size: 12px;
    color: var(--gray-400);
}

.charge-transfer-icon .arrow-icon {
    font-size: 12px;
    color: var(--gray-300);
}

.charge-transfer-icon.user-to-user i:first-child,
.charge-transfer-icon.user-to-user i:last-child {
    color: var(--primary-color);
}

.charge-transfer-icon.user-to-agent i:first-child {
    color: var(--primary-color);
}

.charge-transfer-icon.user-to-agent i:last-child {
    color: var(--green-color);
}

.charge-transfer-icon.user-to-self i {
    color: var(--primary-color);
}

.charge-transfer-icon.agent-to-user i:first-child {
    color: var(--green-color);
}

.charge-transfer-icon.agent-to-user i:last-child {
    color: var(--primary-color);
}

.charge-transfer-icon.agent-to-agent i {
    color: var(--green-color);
}

.charge-transfer-header h5 {
    margin: 0;
    color: var(--white);
    font-size: 14px;
    font-weight: 600;
}

.charge-transfer-body {
    padding-top: 8px;
}

.switch-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.switch-label {
    color: var(--white);
    font-weight: 600;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.switch-label::before {
    content: '\f0e7';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    color: var(--primary-color);
    font-size: 12px;
}

.switch-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 8px 0;
}

.switch-status {
    font-size: 13px;
    font-weight: 500;
    padding: 4px 12px;
    border-radius: 20px;
    transition: all 0.3s ease;
}

.switch-status.active {
    background: rgba(16, 185, 129, 0.2);
    color: var(--green-color);
}

.switch-status.inactive {
    background: rgba(107, 114, 128, 0.2);
    color: var(--gray-400);
}

.switch {
    position: relative;
    display: inline-block;
    width: 56px;
    height: 30px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--gray-300);
    transition: .4s;
}

.slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 4px;
    bottom: 4px;
    background-color: var(--white);
    transition: .4s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

input:checked + .slider {
    background-color: var(--primary-color);
}

input:checked + .slider:before {
    transform: translateX(26px);
}

.charge-transfer-card small {
    color: var(--text-secondary-color);
    font-size: 13px;
    line-height: 1.5;
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-top: 8px;
}

.charge-transfer-card small::before {
    content: '\f05a';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    color: var(--primary-color);
    font-size: 12px;
    margin-top: 2px;
}

.slider.round {
    border-radius: 34px;
}

.slider.round:before {
    border-radius: 50%;
}

.form-actions {
    text-align: center;
}

.btn-save {
    background: var(--primary-button);
    border: none;
    padding: 12px 30px;
    font-weight: bold;
    margin: 16px;
    border-radius: var(--border-radius);
    color: var(--white);
    transition: var(--transition);
}

.btn-save:hover {
    background: var(--primary-color);
    box-shadow: var(--shadow-md);
}

.section-header h4 {
    color: var(--primary-color);
    margin-bottom: 5px;
    font-weight: 600;
}

.section-header p {
    color: var(--text-secondary-color);
}

@media (max-width: 768px) {
    .charge-transfer-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .charge-transfer-card {
        padding: 20px;
    }

    .charge-transfer-icon {
        min-width: 50px;
        height: 50px;
        padding: 10px;
    }

    .charge-transfer-icon i {
        font-size: 16px;
    }

    .switch-wrapper {
        flex-wrap: wrap;
    }
}
</style>

<script>
function updateStatus(checkbox) {
    const statusSpan = checkbox.closest('.switch-wrapper').querySelector('.switch-status');
    if (checkbox.checked) {
        statusSpan.classList.remove('inactive');
        statusSpan.classList.add('active');
        statusSpan.textContent = '{{ __("Active") }}';
    } else {
        statusSpan.classList.remove('active');
        statusSpan.classList.add('inactive');
        statusSpan.textContent = '{{ __("Inactive") }}';
    }
}
</script>
