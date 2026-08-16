<div class="box-body no-padding">
    <div class="nav-scroll-container">
    </div>

    <div class="box-body no-padding">
        <div class="nav-scroll-container">
        </div>
        <div class="col">

            @php
                $name = $areaManager->username ?? '';
                $id = $areaManager->id ?? 0;
                $path = $areaManager->avatar ?? null;
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }
                $showUrl = url('admin/users/' . $areaManager->id);
                  $image = "<img src='" . e($url) . "' style='width: 80px; height: 70px; object-fit: cover; border-radius: 0;'>";
            @endphp
            <div class="col" style="display: flex; justify-content: center;">
                <a href="{{$showUrl}}"
                   style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 10px; text-align: center;">
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 10px;">
                        {!! $image !!}
                        <div>
                            <strong>{{ $name }}</strong><br>
                            <span style="color: #aaa; font-size: smaller;">UID: {{ $id }}</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>


</div>
</div>
</div>

<style>
    .nav-pills > li.active > a, .nav-pills > li.active > a:focus, .nav-pills > li.active > a:hover {
        background-color: var(--primary-color);
    }

    .nav-pills > li.active > a, .nav-pills > li.active > a:hover, .nav-pills > li.active > a:focus {
        border-top-color: var(--primary-color);
    }

    .nav-scroll-container {
        overflow-x: auto; /* Allow horizontal scrolling */
        white-space: nowrap; /* Prevent wrapping to the next line */
        -webkit-overflow-scrolling: touch; /* Enable smooth scrolling on iOS */
    }

    .nav-pills {
        display: inline-flex; /* Display nav items in a single line */
        padding: 10px 0; /* Adjust padding as needed */
    }

    .nav-pills li {
        display: inline-block; /* Ensure list items display inline */
    }

    .details-section {
        padding: 20px;
    }

    .details-title {
        margin-bottom: 20px;
        font-weight: 500;
    }

</style>
