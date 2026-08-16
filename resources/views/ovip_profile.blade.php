<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        canvas {
            background-color: transparent !important;
            transform: none !important;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #121212;
            color: white;
            display: flex;
            flex-direction: column;
        }
        .settings-sidebar {
            width: 250px;
            background: #222;
            min-height: 100vh;
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.5);
        }
        .settings-sidebar h2 {
            text-align: center;
            color: #ff9800;
        }
        .main-content {
            display: flex;
            flex-direction: column;
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }
        .container {
            /* background: #222; */
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            height: 80%;
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        .agency-container, .charge-container {
            background: #222;
            padding: 20px;
            border-radius: 5px;
            width: 200%;
            max-width: 1300px;
            margin: 0 auto 20px;
            text-align: center;
        }
        .avatar  {
            display: flex;
            align-items: center;
            justify-content: center;

        }
        .avatar img {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            border: 3px solid var(--primary-color);

            margin-bottom: 15px;
        }

        .details {
            text-align: left;
            margin-top: 10px;
        }
        .details p {
            margin: 5px 0;
            font-size: 16px;
        }
        .details strong {
            color: #ff9800;
        }

        button {
            padding: 10px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
            margin-top: 15px;
        }

        /* Table styles */
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: center;
            border-bottom: 1px solid #444;
        }
        th {
            background-color: #333;
        }
        .imageContainer{
            border: 2px solid;
            border-radius: 50%;
            width: 50%;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            .settings-sidebar {
                width: 100%;
                min-height: auto;
            }
            .container, .agency-container, .charge-container {
                padding: 15px;
            }
            .avatar img {
                width: 800px;
                height: 800px;
            }
            table {
                font-size: 14px;
            }
            th, td {
                padding: 6px 4px;
            }
            .imageContainer{
            border: 2px solid;
            border-radius: 50%;
            width: auto;
             }
        }

        @media (max-width: 480px) {
            .details p {
                font-size: 14px;
            }
            table {
                font-size: 12px;
            }
            th, td {
                padding: 4px 2px;
            }

            .imageContainer{
            border: 2px solid;
            border-radius: 50%;
            width: auto;
             }
        }

        /* Main Container */
/* .agency-container {
    width: 100%;
    padding: 20px;
} */

/* Card Styling */
.card {
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(187, 109, 7, 0.1);
    padding: 25px;
    max-width: 1200px;
    margin: 0 auto;
}

.card-title {
    text-align: center;
    margin-bottom: 25px;
    font-size: 3rem;
    color: #da7116;
}

.privileges-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px; /* Increased gap between items */
    justify-items: center;
}

.privilege-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    gap: 15px; /* This creates consistent space between image and name */
}

.image-container {
    width: 150px; /* Fixed size for circular container */
    height: 150px;
    position: relative;
    overflow: hidden;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
}

.privilege-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border: 3px solid #ff9800;
    transition: transform 0.3s ease;
}

.privilege-item:hover .privilege-img {
    transform: scale(1.05);
}

.privilege-name {
    text-align: center;
    font-size: 1.5rem;
    font-weight: 500;
    color: #c87121;
    width: 100%;
    margin-top: 10px; /* Additional spacing control */
    padding: 0 10px; /* Prevents text from touching edges */
}

/* Responsive Adjustments */
@media (max-width: 992px) {
    .privileges-grid {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }
    .image-container {
        width: 130px;
        height: 130px;
    }
}

@media (max-width: 768px) {
    .privileges-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 25px;
    }
    .image-container {
        width: 120px;
        height: 120px;
    }
    .privilege-name {
        font-size: 1.3rem;
    }
}

@media (max-width: 576px) {
    .privileges-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .card {
        padding: 15px;
    }
    .image-container {
        width: 100px;
        height: 100px;
    }
    .privilege-name {
        font-size: 1.1rem;
    }
}
    </style>
</head>

<body>
    <div class="main-content">
        <div class="container">
            <div class="avatar text-center">
                <div class="imageContainer" id="imageContainer{{ $oVip->id }}"></div>
            </div>

            {{-- <button onclick="window.history.back()">{{ __("Go Back") }}</button> --}}
        </div>
     <br>

     <div class="agency-container">
        <div class="card">
            <h4 class="card-title text-center">{{ __('ovip Privileges') }}</h4>
            <div class="privileges-grid">
                @foreach($oVip->privilegs as $privilege)
                    <div class="privilege-item">
                        <div class="image-container">
                            <img src="{{ getImagePath($privilege->img1) }}"
                            class="privilege-img"
                            data-level="{{ $oVip->level }}"
                            data-type="{{ $privilege->type }}"
                            onclick="fetchGiftOvip(this)"  class="privilege-img">
                        </div>
                        <div class="privilege-name text-center">{{ $privilege->name }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Modal -->
    <!-- Modal HTML -->
<div class="modal fade" id="giftModal" tabindex="-1" role="dialog" aria-labelledby="giftModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="giftModalTitle">{{ __('gifts') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div id="giftImageContainer"></div>
                <h4 id="giftTitle" class="mb-3"></h4>
            </div>
        </div>
    </div>
</div>

          <!-- <script src="https://cdn.jsdelivr.net/npm/svgaplayerweb@2.3.1/build/svga.min.js"></script> -->
          <!-- Load jQuery first -->

 <script>



    function getFileExtension(url) {
        try {
            const pathname = new URL(url).pathname;
            return pathname.split('.').pop().toLowerCase();
        } catch (e) {
            return url.split('.').pop().toLowerCase();
        }
    }

    function showImageAndMaybeInitSVGA(imageUrl, containerId, canvasId,width,height) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const extension = getFileExtension(imageUrl);
        const isSvga = extension === 'svga';
        let content = '';

        if (isSvga) {
            content = `<canvas id="${canvasId}" width="${width}" height="${height}" style="margin: 0 auto;"></canvas>`;
        } else if (extension === 'mp4') {
            content = `<video width="${width}" controls style="margin: 0 auto;">
                            <source src="${imageUrl}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>`;
        } else {
            content = `<img src="${imageUrl}" alt="" style="width: ${width}px; height: ${height}px; object-fit: cover; margin: 0 auto; border-radius: 50%; border: 3px solid #ff9800;"/>`;
        }

        container.innerHTML = content;

        if (isSvga) {
            function initializeSvgaPlayer() {
                const checkCanvasExist = setInterval(() => {
                    const canvas = document.getElementById(canvasId);
                    if (canvas) {
                        clearInterval(checkCanvasExist);
                        try {
                            const player = new SVGA.Player('#' + canvasId);
                            const parser = new SVGA.Parser('#' + canvasId);
                            player.loops = 100;
                            player.clearsAfterStop = false;

                            parser.load(imageUrl, function(videoItem) {
                                player.setVideoItem(videoItem);
                                player.startAnimation();
                            });
                        } catch (error) {
                            console.error('SVGA Player Error:', error);
                            container.innerHTML = `<img src="${imageUrl.replace('.svga', '.png')}" alt="" style="width: 350px; height: 350px; object-fit: cover; margin: 0 auto; border-radius: 50%; border: 3px solid #ff9800;"/>`;
                        }
                    }
                }, 100);
            }

            if (typeof SVGA === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/svgaplayerweb@2.3.1/build/svga.min.js';
                script.onload = function () {
                    console.log('✅ SVGA Loaded!');
                    initializeSvgaPlayer();
                };
                document.head.appendChild(script);
            } else {
                initializeSvgaPlayer();
            }
        }
    }

    function renderAnimation() {
        const imageUrl = @json(getImagePath($oVip->img));
        const containerId = 'imageContainer{{ $oVip->id }}';
        const canvasId = 'svgaCanvas{{ $oVip->id }}';

        // تأكد من توفر العنصر قبل المتابعة
        const waitForContainer = setInterval(() => {
            if (document.getElementById(containerId)) {
                clearInterval(waitForContainer);
                showImageAndMaybeInitSVGA(imageUrl, containerId, canvasId,350,350);
            }
        }, 100);
    }

    document.addEventListener('DOMContentLoaded', function () {
        renderAnimation();
    });

    $(document).on('pjax:complete', function () {
        renderAnimation();
    });





    const script1 = document.createElement('script');
    const script2 = document.createElement('script');

    script1.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
    script2.src = 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js';

    // Append jQuery script first, and then Bootstrap script
    script1.onload = function () {
        console.log('✅ jQuery Loaded!');
        // Now Bootstrap can be safely loaded after jQuery
        document.body.appendChild(script2);
    };

    script2.onload = function () {
        console.log('✅ Bootstrap Loaded!');
        // Initialize your SVGA player or any functionality that depends on Bootstrap
        initializeSvgaPlayer(); // Ensure SVGA is loaded after Bootstrap
    };

    // Add the first script to the document
    document.body.appendChild(script1);

    // Function to fetch and display the gift (image or animation)
    function fetchGiftOvip(imgElement) {
        const level = imgElement.getAttribute('data-level');
        const type = imgElement.getAttribute('data-type');

        fetch(`/admin/gift-ovip?level=${level}&type=${type}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                if (data && data.image_url) {
                    // Set the title if available
                    if (data.title) {
                        document.getElementById('giftTitle').textContent = data.title;
                    }

                    // Show the image or animation
                    showImageAndMaybeInitSVGA(data.image_url, 'giftImageContainer', 'giftSvgaCanvas', 200, 200);

                    // Show the modal using Bootstrap
                    const modal = new bootstrap.Modal(document.getElementById('giftModal'));
                    $('#giftModal').modal('show');
                } else {
                    throw new Error("No image URL in response");
                }
            })
            .catch(error => {
                console.error("Error fetching image:", error);
                alert(`Failed to load gift: ${error.message}`);
            });
    }

    // Initialize modal cleanup when the modal is hidden
    document.addEventListener('DOMContentLoaded', function () {
        var modalEl = document.getElementById('giftModal');
        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', function () {
                // Clear the container and title when modal is closed
                const container = document.getElementById('giftImageContainer');
                if (container) {
                    container.innerHTML = '';
                }
                document.getElementById('giftTitle').textContent = '';
            });
        }
    });

    // Ensure the above logic works with PJAX (if you're using PJAX in Laravel Admin)
    $(document).on('pjax:complete', function () {
        // Reinitialize modals after page change or PJAX refresh
        var modalEl = document.getElementById('giftModal');
        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', function () {
                const container = document.getElementById('giftImageContainer');
                if (container) {
                    container.innerHTML = '';
                }
                document.getElementById('giftTitle').textContent = '';
            });
        }
    });



 </script>


    </div>
</body>
</html>
