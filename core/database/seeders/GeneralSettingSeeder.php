<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeneralSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('general_settings')->insert([
            'site_name' => 'ViserGo',
            'cur_text' => 'USD',
            'cur_sym' => '$',
            'email_from' => 'info@viserlab.com',
            'email_from_name' => '{{site_name}}',
            'email_template' => '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">... (email template here) ...',
            'sms_template' => 'hi {{fullname}} ({{username}}), {{message}}',
            'sms_from' => '{{site_name}}',
            'push_title' => '{{site_name}}',
            'push_template' => 'hi {{fullname}} ({{username}}), {{message}}',
            'base_color' => 'FE8B1C',
            'secondary_color' => 'c7bb10',
            'mail_config' => '{"name":"php"}',
            'sms_config' => '{"name":"clickatell","clickatell":{"api_key":"----------------"},"infobip":{"username":"------------8888888","password":"-----------------"},"message_bird":{"api_key":"-------------------"},"nexmo":{"api_key":"----------------------","api_secret":"----------------------"},"sms_broadcast":{"username":"----------------------","password":"-----------------------------"},"twilio":{"account_sid":"-----------------------","auth_token":"---------------------------","from":"----------------------"},"text_magic":{"username":"-----------------------","apiv2_key":"-------------------------------"},"custom":{"method":"get","url":"https://hostname.com/demo-api-v1","headers":{"name":["api_key"],"value":["test_api 555"]},"body":{"name":["from_number"],"value":["5657545757"]}}}',
            'firebase_config' => '{"apiKey":"---------------------","authDomain":"----------------------","projectId":"---------------","storageBucket":"--------------------","messagingSenderId":"---------------------","appId":"----------------","measurementId":"----------------"}',
            'global_shortcodes' => '{"site_name":"Name of your site","site_currency":"Currency of your site","currency_symbol":"Symbol of currency"}',
            'ev' => 0,
            'en' => 0,
            'sv' => 0,
            'sn' => 0,
            'pn' => 0,
            'force_ssl' => 0,
            'maintenance_mode' => 0,
            'secure_password' => 0,
            'agree' => 1,
            'multi_language' => 1,
            'registration' => 1,
            'active_template' => 'basic',
            'socialite_credentials' => '{"google":{"client_id":"-----------------","client_secret":"-----------------","status":1},"facebook":{"client_id":"-----------------","client_secret":"-----------------","status":1},"linkedin":{"client_id":"---------------------","client_secret":"--------------------","status":1}}',
            'last_cron' => now(),
            'available_version' => '1.0',
            'system_customized' => 0,
            'paginate_number' => 20,
            'currency_format' => 1,
            'cart_duration' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
