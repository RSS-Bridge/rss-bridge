<?php

declare(strict_types=1);

class NHKWorldJapanShowBridge extends BridgeAbstract
{
    const NAME = 'NHK World-Japan Show Bridge';
    const URI = 'https://www3.nhk.or.jp';
    const CACHE_TIMEOUT = 14400; // 4h
    const DESCRIPTION = 'Returns available episodes from NHK World-Japan Shows';
    const MAINTAINER = 'TReKiE';

    const PARAMETERS = [
        [
            'show' => [
                'name' => 'Name of show',
                'type' => 'text',
                'exampleValue' => 'catseye',
                'required' => true,
                'title' => 'Enter the name of the show as it appears in the URL, e.g. "catseye" for https://www3.nhk.or.jp/nhkworld/en/shows/catseye/'
            ],
            'language' => [
                'name' => 'language',
                'type' => 'list',
                'title' => 'Language of the show',
                'values' => [
                    'English' => 'en',
                    'العربية' => 'ar',
                    'বাংলা' => 'bn',
                    'မြန်မာဘာသာစကား' => 'my',
                    '中文（简体）' => 'zh',
                    '中文（繁體）' => 'zt',
                    'Français' => 'fr',
                    'हिन्दी' => 'hi',
                    'Bahasa Indonesia' => 'id',
                    '코리언' => 'ko',
                    'فارسی' => 'fa',
                    'Português' => 'pt',
                    'Русский' => 'ru',
                    'Español' => 'es',
                    'Kiswahili' => 'sw',
                    'ภาษาไทย' => 'th',
                    'Türkçe' => 'tr',
                    'Українська' => 'uk',
                    'اردو' => 'ur',
                    'Tiếng Việt' => 'vi'
                ],
                'defaultValue' => 'en'
            ],
            'embedoption' => [
                'name' => 'Embed option',
                'type' => 'list',
                'title' => 'Choose to embed the NHK World-Japan video player, a static thumbnail, or no embedding for each episode',
                'values' => [
                    'Embed video player' => 'embed',
                    'Thumbnail' => 'thumb',
                    'None' => 'none'
                ],
                'defaultValue' => 'embed'
            ]
        ]
    ];

    protected static $labels = [
        'length' => [
            'ar' => 'المدة:',
            'bn' => 'দৈর্ঘ্য:',
            'en' => 'Length:',
            'my' => 'အချိန်အရှည်:',
            'zh' => '时长:',
            'zt' => '時長:',
            'fr' => 'Durée:',
            'hi' => 'अवधि:',
            'id' => 'Durasi:',
            'ko' => '재생 시간:',
            'fa' => 'مدت زمان:',
            'pt' => 'Duração:',
            'ru' => 'Длительность:',
            'es' => 'Duración:',
            'sw' => 'Urefu:',
            'th' => 'ความยาว:',
            'tr' => 'Süre:',
            'uk' => 'Тривалість:',
            'ur' => 'دورانیہ:',
            'vi' => 'Thời lượng:'
        ],
        'broadcast' => [
            'ar' => 'بث:',
            'bn' => 'প্রচার:',
            'en' => 'Broadcast:',
            'my' => 'ထုတ်လွှင့်မှု:',
            'zh' => '播出:',
            'zt' => '播放:',
            'fr' => 'Diffusion:',
            'hi' => 'प्रसारण:',
            'id' => 'Siaran:',
            'ko' => '방송:',
            'fa' => 'پخش:',
            'pt' => 'Transmissão:',
            'ru' => 'Трансляция:',
            'es' => 'Emisión:',
            'sw' => 'Matangazo:',
            'th' => 'ออกอากาศ:',
            'tr' => 'Yayın:',
            'uk' => 'Трансляція:',
            'ur' => 'نشریات:',
            'vi' => 'Phát sóng:'
        ],
        'availableuntil' => [
            'ar' => 'متاح حتى:',
            'bn' => 'পর্যন্ত উপলব্ধ:',
            'en' => 'Available until:',
            'my' => 'ရရှိနိုင်သည်:',
            'zh' => '可用至:',
            'zt' => '可用至:',
            'fr' => 'Disponible jusqu’au:',
            'hi' => 'उपलब्ध है:',
            'id' => 'Tersedia hingga:',
            'ko' => '이용 가능:',
            'fa' => 'در دسترس تا:',
            'pt' => 'Disponível até:',
            'ru' => 'Доступно до:',
            'es' => 'Disponible hasta:',
            'sw' => 'Inapatikana hadi:',
            'th' => 'ใช้งานได้จนถึง:',
            'tr' => 'Kullanılabilir:',
            'uk' => 'Доступно до:',
            'ur' => 'دستیاب ہے:',
            'vi' => 'Có sẵn đến:'
        ],
        'watchdirectly' => [
            'ar' => 'شاهد مباشرة على مشغل الفيديو',
            'bn' => 'সরাসরি ভিডিও প্লেয়ারে দেখুন',
            'en' => 'Watch on direct video player',
            'my' => 'တိုက်ရိုက်ဗီဒီယိုပလေယာတွင်ကြည့်ပါ',
            'zh' => '在直接视频播放器上观看',
            'zt' => '在直接视频播放器上观看',
            'fr' => 'Regarder sur le lecteur vidéo direct',
            'hi' => 'प्रत्यक्ष वीडियो प्लेयर पर देखें',
            'id' => 'Tonton di pemutar video langsung',
            'ko' => '직접 비디오 플레이어에서 시청하기',
            'fa' => 'مشاهده در پخش کننده ویدیویی مستقیم',
            'pt' => 'Assista no reprodutor de vídeo direto',
            'ru' => 'Смотреть на прямом видеоплеере',
            'es' => 'Ver en reproductor de video directo',
            'sw' => 'Tazama kwenye mchezaji wa video moja kwa moja',
            'th' => 'ดูบนเครื่องเล่นวิดีโอโดยตรง',
            'tr' => 'Doğrudan video oynatıcıda izle',
            'uk' => 'Дивитися на прямому відеоплеєрі',
            'ur' => 'براہ راست ویڈیو پلیئر پر دیکھیں',
            'vi' => 'Xem trên trình phát video trực tiếp'
        ],
        'watchonplayer' => [
            'ar' => 'شاهد على مشغل NHK World-Japan',
            'bn' => 'NHK World-Japan প্লেয়ারে দেখুন',
            'en' => 'Watch on NHK World-Japan player',
            'my' => 'NHK World-Japan ပလေယာတွင်ကြည့်ပါ',
            'zh' => '在NHK World-Japan播放器上观看',
            'zt' => '在NHK World-Japan播放器上观看',
            'fr' => 'Regarder sur le lecteur NHK World-Japan',
            'hi' => 'NHK World-Japan प्लेयर पर देखें',
            'id' => 'Tonton di pemutar NHK World-Japan',
            'ko' => 'NHK World-Japan 플레이어에서 시청하기',
            'fa' => 'مشاهده در پخش کننده NHK World-Japan',
            'pt' => 'Assista no reprodutor NHK World-Japan',
            'ru' => 'Смотреть на плеере NHK World-Japan',
            'es' => 'Ver en reproductor de NHK World-Japan',
            'sw' => 'Tazama kwenye mchezaji wa NHK World-Japan',
            'th' => 'ดูบนเครื่องเล่น NHK World-Japan',
            'tr' => 'NHK World-Japan oynatıcısında izle',
            'uk' => 'Дивитися на плеєрі NHK World-Japan',
            'ur' => 'NHK World-Japan پلیئر پر دیکھیں',
            'vi' => 'Xem trên trình phát NHK World-Japan'
        ]
    ];

    protected static $rtlLanguages = [
        'ar','fa','ur'
    ];

    public function getURI()
    {
        if (($this->getInput('show')) && ($this->getInput('language'))) {
            return self::URI . '/nhkworld/' . $this->getInput('language') . '/shows/' . $this->getInput('show') . '/';
        }

        return parent::getURI() . '/nhkworld/';
    }

    public function getName()
    {
        if (($this->getInput('show')) && ($this->getInput('language'))) {
            $html = getSimpleHTMLDOMCached($this->getURI());
            return html_entity_decode($html->find('meta[property="og:title"]', 0)->content, ENT_QUOTES, 'UTF-8');
        }

        return parent::getName();
    }

    public function getIcon()
    {
        return 'https://www3.nhk.or.jp/nhkworld/common/site_images/nw_webapp.ico';
    }

    public function collectData()
    {
        $json = getContents('https://api.nhkworld.jp/nwapi/vodesdlist/v7b/program/' . $this->getInput('show') . '/' . $this->getInput('language') . '/all/all.json');
        $data = json_decode($json, true);

        if (isset($data['data']['episodes']) && is_array($data['data']['episodes'])) {
            foreach ($data['data']['episodes'] as $program) {
                $title = $program['sub_title_clean'] ?? '';
                $author = $program['title_clean'] ?? '';
                $description = $program['description'] ?? '';
                $url = $program['url'];
                $vod_id = $program['vod_id'];
                $iframeurl = self::URI . '/nhkworld/common/player/tv/vod/world/player/?opid=' . $vod_id;
                $movielength = $program['movie_lengh'] ?? 'Unknown length';
                $onair = $program['onair'] ?? round(microtime(true) * 1000);
                $vod_to = $program['vod_to'] ?? round(microtime(true) * 1000);

                switch ($this->getInput('embedoption')) {
                    case 'embed':
                        $embedhtml = '<iframe src="' . $iframeurl . '" width="640" height="360" frameborder="0" allowfullscreen referrerpolicy="no-referrer">';
                        $embedhtml .= '<img src="' . self::URI . $program['image'] . '" alt="Video thumbnail" width="640" height="360"></iframe><br><br>';
                        break;
                    case 'thumb':
                        $embedhtml = '<img src="' . self::URI . $program['image'] . '" alt="Video thumbnail"><br><br>';
                        break;
                    default:
                        $embedhtml = '';
                }

                $dt = new DateTime('@' . ($onair / 1000));
                $dt->setTimezone(new DateTimeZone('UTC'));
                $broadcastdate = ($this->getInput('language') === 'en') ? $dt->format('F j, Y') : $dt->format('Y-m-d');
                $voddate = ($this->getInput('language') === 'en') ? date('F j, Y', $vod_to / 1000) : date('Y-m-d', $vod_to / 1000);
                $spantag = '<span dir="' . (in_array($this->getInput('language'), self::$rtlLanguages) ? 'rtl' : 'ltr') . '">';

                $description = $spantag . $description;
                $description .= '<br><br>';
                $description .= $this->getLocaleString('length') . ' ' . $movielength . '<br>';
                $description .= $this->getLocaleString('broadcast') . ' ' . $broadcastdate . ' UTC <br> ' . $this->getLocaleString('availableuntil') . ' ' . $voddate . '<br><br>';

                $description .= $embedhtml;

                $description .= '<a href="' . $iframeurl . '" referrerpolicy="no-referrer">' . $this->getLocaleString('watchdirectly') . '</a>';
                $description .= '<br><a href="' . self::URI . $url . '" referrerpolicy="no-referrer">' . $this->getLocaleString('watchonplayer') . '</a>';
                $description .= '</span>';

                $item = [];
                $item['uri'] = self::URI . $url;
                $item['uid'] = self::URI . $url;
                $item['title'] = $title;
                $item['author'] = $author;
                $item['timestamp'] = $onair / 1000;
                $item['content'] = $description;

                $this->items[] = $item;
            }
        } else {
            throw new \Exception('Could not find the episodes for this show. Please create a new GitHub issue if this is unexpected.');
        }
    }

    public function detectParameters($url)
    {
        $params = [];
        $regex = '/^(https?:\/\/)?(www3\.)?nhk\.or\.jp\/nhkworld\/(?<language>[a-z]{2})\/shows\/(?<show>[a-zA-Z0-9_-]+)\/?$/';

        if (preg_match($regex, $url, $matches) > 0) {
            $params['language'] = $matches['language'];
            $params['show'] = $matches['show'];
            return $params;
        }

        return null;
    }

    protected function getLocaleString($string)
    {
        $language = $this->getInput('language');
        if (isset(self::$labels[$string][$language])) {
            return self::$labels[$string][$language];
        }

        if (isset(self::$labels[$string]['en'])) {
            return self::$labels[$string]['en'];
        }

        return '';
    }
}
