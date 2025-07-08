<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute باید پذیرفته شود.',
    'active_url' => ':attribute یک URL معتبر نیست.',
    'after' => ':attribute باید تاریخی بعد از :date باشد.',
    'after_or_equal' => ':attribute باید تاریخی بعد یا مساوی با :date باشد.',
    'alpha' => ':attribute فقط باید شامل حروف باشد.',
    'alpha_dash' => ':attribute فقط باید شامل حروف، اعداد، خط تیره و زیرخط باشد.',
    'alpha_num' => ':attribute فقط باید شامل حروف و اعداد باشد.',
    'array' => ':attribute باید یک آرایه باشد.',
    'before' => ':attribute باید تاریخی قبل از :date باشد.',
    'before_or_equal' => ':attribute باید تاریخی قبل یا مساوی با :date باشد.',
    'between' => [
        'numeric' => ':attribute باید بین :min و :max باشد.',
        'file' => ':attribute باید بین :min و :max کیلوبایت باشد.',
        'string' => ':attribute باید بین :min و :max کاراکتر باشد.',
        'array' => ':attribute باید بین :min و :max آیتم داشته باشد.',
    ],
    'boolean' => 'فیلد :attribute باید درست یا نادرست باشد.',
    'confirmed' => 'تاییدیه :attribute مطابقت ندارد.',
    'date' => ':attribute یک تاریخ معتبر نیست.',
    'date_equals' => ':attribute باید تاریخی مساوی با :date باشد.',
    'date_format' => ':attribute با فرمت :format مطابقت ندارد.',
    'different' => ':attribute و :other باید متفاوت باشند.',
    'digits' => ':attribute باید :digits رقم باشد.',
    'digits_between' => ':attribute باید بین :min و :max رقم باشد.',
    'dimensions' => ':attribute دارای ابعاد تصویر نامعتبر است.',
    'distinct' => 'فیلد :attribute دارای مقدار تکراری است.',
    'email' => ':attribute باید یک آدرس ایمیل معتبر باشد.',
    'ends_with' => ':attribute باید با یکی از موارد زیر پایان یابد: :values.',
    'exists' => ':attribute انتخاب شده نامعتبر است.',
    'file' => ':attribute باید یک فایل باشد.',
    'filled' => 'فیلد :attribute باید مقداری داشته باشد.',
    'gt' => [
        'numeric' => ':attribute باید بزرگتر از :value باشد.',
        'file' => ':attribute باید بزرگتر از :value کیلوبایت باشد.',
        'string' => ':attribute باید بزرگتر از :value کاراکتر باشد.',
        'array' => ':attribute باید بیش از :value آیتم داشته باشد.',
    ],
    'gte' => [
        'numeric' => ':attribute باید بزرگتر یا مساوی :value باشد.',
        'file' => ':attribute باید بزرگتر یا مساوی :value کیلوبایت باشد.',
        'string' => ':attribute باید بزرگتر یا مساوی :value کاراکتر باشد.',
        'array' => ':attribute باید :value آیتم یا بیشتر داشته باشد.',
    ],
    'image' => ':attribute باید یک تصویر باشد.',
    'in' => ':attribute انتخاب شده نامعتبر است.',
    'in_array' => 'فیلد :attribute در :other وجود ندارد.',
    'integer' => ':attribute باید یک عدد صحیح باشد.',
    'ip' => ':attribute باید یک آدرس IP معتبر باشد.',
    'ipv4' => ':attribute باید یک آدرس IPv4 معتبر باشد.',
    'ipv6' => ':attribute باید یک آدرس IPv6 معتبر باشد.',
    'json' => ':attribute باید یک رشته JSON معتبر باشد.',
    'lt' => [
        'numeric' => ':attribute باید کمتر از :value باشد.',
        'file' => ':attribute باید کمتر از :value کیلوبایت باشد.',
        'string' => ':attribute باید کمتر از :value کاراکتر باشد.',
        'array' => ':attribute باید کمتر از :value آیتم داشته باشد.',
    ],
    'lte' => [
        'numeric' => ':attribute باید کمتر یا مساوی :value باشد.',
        'file' => ':attribute باید کمتر یا مساوی :value کیلوبایت باشد.',
        'string' => ':attribute باید کمتر یا مساوی :value کاراکتر باشد.',
        'array' => ':attribute نباید بیش از :value آیتم داشته باشد.',
    ],
    'max' => [
        'numeric' => ':attribute نباید بزرگتر از :max باشد.',
        'file' => ':attribute نباید بزرگتر از :max کیلوبایت باشد.',
        'string' => ':attribute نباید بزرگتر از :max کاراکتر باشد.',
        'array' => ':attribute نباید بیش از :max آیتم داشته باشد.',
    ],
    'mimes' => ':attribute باید فایلی از نوع :values باشد.',
    'mimetypes' => ':attribute باید فایلی از نوع :values باشد.',
    'min' => [
        'numeric' => ':attribute باید حداقل :min باشد.',
        'file' => ':attribute باید حداقل :min کیلوبایت باشد.',
        'string' => ':attribute باید حداقل :min کاراکتر باشد.',
        'array' => ':attribute باید حداقل :min آیتم داشته باشد.',
    ],
    'multiple_of' => ':attribute باید مضربی از :value باشد.',
    'not_in' => ':attribute انتخاب شده نامعتبر است.',
    'not_regex' => 'فرمت :attribute نامعتبر است.',
    'numeric' => ':attribute باید یک عدد باشد.',
    'password' => [
        'min' => 'رمز عبور باید حداقل :min کاراکتر باشد.',
        'mixed' => 'رمز عبور باید حداقل شامل یک حرف بزرگ و یک حرف کوچک باشد.',
        'numbers' => 'رمز عبور باید حداقل شامل یک عدد باشد.',
        'symbols' => 'رمز عبور باید حداقل شامل یک نماد باشد.',
        'uncompromised' => 'رمز عبور در یک نشت اطلاعاتی ظاهر شده و قابل استفاده نیست. لطفاً رمز عبور دیگری انتخاب کنید.',
    ],
    'present' => 'فیلد :attribute باید موجود باشد.',
    'regex' => 'فرمت :attribute نامعتبر است.',
    'required' => 'فیلد :attribute الزامی است.',
    'required_if' => 'فیلد :attribute زمانی که :other برابر با :value است، الزامی است.',
    'required_unless' => 'فیلد :attribute الزامی است مگر اینکه :other در :values باشد.',
    'required_with' => 'فیلد :attribute زمانی که :values موجود است، الزامی است.',
    'required_with_all' => 'فیلد :attribute زمانی که :values موجود هستند، الزامی است.',
    'required_without' => 'فیلد :attribute زمانی که :values موجود نیست، الزامی است.',
    'required_without_all' => 'فیلد :attribute زمانی که هیچ یک از :values موجود نیستند، الزامی است.',
    'prohibited_if' => 'فیلد :attribute زمانی که :other برابر با :value است، ممنوع است.',
    'prohibited_unless' => 'فیلد :attribute ممنوع است مگر اینکه :other در :values باشد.',
    'same' => ':attribute و :other باید مطابقت داشته باشند.',
    'size' => [
        'numeric' => ':attribute باید :size باشد.',
        'file' => ':attribute باید :size کیلوبایت باشد.',
        'string' => ':attribute باید :size کاراکتر باشد.',
        'array' => ':attribute باید شامل :size آیتم باشد.',
    ],
    'starts_with' => ':attribute باید با یکی از موارد زیر شروع شود: :values.',
    'string' => ':attribute باید یک رشته باشد.',
    'timezone' => ':attribute باید یک منطقه زمانی معتبر باشد.',
    'unique' => ':attribute قبلاً گرفته شده است.',
    'uploaded' => ':attribute در بارگذاری ناموفق بود.',
    'url' => 'فرمت :attribute نامعتبر است.',
    'uuid' => ':attribute باید یک UUID معتبر باشد.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'پیام-سفارشی',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'name' => 'نام',
        'username' => 'نام کاربری',
        'email' => 'آدرس ایمیل',
        'first_name' => 'نام',
        'last_name' => 'نام خانوادگی',
        'password' => 'رمز عبور',
        'password_confirmation' => 'تایید رمز عبور',
        'city' => 'شهر',
        'country' => 'کشور',
        'address' => 'آدرس',
        'phone' => 'تلفن',
        'mobile' => 'موبایل',
        'age' => 'سن',
        'sex' => 'جنسیت',
        'gender' => 'جنسیت',
        'day' => 'روز',
        'month' => 'ماه',
        'year' => 'سال',
        'hour' => 'ساعت',
        'minute' => 'دقیقه',
        'second' => 'ثانیه',
        'title' => 'عنوان',
        'content' => 'محتوا',
        'description' => 'توضیحات',
        'excerpt' => 'خلاصه',
        'date' => 'تاریخ',
        'time' => 'زمان',
        'available' => 'موجود',
        'size' => 'اندازه',
        'price' => 'قیمت',
        'image' => 'تصویر',
        'subject' => 'موضوع',
        'message' => 'پیام',
        'verification_code' => 'کد تایید',
        'current_password' => 'رمز عبور فعلی',
        'new_password' => 'رمز عبور جدید',
        'new_password_confirmation' => 'تایید رمز عبور جدید',
        'amount' => 'مبلغ',
        'transaction_id' => 'شناسه تراکنش',
        'payment_method' => 'روش پرداخت',
        'gateway' => 'درگاه',
        'currency' => 'ارز',
        'ticket_price' => 'قیمت بلیت',
        'number_of_winners' => 'تعداد برندگان',
        'draw_date' => 'تاریخ قرعه‌کشی',
        'start_date' => 'تاریخ شروع',
        'end_date' => 'تاریخ پایان',
        'min_amount' => 'حداقل مبلغ',
        'max_amount' => 'حداکثر مبلغ',
        'fixed_charge' => 'کارمزد ثابت',
        'percent_charge' => 'کارمزد درصدی',
        'status' => 'وضعیت',
        'type' => 'نوع',
        'slug' => 'اسلاگ',
        'meta_keywords' => 'کلمات کلیدی متا',
        'meta_description' => 'توضیحات متا',
        'social_title' => 'عنوان اجتماعی',
        'social_description' => 'توضیحات اجتماعی',
        'host' => 'هاست',
        'port' => 'پورت',
        'encryption' => 'رمزگذاری',
        'app_key' => 'کلید برنامه',
        'api_public_key' => 'کلید عمومی API',
        'api_secret_key' => 'کلید خصوصی API',
        'account_sid' => 'شناسه حساب SID',
        'auth_token' => 'توکن احراز هویت',
        'from_number' => 'از شماره',
        'apiv2_key' => 'کلید Apiv2',
        'api_url' => 'URL API',
        'client_id' => 'شناسه مشتری',
        'client_secret' => 'کلید خصوصی مشتری',
        'callback_url' => 'URL بازگشت',
        'site_title' => 'عنوان سایت',
        'currency_symbol' => 'نماد ارز',
        'timezone' => 'منطقه زمانی',
        'site_base_color' => 'رنگ پایه سایت',
        'site_secondary_color' => 'رنگ ثانویه سایت',
        'logo' => 'لوگو',
        'favicon' => 'فاویکون',
        'reason' => 'دلیل',
    ],

];
