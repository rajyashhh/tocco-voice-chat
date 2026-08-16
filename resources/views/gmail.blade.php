<!DOCTYPE html>

<!--
Author: Keenthemes
Product Name: Metronic - Bootstrap 5 HTML, VueJS, React, Angular. Laravel, Asp.Net Core, Ruby on Rails, Spring Boot, Blazor, Django, Express Node.js & Flask Admin Dashboard Theme
Purchase: https://1.envato.market/EA4JP
Website: http://www.keenthemes.com
Contact: support@keenthemes.com
Follow: www.twitter.com/keenthemes
Dribbble: www.dribbble.com/keenthemes
Like: www.facebook.com/keenthemes
License: For each use you must have a valid license purchased only from above link in order to legally use the theme for your project.
-->
<html lang="en">
	<!--begin::Head-->
	<head><base href="../../"/>
		<meta charset="utf-8" />
		<meta name="description" content="The most advanced Bootstrap Admin Theme on Themeforest trusted by 100,000 beginners and professionals. Multi-demo, Dark Mode, RTL support and complete React, Angular, Vue, Asp.Net Core, Rails, Spring, Blazor, Django, Flask & Laravel versions. Grab your copy now and get life-time updates for free." />
		<meta name="keywords" content="metronic, bootstrap, bootstrap 5, angular, VueJs, React, Asp.Net Core, Rails, Spring, Blazor, Django, Flask & Laravel starter kits, admin themes, web design, figma, web development, free templates, free admin themes, bootstrap theme, bootstrap template, bootstrap dashboard, bootstrap dak mode, bootstrap button, bootstrap datepicker, bootstrap timepicker, fullcalendar, datatables, flaticon" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta property="og:locale" content="en_US" />
		<meta property="og:type" content="article" />
		<meta property="og:title" content="Metronic - Bootstrap 5 HTML, VueJS, React, Angular. Laravel, Asp.Net Core, Ruby on Rails, Spring Boot, Blazor, Django, Express Node.js & Flask Admin Dashboard Theme" />
		<meta property="og:url" content="https://keenthemes.com/metronic" />
		<meta property="og:site_name" content="Keenthemes | Metronic" />
		<link rel="canonical" href="https://preview.keenthemes.com/metronic8" />
		<link rel="shortcut icon" href="assets/media/logos/favicon.ico" />
		<!--begin::Fonts(mandatory for all pages)-->
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
		<!--end::Fonts-->
		<!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
		<link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
		<!--end::Global Stylesheets Bundle-->
        
	</head>
	<!--end::Head-->
	<!--begin::Body-->
	<body id="kt_body" class="app-blank">
		<!--begin::Theme mode setup on page load-->
		<script>var defaultThemeMode = "light"; var themeMode; if ( document.documentElement ) { if ( document.documentElement.hasAttribute("data-theme-mode")) { themeMode = document.documentElement.getAttribute("data-theme-mode"); } else { if ( localStorage.getItem("data-theme") !== null ) { themeMode = localStorage.getItem("data-theme"); } else { themeMode = defaultThemeMode; } } if (themeMode === "system") { themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"; } document.documentElement.setAttribute("data-theme", themeMode); }</script>
		<!--end::Theme mode setup on page load-->
		<!--begin::Main-->
		<!--begin::Root-->
		<div class="d-flex flex-column flex-root">
			<!--begin::Wrapper-->
			<div class="d-flex flex-column flex-column-fluid">
				<!--begin::Header-->
					
				<!--end::Header-->
				<!--begin::Body-->
				<div class="scroll-y flex-column-fluid px-10 py-10" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_app_header_nav" data-kt-scroll-offset="5px" data-kt-scroll-save-state="true" style="background-color:#D5D9E2; --kt-scrollbar-color: #d9d0cc; --kt-scrollbar-hover-color: #d9d0cc">
					<!--begin::Email template-->
					<div id="#kt_app_body_content" style="background-color:#D5D9E2; font-family:Arial,Helvetica,sans-serif; line-height: 1.5; min-height: 100%; font-weight: normal; font-size: 15px; color: #2F3044; margin:0; padding:0; width:100%;">
						<div style="background-color:#ffffff; padding: 45px 0 34px 0; border-radius: 24px; margin:40px auto; max-width: 600px;">
							<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto" style="border-collapse:collapse">
								<tbody>
									<tr>
										<td align="center" valign="center" style="text-align:center; padding-bottom: 10px">
											<!--begin:Email content-->
											<div style="margin-bottom:55px; text-align:left">
												<!--begin:Logo-->
												<!--end:Logo-->
												<!--begin:Text-->
												<div style="font-size: 14px; text-align:center; font-weight: 500; margin:0 60px 36px 60px; font-family:Arial,Helvetica,sans-serif">
													<p style="color:#181C32; font-size: 30px; font-weight:700; line-height:1.4; margin-bottom:6px">طلب انشاء وكاله </p>
						
												</div>
												<!--end:Text-->
												<!--begin:Items-->
												<div style="display: flex; justify-content: center; flex-wrap: wrap; margin: 0 40px 42px 40px">
                                                    <!--begin:Item-->
                                                    <div style="width: 220px; margin: 18px 20px; flex: 0 0 220px;">
                                                        <!--begin:Media-->
                                                        <div style="display: flex; flex-direction: column; align-items: center;">
                                                            <p style="text-align: center; margin-bottom: 9px;">صورة البطاقة الأمامية</p>
                                                            <img alt="" style="width: 100%; height: 220px; object-fit: cover; border-radius: 12px; margin-bottom: 9px;" src="{{ Storage::url($agency->additionalInfo->face_image_nationalId)}}" />
                                                        </div>

                                                    </div>
                                                    <!--end:Item-->
                                                    <!--begin:Item-->
                                                    <div style="width: 220px; margin: 18px 20px; flex: 0 0 220px;">
                                                        <!--begin:Media-->
                                                        <div style="display: flex; flex-direction: column; align-items: center;">
                                                            <p style="text-align: center; margin-bottom: 9px;">صورة البطاقة الخلفية</p>
                                                            <img alt="" style="width: 100%; height: 220px; object-fit: cover; border-radius: 12px; margin-bottom: 9px;" src="{{ Storage::url($agency->additionalInfo->back_image_nationalId)}}" />
                                                        </div>
                                                    </div>
                                                </div>
												
										</td>
									</tr>
				
								</tbody>
							</table>

                                        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto" style="border-collapse:collapse">
                                            <thead>
                                                <tr>
                                                    <th> الاسم</th>
                                                    <th> رقم الهاتف</th>
                                                    <th>البلد</th>
                                                    <th> بريد الالكترونى</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td align="center" valign="center" style="text-align:center; padding-bottom: 20px">{{$agency->name }}</td>
                                                    <td align="center" valign="center" style="text-align:center; padding-bottom: 20px">{{$agency->phone}}</td>
                                                    <td align="center" valign="center" style="text-align:center; padding-bottom: 20px">{{$agency->additionalInfo->country}}</td>
                                                    <td align="center" valign="center" style="text-align:center; padding-bottom: 20px">{{$agency->additionalInfo->gmail}}</td>
                                                </tr>
                                            
                                            </tbody>
                                        </table>
                                    <br>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto" style="border-collapse:collapse">
                                            <thead>
                                                <tr>
                                                    <th>الراتب</th>
                                                    <th>المضيف</th>  
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td align="center" valign="center" style="text-align:center; padding-bottom: 10px">{{$agency->additionalInfo->first()->salary}}</td>
                                                    <td align="center" valign="center" style="text-align:center; padding-bottom: 10px">{{$agency->additionalInfo->first()->host}}</td>
                                                   
                                                </tr>
                                            
                                            </tbody>
                                        </table>

                                        <br>
                                        <a href="{{ url('admin/request-agencies?id=' . $agency->id ) }}" target="_blank" style="background-color:#50cd89; border-radius:6px; display:inline-block; margin-left:60px; padding:11px 19px; color: #FFFFFF; font-size: 14px; font-weight:500; font-family:Arial,Helvetica,sans-serif">الوكاله</a>
						</div>
					</div>
					<!--end::Email template-->
				</div>
				<!--end::Body-->
			</div>
			<!--end::Wrapper-->
		</div>
		<!--end::Root-->
		<!--end::Main-->
		<!--begin::Javascript-->
		<script>var hostUrl = "assets/";</script>
		<!--begin::Global Javascript Bundle(mandatory for all pages)-->
		<script src="assets/plugins/global/plugins.bundle.js"></script>
		<script src="assets/js/scripts.bundle.js"></script>
		<!--end::Global Javascript Bundle-->
		<!--end::Javascript-->
	</body>
	<!--end::Body-->
</html>