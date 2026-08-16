<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminMemuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin_menu = array(
            array('id' => '1','parent_id' => '0','order' => '6','title' => 'Dashboard','icon' => 'fa-bar-chart','uri' => '/','permission' => NULL,'created_at' => NULL,'updated_at' => '2023-02-09 10:10:12'),
            array('id' => '2','parent_id' => '0','order' => '7','title' => 'Admin','icon' => 'fa-tasks','uri' => NULL,'permission' => NULL,'created_at' => NULL,'updated_at' => '2023-02-09 10:10:12'),
            array('id' => '3','parent_id' => '2','order' => '8','title' => 'Users','icon' => 'fa-users','uri' => 'auth/users','permission' => NULL,'created_at' => NULL,'updated_at' => '2023-02-09 10:10:12'),
            array('id' => '4','parent_id' => '2','order' => '9','title' => 'Roles','icon' => 'fa-user','uri' => 'auth/roles','permission' => NULL,'created_at' => NULL,'updated_at' => '2023-02-09 10:10:12'),
            array('id' => '5','parent_id' => '2','order' => '10','title' => 'Permission','icon' => 'fa-ban','uri' => 'auth/permissions','permission' => NULL,'created_at' => NULL,'updated_at' => '2023-02-09 10:10:12'),
            array('id' => '6','parent_id' => '2','order' => '11','title' => 'Menu','icon' => 'fa-bars','uri' => 'auth/menu','permission' => NULL,'created_at' => NULL,'updated_at' => '2023-02-09 10:10:12'),
            array('id' => '7','parent_id' => '2','order' => '12','title' => 'Operation log','icon' => 'fa-history','uri' => 'auth/logs','permission' => NULL,'created_at' => NULL,'updated_at' => '2023-02-09 10:10:12'),
            array('id' => '8','parent_id' => '0','order' => '41','title' => 'Helpers','icon' => 'fa-gears','uri' => NULL,'permission' => NULL,'created_at' => '2022-11-15 21:07:39','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '9','parent_id' => '8','order' => '42','title' => 'Scaffold','icon' => 'fa-keyboard-o','uri' => 'helpers/scaffold','permission' => NULL,'created_at' => '2022-11-15 21:07:39','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '10','parent_id' => '8','order' => '43','title' => 'Database terminal','icon' => 'fa-database','uri' => 'helpers/terminal/database','permission' => NULL,'created_at' => '2022-11-15 21:07:39','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '11','parent_id' => '8','order' => '44','title' => 'Laravel artisan','icon' => 'fa-terminal','uri' => 'helpers/terminal/artisan','permission' => NULL,'created_at' => '2022-11-15 21:07:39','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '12','parent_id' => '8','order' => '45','title' => 'Routes','icon' => 'fa-list-alt','uri' => 'helpers/routes','permission' => NULL,'created_at' => '2022-11-15 21:07:39','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '13','parent_id' => '0','order' => '13','title' => 'App','icon' => 'fa-android','uri' => '/','permission' => NULL,'created_at' => '2022-11-17 22:09:13','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '14','parent_id' => '13','order' => '40','title' => 'Users','icon' => 'fa-users','uri' => '/users','permission' => NULL,'created_at' => '2022-11-17 22:10:47','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '15','parent_id' => '0','order' => '5','title' => 'DevDash','icon' => 'fa-bars','uri' => '/dev','permission' => NULL,'created_at' => '2022-11-17 23:15:08','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '16','parent_id' => '13','order' => '33','title' => 'level list','icon' => 'fa-viacoin','uri' => 'vips','permission' => NULL,'created_at' => '2022-11-23 03:01:22','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '17','parent_id' => '13','order' => '34','title' => 'rooms','icon' => 'fa-wechat','uri' => 'rooms','permission' => NULL,'created_at' => '2022-11-23 04:37:36','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '18','parent_id' => '13','order' => '35','title' => 'Black List','icon' => 'fa-list-alt','uri' => 'blacks','permission' => NULL,'created_at' => '2022-11-23 05:00:07','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '19','parent_id' => '13','order' => '36','title' => 'Phone Codes','icon' => 'fa-qrcode','uri' => 'codes','permission' => NULL,'created_at' => '2022-11-23 05:09:55','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '20','parent_id' => '13','order' => '37','title' => 'Gifts','icon' => 'fa-gift','uri' => 'gifts','permission' => NULL,'created_at' => '2022-11-26 00:32:27','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '21','parent_id' => '13','order' => '38','title' => 'mall','icon' => 'fa-empire','uri' => '/','permission' => NULL,'created_at' => '2022-11-26 04:06:16','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '22','parent_id' => '21','order' => '39','title' => 'wares','icon' => 'fa-cart-arrow-down','uri' => 'wares','permission' => NULL,'created_at' => '2022-11-26 04:09:08','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '24','parent_id' => '0','order' => '47','title' => 'Configurations','icon' => 'fa-sliders','uri' => 'configs','permission' => NULL,'created_at' => '2022-11-26 23:56:05','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '25','parent_id' => '13','order' => '29','title' => 'Room Categories','icon' => 'fa-sitemap','uri' => 'categories','permission' => NULL,'created_at' => '2022-11-28 04:14:36','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '26','parent_id' => '13','order' => '30','title' => 'countries','icon' => 'fa-flag','uri' => 'countries','permission' => NULL,'created_at' => '2022-11-28 16:39:45','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '27','parent_id' => '13','order' => '31','title' => 'backgrounds','icon' => 'fa-file-image-o','uri' => 'backgrounds','permission' => NULL,'created_at' => '2022-12-14 14:55:43','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '28','parent_id' => '13','order' => '32','title' => 'Emojis','icon' => 'fa-smile-o','uri' => 'emojis','permission' => NULL,'created_at' => '2022-12-16 08:56:57','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '29','parent_id' => '13','order' => '26','title' => 'Official Messages','icon' => 'fa-envelope','uri' => 'official_msgs','permission' => NULL,'created_at' => '2022-12-18 16:21:18','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '30','parent_id' => '13','order' => '27','title' => 'home carousels','icon' => 'fa-image','uri' => 'home_carousels','permission' => NULL,'created_at' => '2022-12-20 13:52:26','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '31','parent_id' => '13','order' => '28','title' => 'vip privileges','icon' => 'fa-angellist','uri' => 'vip_prev','permission' => NULL,'created_at' => '2022-12-20 22:53:19','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '32','parent_id' => '0','order' => '46','title' => 'Agencies','icon' => 'fa-bookmark','uri' => 'agencies','permission' => NULL,'created_at' => '2023-01-03 10:59:06','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '33','parent_id' => '13','order' => '16','title' => 'Target','icon' => 'fa-bullseye','uri' => 'targets','permission' => NULL,'created_at' => '2023-01-04 14:59:53','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '34','parent_id' => '13','order' => '17','title' => 'Families','icon' => 'fa-sellsy','uri' => 'families','permission' => NULL,'created_at' => '2023-01-09 12:49:38','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '35','parent_id' => '13','order' => '18','title' => 'users targets','icon' => 'fa-blind','uri' => 'userTarget','permission' => NULL,'created_at' => '2023-01-14 01:33:09','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '36','parent_id' => '13','order' => '19','title' => 'user profile','icon' => 'fa-bookmark-o','uri' => 'profiles','permission' => NULL,'created_at' => '2023-01-14 01:57:01','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '37','parent_id' => '0','order' => '1','title' => 'dashboard','icon' => 'fa-area-chart','uri' => '/','permission' => NULL,'created_at' => '2023-01-14 04:02:35','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '38','parent_id' => '0','order' => '2','title' => 'agency','icon' => 'fa-bars','uri' => NULL,'permission' => NULL,'created_at' => '2023-01-14 05:08:47','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '39','parent_id' => '38','order' => '3','title' => 'users','icon' => 'fa-users','uri' => 'users','permission' => 'browse-users','created_at' => '2023-01-14 05:12:02','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '40','parent_id' => '38','order' => '4','title' => 'targets','icon' => 'fa-anchor','uri' => 'userTarget','permission' => NULL,'created_at' => '2023-01-14 05:16:31','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '41','parent_id' => '13','order' => '20','title' => 'Join To Agency Requests','icon' => 'fa-500px','uri' => 'agency_join_requests','permission' => NULL,'created_at' => '2023-01-17 18:30:41','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '42','parent_id' => '13','order' => '21','title' => 'recharge balance','icon' => 'fa-bars','uri' => 'charges','permission' => NULL,'created_at' => '2023-01-25 14:38:51','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '43','parent_id' => '13','order' => '22','title' => 'charge values','icon' => 'fa-amazon','uri' => 'charge_values','permission' => NULL,'created_at' => '2023-01-25 21:27:09','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '44','parent_id' => '13','order' => '23','title' => 'family levels','icon' => 'fa-adn','uri' => 'family_levels','permission' => NULL,'created_at' => '2023-01-31 10:13:14','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '45','parent_id' => '13','order' => '24','title' => 'Silver','icon' => 'fa-adjust','uri' => 'silver','permission' => NULL,'created_at' => '2023-02-02 10:18:32','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '46','parent_id' => '13','order' => '25','title' => 'coins','icon' => 'fa-area-chart','uri' => 'coins','permission' => NULL,'created_at' => '2023-02-06 00:08:28','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '47','parent_id' => '13','order' => '14','title' => 'the vips','icon' => 'fa-vimeo','uri' => 'ovip','permission' => NULL,'created_at' => '2023-02-09 10:07:57','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '48','parent_id' => '13','order' => '15','title' => 'vip privilege','icon' => 'fa-bold','uri' => 'vip_privilege','permission' => NULL,'created_at' => '2023-02-09 10:09:05','updated_at' => '2023-02-09 10:10:12'),
            array('id' => '49','parent_id' => '13','order' => '0','title' => 'Tickets','icon' => 'fa-sticky-note','uri' => 'tickets','permission' => NULL,'created_at' => '2023-02-15 21:59:07','updated_at' => '2023-02-15 21:59:07')
        );
        DB::table ('admin_menu')->insert ($admin_menu);
    }
}
