<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
    <!-- Use the asset helper to load the CSS file from the public/css folder -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
  </head>
  <body>
    <div class="about-section">
      <h1>Account deletion policy for: {{config('app.name_en')}}</h1>
      <p>
        We at {{config('app.name_en')}} are keen to keep all our data private and highly secure
        for the comfort of our users
      </p>
    </div>
    <p class="container mid-text">
      Warning: Deleting your account means all data, activities, and history
      will be permanently deleted and cannot be recovered after deletion, so
      please be careful.
    </p>
    <p class="container delete">For delete you acccount follow the below steps</p>
    <div class="container">
        @foreach ($data as $key =>$item)
            <div class="section-step d-flex align-items-center mb-4">
                <div class="w-25">
                    <!-- Check if the image exists -->
                    <img src="{{ getImagePath( $item->image) }}" class="img-fluid">
                </div>
                <div class="w-75">
                    <p class="section-text">{{ $key +1}}- {{ $item->title }}</p>
                </div>
            </div>
        @endforeach
    </div>
  </body>
</html>