<div class="box grid-box" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <div class="card mb-5 mb-xl-10 p-4 shadow-lg">
        <div class="row align-items-center">
            @if(app()->getLocale() === 'ar')
                <div class="col-md-10">
                    <h4 class="fw-bold mb-2 d-flex align-items-center text-warning">
                        {{ $user->name ?? '' }}
                        <i class="ki-duotone ki-verify fs-5 text-primary me-2"></i>
                    </h4>

                    <div class="row text-muted fw-semibold gy-2">
                        <div class="col-md-4 d-flex align-items-center">
                            <span>{{ $user->uuid == $user->original_uuid ? $user->uuid : $user->uuid . ' - ' . $user->original_uuid }}</span>
                            <img src="{{ asset('images/uuid.jpg') }}" alt="UUID" width="20" class="ms-2">
                        </div>

                        <div class="col-md-4 d-flex align-items-center">
                            <a href="mailto:{{ $user->email ?? '' }}" class="text-decoration-none text-muted">
                                {{ $user->email ?? '' }}
                            </a>
                            <img src="{{ asset('images/email.jpg') }}" alt="Email" width="20" class="ms-2">
                        </div>

                        <div class="col-md-4 d-flex align-items-center">
                            <span>{{ $user->phone ?? '-' }}</span>
                            <img src="{{ asset('images/phone.jpg') }}" alt="Phone" width="20" class="ms-2">
                        </div>
                    </div>
                </div>
                <div class="col-md-2 text-center">
                    @else
                        <div class="col-md-2 text-center">
                            @endif
                            <div class="position-relative d-inline-block">
                                @php
                                    $defaultImage = asset("images/businessman-icon.jpg");
                                    $avatarPath = @$user->avatar;
                                    $avatar = getImagePath($avatarPath) ?? $defaultImage;

                                    if (!isImageExists($avatar)) {
                                        $avatar = $defaultImage;
                                    }
                                @endphp
                                <div class="mb-3">
                                    <img src="{{ $avatar }}" alt="Profile Picture"
                                         class="border shadow" width="100" height="100"
                                         style="border-radius: 20px; object-fit: cover;">
                                </div>
                                <span class="position-absolute bottom-0 {{ app()->getLocale() === 'ar' ? 'start-0' : 'end-0' }} bg-success border border-light rounded-circle"
                                      style="width: 16px; height: 16px;"></span>
                            </div>
                        </div>
                        @if(app()->getLocale() !== 'ar')
                            <div class="col-md-10">
                                <h4 class="fw-bold mb-2 d-flex align-items-center text-warning">
                                    {{ $user->name ?? '' }}
                                    <i class="ki-duotone ki-verify fs-5 text-primary ms-2"></i>
                                </h4>

                                <div class="row text-muted fw-semibold gy-2">
                                    <div class="col-md-4 d-flex align-items-center">
                                        <img src="{{ asset('images/uuid.jpg') }}" alt="UUID" width="20" class="me-2">
                                        <span>{{ $user->uuid == $user->original_uuid ? $user->uuid : $user->uuid . ' - ' . $user->original_uuid }}</span>
                                    </div>

                                    <div class="col-md-4 d-flex align-items-center">
                                        <img src="{{ asset('images/email.jpg') }}" alt="Email" width="20" class="me-2">
                                        <a href="mailto:{{ $user->email ?? '' }}" class="text-decoration-none text-muted">
                                            {{ $user->email ?? '' }}
                                        </a>
                                    </div>

                                    <div class="col-md-4 d-flex align-items-center">
                                        <img src="{{ asset('images/phone.jpg') }}" alt="Phone" width="20" class="me-2">
                                        <span>{{ $user->phone ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                </div>
        </div>
    </div>
