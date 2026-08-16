<?php

namespace Database\Seeders;

use Cache;
use App\Models\Setting;
use App\Models\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\NotificationTranslation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $appNameEn = Setting::where('key', 'app_title_en')->value('value') ?? 'Default';

        $appNameAr = Setting::where('key', 'app_title_ar')->value('value') ?? 'Default';

        // DB::table('notifications')->truncate();
        // DB::table('notification_translations')->truncate();

        $notifications = Notification::all();
        foreach ($notifications as $n) {
            Cache::forget($n->key);
        }

        // Insert notification

        /* ----------------------------------------sender level --------------------------------------------- */
        $sender_level = DB::table('notifications')->insertGetId([
            'key' => 'sender_level'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $sender_level,
                'title' => 'Sender level upgraded',
                'message' => 'Congratulations you reach sender level {level}',
                'language' => 'en'
            ],
            [
                'notification_id' => $sender_level,
                'title' => 'ترقية مستوى المرسل',
                'message' => 'تهانينا لقد وصلت لمستوى المرسل {level}',
                'language' => 'ar'
            ],
            [
                'notification_id' => $sender_level,
                'title' => 'प्रेषक स्तर उन्नत किया गया',
                'message' => 'बधाई हो आप प्रेषक स्तर पर पहुंच गए हैं {level}',
                'language' => 'hi'
            ],
            [
                'notification_id' => $sender_level,
                'title' => 'Gönderici düzeyi yükseltildi',
                'message' => 'Tebrikler gönderici seviyesine ulaştınız {level}',
                'language' => 'tu' // Fixed language code
            ]
        ]);

        /* ---------------------------------------receiver level ----------------------------------------- */

        $receiver_level = DB::table('notifications')->insertGetId([
            'key' => 'receiver_level'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $receiver_level,
                'title' => 'Receiver level upgraded',
                'message' => 'Congratulations you reach receiver level {level}',
                'language' => 'en'
            ],
            [
                'notification_id' => $receiver_level,
                'title' => 'ترقية مستوى المتلقى',
                'message' => 'تهانينا لقد وصلت لمستوى المتلقى {level}',
                'language' => 'ar'
            ],
            [
                'notification_id' => $receiver_level,
                'title' => 'रिसीवर स्तर उन्नत किया गया',
                'message' => 'बधाई हो आप रिसीवर स्तर तक पहुंच गए हैं {level}',
                'language' => 'hi'
            ],
            [
                'notification_id' => $receiver_level,
                'title' => 'Alıcı seviyesi yükseltildi',
                'message' => 'Tebrikler alıcı seviyesine ulaştınız {level}',
                'language' => 'tu' // Fixed language code
            ]
        ]);

        /* ----------------------------------background request --------------------------------- */

        $background_request_accept = DB::table('notifications')->insertGetId([
            'key' => 'background_accept'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $background_request_accept,
                'title' => '',
                'message' => "Your background image request has been accepted.",
                'language' => 'en'
            ],
            [
                'notification_id' => $background_request_accept,
                'title' => '',
                'message' => "تم قبول طلب صوره الخلفية",
                'language' => 'ar'
            ],
            [
                'notification_id' => $background_request_accept,
                'title' => '',
                'message' => "आपका पृष्ठभूमि छवि अनुरोध स्वीकार कर लिया गया है।",
                'language' => 'hi'
            ],
            [
                'notification_id' => $background_request_accept,
                'title' => '',
                'message' => "Arka plan resmi isteğiniz kabul edildi.",
                'language' => 'tu' // Fixed language code
            ]
        ]);



        $background_request_refuse = DB::table('notifications')->insertGetId([
            'key' => 'background_refuse'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $background_request_refuse,
                'title' => '',
                'message' => "background photo request was denied.",
                'language' => 'en'
            ],
            [
                'notification_id' => $background_request_refuse,
                'title' => '',
                'message' => "تم رفض طلب صوره الخلفية",
                'language' => 'ar'
            ],
            [
                'notification_id' => $background_request_refuse,
                'title' => '',
                'message' => "पृष्ठभूमि फोटो अनुरोध अस्वीकार कर दिया गया",
                'language' => 'hi'
            ],
            [
                'notification_id' => $background_request_refuse,
                'title' => '',
                'message' => "arka plan fotoğrafı isteği reddedildi",
                'language' => 'tu' // Fixed language code
            ]
        ]);



        /* ----------------------------------target ---------------------------------------------------- */

        $target = DB::table('notifications')->insertGetId([
            'key' => 'target'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $target,
                'title' => 'New Target',
                'message' => "Congrats! you achieve new target in {agency} your salary now is {salary}",
                'language' => 'en'
            ],
            [
                'notification_id' => $target,
                'title' => 'هدف جديد',
                'message' => "مبروك! لقد حققت هدفًا جديدًا في {agency} راتبك الآن هو {salary}",
                'language' => 'ar'
            ],
            [
                'notification_id' => $target,
                'title' => 'नया लक्ष्य',
                'message' => "बधाई हो! आपने {agency} में नया लक्ष्य हासिल कर लिया है, अब आपका वेतन {salary} है",
                'language' => 'hi'
            ],
            [
                'notification_id' => $target,
                'title' => 'Yeni Hedef',
                'message' => "Tebrikler! {agency} şirketinde yeni bir hedefe ulaştınız. Maaşınız artık {salary}",
                'language' => 'tu'
            ]
        ]);


        /* ------------------------------------- comment moment ----------------------------------------------- */

        $comment_moment = DB::table('notifications')->insertGetId([
            'key' => 'comment_moment'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $comment_moment,
                'title' => 'moment Comment',
                'message' => "{user_name} commented on your moment",
                'language' => 'en'
            ],
            [
                'notification_id' => $comment_moment,
                'title' => "لحظة التعليق",
                'message' => ' قام {user_name} بالتفاعل على اللحظة الخاصة بك',
                'language' => 'ar'
            ],
            [
                'notification_id' => $comment_moment,
                'title' => 'क्षण टिप्पणी',
                'message' => "{user_name} ने आपके पल पर टिप्पणी की",
                'language' => 'hi'
            ],
            [
                'notification_id' => $comment_moment,
                'title' => 'an Yorum',
                'message' => "{user_name} anınıza yorum yaptı",
                'language' => 'tu'
            ]
        ]);


        /* ----------------------------------- like real ------------------------------------ */

        $like_real = DB::table('notifications')->insertGetId([
            'key' => 'like_real'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $like_real,
                'title' => 'like real',
                'message' => '{user_name} reacted your real',
                'language' => 'en'
            ],
            [
                'notification_id' => $like_real,
                'title' => "الاعجاب بفيديو",
                'message' => 'قام {user_name} بالتفاعل على الفيديو الخاصة بك',
                'language' => 'ar'
            ],
            [
                'notification_id' => $like_real,
                'title' => 'असली जैसा',
                'message' => '{user_name} ने आपकी वास्तविक प्रतिक्रिया दी',
                'language' => 'hi'
            ],
            [
                'notification_id' => $like_real,
                'title' => 'gerçek gibi',
                'message' => '{user_name} gerçek tepkinizi verdi',
                'language' => 'tu'
            ]
        ]);


        /* ------------------------------- comment_real ----------------------------------- */

        $comment_real = DB::table('notifications')->insertGetId([
            'key' => 'comment_real'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $comment_real,
                'title' => 'like real',
                'message' => '{user_name} commented on your real',
                'language' => 'en'
            ],
            [
                'notification_id' => $comment_real,
                'title' => "الاعجاب بفيديو",
                'message' => ' قام {user_name} بالتفاعل على الفيديو الخاصة بك',
                'language' => 'ar'
            ],
            [
                'notification_id' => $comment_real,
                'title' => 'असली जैसा',
                'message' => '{user_name} ने आपके असली नाम पर टिप्पणी की है',
                'language' => 'hi'
            ],
            [
                'notification_id' => $comment_real,
                'title' => 'gerçek gibi',
                'message' => '{user_name} sizin gerçek fotoğrafınıza yorum yaptı',
                'language' => 'tu'
            ]
        ]);

        /* ----------------------------------- Like Moment ---------------------------------------- */

        $like_moment = DB::table('notifications')->insertGetId([
            'key' => 'like_moment'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $like_moment,
                'title' => 'like moment',
                'message' => '{user_name} reacted your moment',
                'language' => 'en'
            ],
            [
                'notification_id' => $like_moment,
                'title' => "الاعجاب بلحظة",
                'message' => '  قام {user_name} بالتفاعل على اللحظة الخاصة بك',
                'language' => 'ar'
            ],
            [
                'notification_id' => $like_moment,
                'title' => 'जैसे पल',
                'message' => '{user_name} ने आपके पल पर प्रतिक्रिया दी',
                'language' => 'hi'
            ],
            [
                'notification_id' => $like_moment,
                'title' => 'an gibi',
                'message' => '{user_name} anınıza tepki gösterdi',
                'language' => 'tu'
            ]
        ]);

        /* -------------------------------- accept agency ------------------------------------ */

        $accept_agency = DB::table('notifications')->insertGetId([
            'key' => 'accept_agency'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $accept_agency,
                'title' => '',
                'message' => 'Congrats! Your request to join {agency_name} agency is accepted',
                'language' => 'en'
            ],
            [
                'notification_id' => $accept_agency,
                'title' => '',
                'message' =>  'مبروك لقد تم قبول طلب الانضمام وكاله {agency_name}',
                'language' => 'ar'
            ],
            [
                'notification_id' => $accept_agency,
                'title' => '',
                'message' => 'बधाई हो! {agency_name} एजेंसी में शामिल होने का आपका अनुरोध स्वीकार कर लिया गया है',
                'language' => 'hi'
            ],
            [
                'notification_id' => $accept_agency,
                'title' => '',
                'message' => 'Tebrikler! {agency_name} ajansına katılma isteğiniz kabul edildi',
                'language' => 'tu'
            ]
        ]);

        /* ----------------------------------- visit profile ------------------------------------------------ */
        $visited_profile = DB::table('notifications')->insertGetId([
            'key' => 'visited_profile'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $visited_profile,
                'title' => '',
                'message' => '{user_name} visited your profile',
                'language' => 'en'
            ],
            [
                'notification_id' => $visited_profile,
                'title' => '',
                'message' =>  '{user_name} قام بزياره ملفك الشخصى',
                'language' => 'ar'
            ],
            [
                'notification_id' => $visited_profile,
                'title' => '',
                'message' => '{user_name} ने आपकी प्रोफ़ाइल देखी',
                'language' => 'hi'
            ],
            [
                'notification_id' => $visited_profile,
                'title' => '',
                'message' => '{user_name} profilinizi ziyaret etti',
                'language' => 'tu'
            ]
        ]);

        /* ------------------------------------- follow ------------------------------------------- */

        $follow = DB::table('notifications')->insertGetId([
            'key' => 'follow'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $follow,
                'title' => '',
                'message' => '{user_name} followed you',
                'language' => 'en'
            ],
            [
                'notification_id' => $follow,
                'title' => '',
                'message' =>  ' بمتابعتك{user_name} قام',
                'language' => 'ar'
            ],
            [
                'notification_id' => $follow,
                'title' => '',
                'message' => '{user_name} ने आपका अनुसरण किया',
                'language' => 'hi'
            ],
            [
                'notification_id' => $follow,
                'title' => '',
                'message' => '{user_name} sizi takip etti',
                'language' => 'tu'
            ]
        ]);


        /* ------------------------------------- follow back ---------------------------------------- */

        $follow_back = DB::table('notifications')->insertGetId([
            'key' => 'follow_back'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $follow_back,
                'title' => '',
                'message' => 'Now you are friend with {user_name}',
                'language' => 'en'
            ],
            [
                'notification_id' => $follow_back,
                'title' => '',
                'message' =>  '{user_name} اصبحت صديق مع ',
                'language' => 'ar'
            ],
            [
                'notification_id' => $follow_back,
                'title' => '',
                'message' => 'अब आप {user_name} के मित्र हैं',
                'language' => 'hi'
            ],
            [
                'notification_id' => $follow_back,
                'title' => '',
                'message' => 'Artık {user_name} ile arkadaşsın',
                'language' => 'tu'
            ]
        ]);

        /* ------------------------------------ remove from family ---------------------------------- */
        $remove_from_family = DB::table('notifications')->insertGetId([
            'key' => 'remove_from_family'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $remove_from_family,
                'title' => '',
                'message' => 'Sorry! You are removed from family {family_name} ',
                'language' => 'en'
            ],
            [
                'notification_id' => $remove_from_family,
                'title' => '',
                'message' =>  'تم حظرك من عائله {family_name}',
                'language' => 'ar'
            ],
            [
                'notification_id' => $remove_from_family,
                'title' => '',
                'message' => 'क्षमा करें! आपको परिवार {family_name} से निकाल दिया गया है',
                'language' => 'hi'
            ],
            [
                'notification_id' => $remove_from_family,
                'title' => '',
                'message' => 'Üzgünüz! {family_name} ailesinden çıkarıldınız',
                'language' => 'tu'
            ]
        ]);


        /* ----------------------------------- admin family ---------------------------------------------- */
        $admin_family = DB::table('notifications')->insertGetId([
            'key' => 'admin_family'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $admin_family,
                'title' => '',
                'message' => "Congrats! You are now admin in family {fmaily_name} \nBe worthy of your position to raise " . $appNameEn,
                'language' => 'en'
            ],
            [
                'notification_id' => $admin_family,
                'title' => '',
                'message' =>  ' تهانينا لقد اصبحت الان مشرف فى عائلة {family_name}' . $appNameAr . 'يجب عليك ان تكون جيد بالمنصب من اجل ارتقاء  ',
                'language' => 'ar'
            ],
            [
                'notification_id' => $admin_family,
                'title' => '',
                'message' => "बधाई हो! अब आप परिवार {family_name} में व्यवस्थापक हैं " . $appNameEn . " को बढ़ाने के लिए अपने पद के योग्य बनें",
                'language' => 'hi'
            ],
            [
                'notification_id' => $admin_family,
                'title' => '',
                'message' => "Tebrikler! Artık {family_name} ailesinde admin oldun " . $appNameEn . "yı yetiştirmek için pozisyonuna layık ol",
                'language' => 'tu'
            ]
        ]);

        /* ------------------------------------- accept request agency ------------------------------ */
        $accept_request_agency = DB::table('notifications')->insertGetId([
            'key' => 'accept_request_agency'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $accept_request_agency,
                'title' => '',
                'message' => 'Your  agency has been accepted',
                'language' => 'en'
            ],
            [
                'notification_id' => $accept_request_agency,
                'title' => '',
                'message' =>   'لقد تم قبول وكالتك',
                'language' => 'ar'
            ],
            [
                'notification_id' => $accept_request_agency,
                'title' => '',
                'message' => 'आपकी एजेंसी स्वीकार कर ली गई है',
                'language' => 'hi'
            ],
            [
                'notification_id' => $accept_request_agency,
                'title' => '',
                'message' => 'Acentanız kabul edildi',
                'language' => 'tu'
            ]
        ]);

        /* ---------------------------------- refuse request agency ------------------------------ */
        $refuse_request_agency = DB::table('notifications')->insertGetId([
            'key' => 'refuse_request_agency'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $refuse_request_agency,
                'title' => '',
                'message' => 'Your agency has been refused',
                'language' => 'en'
            ],
            [
                'notification_id' => $refuse_request_agency,
                'title' => '',
                'message' =>  'لقد تم رفض وكالتك',
                'language' => 'ar'
            ],
            [
                'notification_id' => $refuse_request_agency,
                'title' => '',
                'message' => 'आपकी एजेंसी को अस्वीकार कर दिया गया है',
                'language' => 'hi'
            ],
            [
                'notification_id' => $refuse_request_agency,
                'title' => '',
                'message' => 'Acentanız reddedildi',
                'language' => 'tu'
            ]
        ]);

        /* ---------------------------------- user_send_gift_moment --------------------------------- */

        $user_send_gift_moment = DB::table('notifications')->insertGetId([
            'key' => 'user_send_gift_moment'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $user_send_gift_moment,
                'title' => '',
                'message' => '{user_name} send you a {gift} in your moment',
                'language' => 'en'
            ],
            [
                'notification_id' => $user_send_gift_moment,
                'title' => '',
                'message' =>  'قام {user_name} بارسال {gift} علي اللحظة الخاصة بك.',
                'language' => 'ar'
            ],
            [
                'notification_id' => $user_send_gift_moment,
                'title' => '',
                'message' => '{user_name} आपको इस समय एक {gift} भेज रहा हूँ',
                'language' => 'hi'
            ],
            [
                'notification_id' => $user_send_gift_moment,
                'title' => '',
                'message' => '{user_name} size bu anınızda bir {gift} gönderdi',
                'language' => 'tu'
            ]
        ]);

        /* ----------------------------------- mallSend --------------------------------------- */
        $gift_aristocracy = DB::table('notifications')->insertGetId([
            'key' => 'gift_aristocracy'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $gift_aristocracy,
                'title' => '',
                'message' => "{user_name} sent you {ware} gift \nClick and activate your gift now from the bag",
                'language' => 'en'
            ],
            [
                'notification_id' => $gift_aristocracy,
                'title' => '',
                'message' =>  "{user_name} ارسل لك {ware} جائزه \nاضغط وقم بتفعيل هديتك الآن من الحقيبة",
                'language' => 'ar'
            ],
            [
                'notification_id' => $gift_aristocracy,
                'title' => '',
                'message' => "{user_name} ने आपको {ware} उपहार भेजा है \क्लिक करें और बैग से अभी अपना उपहार सक्रिय करें",
                'language' => 'hi'
            ],
            [
                'notification_id' => $gift_aristocracy,
                'title' => '',
                'message' => "{user_name} size {ware} hediye gönderdi \Tıklayın ve hediyenizi şimdi çantadan etkinleştirin",
                'language' => 'tu'
            ]
        ]);


        /* ----------------------------------accept user family------------------------------------- */
        $accept_family = DB::table('notifications')->insertGetId([
            'key' => 'accept_family'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $accept_family,
                'title' => '',
                'message' => "Congrats! Your request to join family {family_name} is accepted \nFamily is everything, come on participate and grow your family",
                'language' => 'en'
            ],
            [
                'notification_id' => $accept_family,
                'title' => '',
                'message' =>  'تهانينا: لقد تم قبول اضافتك الى عائلة {family_name} . العائلة يعنى اللمة الحلوة يلا شارك وكبر اللمة الحلوة',
                'language' => 'ar'
            ],
            [
                'notification_id' => $accept_family,
                'title' => '',
                'message' => "बधाई हो! परिवार {family_name} में शामिल होने का आपका अनुरोध स्वीकार कर लिया गया है \nपरिवार ही सब कुछ है, आइए भाग लें और अपना परिवार बढ़ाएँ",
                'language' => 'hi'
            ],
            [
                'notification_id' => $accept_family,
                'title' => '',
                'message' => "Tebrikler! {family_name} ailesine katılma isteğiniz kabul edildi \nAile her şeydir, gelin katılın ve ailenizi büyütün",
                'language' => 'tu'
            ]
        ]);

        /* ------------------------------------ send family ---------------------------------------- */
        $send_Family = DB::table('notifications')->insertGetId([
            'key' => 'send_Family'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $send_Family,
                'title' => '',
                'message' => "{user_name} send request to join your family",
                'language' => 'en'
            ],
            [
                'notification_id' => $send_Family,
                'title' => '',
                'message' =>  ' {user_name} طلب االانضمام الى عائله',
                'language' => 'ar'
            ],
            [
                'notification_id' => $send_Family,
                'title' => '',
                'message' => "{user_name} आपके परिवार में शामिल होने के लिए अनुरोध भेजें",
                'language' => 'hi'
            ],
            [
                'notification_id' => $send_Family,
                'title' => '',
                'message' => "{user_name} ailenize katılmak için istek gönderdi",
                'language' => 'tu'
            ]
        ]);

        /* ----------------------------------------- got coin --------------------------------------- */
        $got_coin = DB::table('notifications')->insertGetId([
            'key' => 'got_coin'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $got_coin,
                'title' => '',
                'message' => 'Congrats {user_name} !   {coins} coins are added to your account',
                'language' => 'en'
            ],
            [
                'notification_id' => $got_coin,
                'title' => '',
                'message' =>  'تهانينا يا {user_name} . لقد تم اضافة {coins} كوين الى حسابك',
                'language' => 'ar'
            ],
            [
                'notification_id' => $got_coin,
                'title' => '',
                'message' => 'बधाई हो {user_name}! आपके खाते में {coins} सिक्के जोड़ दिए गए हैं',
                'language' => 'hi'
            ],
            [
                'notification_id' => $got_coin,
                'title' => '',
                'message' => 'Tebrikler {user_name}! {coins} coin hesabınıza eklendi',
                'language' => 'tu'
            ]
        ]);

        /* ------------------------------ ban user id --------------------------------- */
        $ban_user_id = DB::table('notifications')->insertGetId([
            'key' => 'ban_user_id'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $ban_user_id,
                'title' => '',
                'message' => "Sorry! You cannot log in to " . $appNameEn . "  now, you can log in after {duration} hours \nFor objection and complaint contact customer service",
                'language' => 'en'
            ],
            [
                'notification_id' => $ban_user_id,
                'title' => '',
                'message' =>  ' ابلاغك انك لن تستطيع الدخول الى ' . $appNameAr . ' الا بعد مرور  {duration} ساعة. للاعتراض و الشكوى يرجى التواصل مع خدمة العملاء',
                'language' => 'ar'
            ],
            [
                'notification_id' => $ban_user_id,
                'title' => '',
                'message' => "क्षमा करें! आप अभी " . $appNameEn . " में लॉग इन नहीं कर सकते, आप {duration} घंटे बाद लॉग इन कर सकते हैं \nआपत्ति और शिकायत के लिए ग्राहक सेवा से संपर्क करें",
                'language' => 'hi'
            ],
            [
                'notification_id' => $ban_user_id,
                'title' => '',
                'message' => "Üzgünüz! Şu anda " . $appNameEn . " ya giriş yapamazsınız, {duration} saat sonra giriş yapabilirsiniz \nİtiraz ve şikayet için müşteri hizmetleriyle iletişime geçin",
                'language' => 'tu'
            ]
        ]);

        /* --------------------------------- remove ban user ---------------------------------------- */
        $removeBan_user_id = DB::table('notifications')->insertGetId([
            'key' => 'removeBan_user_id'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $removeBan_user_id,
                'title' => '',
                'message' => 'Congrats! You can log in to ' . $appNameEn . ' now,',
                'language' => 'en'
            ],
            [
                'notification_id' => $removeBan_user_id,
                'title' => '',
                'message' =>  'مبروك انت الان تستطيع استخدام ' . $appNameAr,
                'language' => 'ar'
            ],
            [
                'notification_id' => $removeBan_user_id,
                'title' => '',
                'message' => 'बधाई हो! अब आप ' . $appNameEn . ' में लॉग इन कर सकते हैं,',
                'language' => 'hi'
            ],
            [
                'notification_id' => $removeBan_user_id,
                'title' => '',
                'message' => 'Tebrikler! Artık ' . $appNameEn . 'ya giriş yapabilirsiniz,',
                'language' => 'tu'
            ]
        ]);

        /* ----------------------------user vips --------------------------------- */
        $userVips = DB::table('notifications')->insertGetId([
            'key' => 'userVips'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $userVips,
                'title' => '',
                'message' => 'Congrats! You reach {vip}, for {duration} days ',
                'language' => 'en'
            ],
            [
                'notification_id' => $userVips,
                'title' => '',
                'message' =>  ' مبروك تم حصولك على {vip} لمدة {duration} يوم ',
                'language' => 'ar'
            ],
            [
                'notification_id' => $userVips,
                'title' => '',
                'message' => 'बधाई हो! आप {vip} तक पहुंच गए हैं, {duration} दिनों के लिए',
                'language' => 'hi'
            ],
            [
                'notification_id' => $userVips,
                'title' => '',
                'message' => 'Tebrikler! {duration} gün boyunca {vip} oldunuz',
                'language' => 'tu'
            ]
        ]);

        /* -----------------------------family level up---------------------------------- */
        $family_level_up = DB::table('notifications')->insertGetId([
            'key' => 'family_level_up'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $family_level_up,
                'title' => 'Family level up',
                'message' => 'Congrats! your family {family_name} level up to {family_level}',
                'language' => 'en'
            ],
            [
                'notification_id' => $family_level_up,
                'title' => 'رفع مستوى العائلة',
                'message' =>  'تهانينا! تم ترقية عائلتك {family_name} إلى المستوى {family_level}',
                'language' => 'ar'
            ],
            [
                'notification_id' => $family_level_up,
                'title' => 'परिवार का स्तर ऊपर',
                'message' => 'बधाई हो! आपके परिवार का {family_name} स्तर {family_level} तक पहुंच गया है',
                'language' => 'hi'
            ],
            [
                'notification_id' => $family_level_up,
                'title' => 'Aile seviyesi yükseldi',
                'message' => 'Tebrikler! Aileniz {family_name} {family_level} seviyesine yükseldi',
                'language' => 'tu'
            ]
        ]);


        /* -------------------------------------------ware Vips----------------------------------------- */
        $wareVips = DB::table('notifications')->insertGetId([
            'key' => 'wareVips'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $wareVips,
                'title' => '',
                'message' => 'Congrats! You reach {ware_vip}, for {duration} days ',
                'language' => 'en'
            ],
            [
                'notification_id' => $wareVips,
                'title' => '',
                'message' =>  ' مبروك تم حصولك على {ware_vip} لمدة {duration} يوم ',
                'language' => 'ar'
            ],
            [
                'notification_id' => $wareVips,
                'title' => '',
                'message' => 'बधाई हो! आप {ware_vip} पर पहुंच गए, {duration} दिनों के लिए',
                'language' => 'hi'
            ],
            [
                'notification_id' => $wareVips,
                'title' => '',
                'message' => 'Tebrikler! {duration} gün boyunca {ware_vip} seviyesine ulaştınız',
                'language' => 'tu'
            ]
        ]);

        /* ------------------------------accept request salary-------------------------------------------- */
        $accept_request_sallary = DB::table('notifications')->insertGetId([
            'key' => 'accept_request_sallary'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $accept_request_sallary,
                'title' => '',
                'message' => 'Your salary transfer request with value {value}  has been accepted',
                'language' => 'en'
            ],
            [
                'notification_id' => $accept_request_sallary,
                'title' => '',
                'message' =>  ' تم قبول طلبك الخاص بتحويل الراتب والذي يبلغ قيمه {value} ',
                'language' => 'ar'
            ],
            [
                'notification_id' => $accept_request_sallary,
                'title' => '',
                'message' => 'आपका {value} मूल्य वाला वेतन स्थानांतरण अनुरोध स्वीकार कर लिया गया है',
                'language' => 'hi'
            ],
            [
                'notification_id' => $accept_request_sallary,
                'title' => '',
                'message' => '{value} tutarındaki maaş transfer talebiniz kabul edildi',
                'language' => 'tu'
            ]
        ]);


        /* ------------------------------denied request sallary------------------------------------ */
        $denied_request_sallary = DB::table('notifications')->insertGetId([
            'key' => 'denied_request_sallary'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $denied_request_sallary,
                'title' => '',
                'message' => 'Your request for salary transfer with value: {value} was rejected for reason: {reason}',
                'language' => 'en'
            ],
            [
                'notification_id' => $denied_request_sallary,
                'title' => '',
                'message' =>  'تم رفض طلبك الخاص بتحويل الراتب الذي يبلغ قيمته {value} وذالك لسبب {reason}',
                'language' => 'ar'
            ],
            [
                'notification_id' => $denied_request_sallary,
                'title' => '',
                'message' => 'आपका वेतन हस्तांतरण का अनुरोध, जिसका मूल्य {value} है, इस कारण से अस्वीकृत कर दिया गया है: {reason}',
                'language' => 'hi'
            ],
            [
                'notification_id' => $denied_request_sallary,
                'title' => '',
                'message' => '{value} değerindeki maaş transferi talebiniz şu nedenle reddedildi: {reason}',
                'language' => 'tu'
            ]
        ]);

        /* -------------------------------- room target ------------------------------ */
        $roomTarget = DB::table('notifications')->insertGetId([
            'key' => 'roomTarget'
        ]);

        // Insert translations in batch
        DB::table('notification_translations')->insert([
            [
                'notification_id' => $roomTarget,
                'title' => '',
                'message' => "Congrats! you achieve new target in {room} you got {coins}",
                'language' => 'en'
            ],
            [
                'notification_id' => $roomTarget,
                'title' => '',
                'message' =>  "   تهانينا! لقد حققت هدفا جديدا فى {room} وحصلت على {coins}",
                'language' => 'ar'
            ],
            [
                'notification_id' => $roomTarget,
                'title' => '',
                'message' => "बधाई हो! आपने {room} में नया लक्ष्य हासिल कर लिया है, आपको {coins} मिल गए हैं",
                'language' => 'hi'
            ],
            [
                'notification_id' => $roomTarget,
                'title' => '',
                'message' => "Tebrikler! {room}da yeni hedefe ulaştın, {coins} kazandın",
                'language' => 'tu'
            ]
        ]);

        // RoomCup Reward Notification
        $roomcupReward = DB::table('notifications')->insertGetId([
            'key' => 'roomcup_reward'
        ]);

        DB::table('notification_translations')->insert([
            [
                'notification_id' => $roomcupReward,
                'title' => 'RoomCup Reward 🎉',
                'message' => 'Congratulations! You received {amount} coins as {reward_type} reward in RoomCup',
                'language' => 'en'
            ],
            [
                'notification_id' => $roomcupReward,
                'title' => 'جائزة رومكب 🎉',
                'message' => 'مبروك! لقد حصلت على {amount} عملة كمكافأة {reward_type} في رومكب',
                'language' => 'ar'
            ],
            [
                'notification_id' => $roomcupReward,
                'title' => 'RoomCup Ödülü 🎉',
                'message' => 'Tebrikler! RoomCup\'ta {reward_type} ödülü olarak {amount} jeton kazandınız',
                'language' => 'tu'
            ],
            [
                'notification_id' => $roomcupReward,
                'title' => 'RoomCup पुरस्कार 🎉',
                'message' => 'बधाई हो! आपने RoomCup में {reward_type} पुरस्कार के रूप में {amount} सिक्के प्राप्त किए',
                'language' => 'hi'
            ]
        ]);

        $notifications = Notification::all();
        foreach ($notifications as $n) {
            Cache::forever($n->key, $n->translations->toArray());
        }
    }
}
