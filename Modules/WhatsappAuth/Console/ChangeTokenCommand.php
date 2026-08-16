<?php


namespace Modules\WhatsappAuth\Console;

use App\Facades\RedisService;
use Illuminate\Console\Command;

class ChangeTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:change-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $token = $this->login();
        if ($token) {
            RedisService::update('whatsapp_token', $token);
            $this->info('Making Token Done' );
            return 1;
        }
        $this->error('An error occurred');
        return 0;
    }

    private function login() : ?string
    {
        $url = config('whatsappauth.server_url_login');
        $password = config('whatsappauth.password');
        $username = config('whatsappauth.username');

        $response = \Http::post($url, ['username' => $username, 'password' => $password]);

        if ($response->status() == 200) {
            $data = $response->json();
            if (gettype($data) == 'array' && array_key_exists('token', $data)){
                return  $data['token'];
            }
        }
        return null;
    }
}
