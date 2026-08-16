<div class="row" style="margin: 0;">
    <div class="col-md-8 col-md-offset-2">
        <form method="POST" action="{{ admin_url('notification-settings/save') }}">
            @csrf

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-fire"></i> {{ __('Push notifications') }} (Firebase FCM)</h3>
                </div>
                <div class="box-body">

                    <div class="form-group">
                        <label>{{ __('Project Name') }}</label>
                        <input type="text" name="firebase_project_name" class="form-control"
                               value="{{ $values['firebase_project_name'] }}"
                               placeholder="your-firebase-project-id">
                        <span class="help-block">{{ __('Firebase project id (from the service account / Firebase console).') }}</span>
                    </div>

                    <div class="form-group">
                        <label>
                            {{ __('Service Account JSON') }}
                            @if($secretsConfigured['firebase_service_account_json'])
                                <span class="label label-success">{{ __('Configured') }}</span>
                            @endif
                        </label>
                        <textarea name="firebase_service_account_json" class="form-control" rows="6"
                                  style="font-family:monospace;"
                                  placeholder='{ "type": "service_account", "project_id": "...", "private_key": "...", ... }'></textarea>
                        <span class="help-block">{{ __('Paste the full Firebase Admin SDK service account JSON, then Save. Stored in the database — no server upload needed. Leave blank to keep the current value.') }}</span>
                    </div>

                    <div class="form-group">
                        <label>{{ __('Firebase Realtime Database URI') }}</label>
                        <input type="text" name="firebase_database_uri" class="form-control"
                               value="{{ $values['firebase_database_uri'] }}"
                               placeholder="https://your-project-default-rtdb.firebaseio.com">
                    </div>

                    <div class="form-group">
                        <label>{{ __('FCM Sender ID') }}</label>
                        <input type="text" name="fcm_sender_id" class="form-control"
                               value="{{ $values['fcm_sender_id'] }}"
                               placeholder="123456789012">
                        <span class="help-block">{{ __('FCM sender / project id (legacy group messaging)') }}</span>
                    </div>

                    <div class="form-group">
                        <label>{{ __('Default Notification Image') }}</label>
                        <input type="text" name="notification_default_image" class="form-control"
                               value="{{ $values['notification_default_image'] }}"
                               placeholder="https://...">
                        <span class="help-block">{{ __('Default push notification image (falls back to app logo)') }}</span>
                    </div>

                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>