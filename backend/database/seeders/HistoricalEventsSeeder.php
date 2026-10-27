<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class HistoricalEventsSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'title' => 'Kafr El-Sheikh Half Marathon 2021',
                'title_ar' => 'نصف ماراثون كفر الشيخ 2021',
                'slug' => 'half-marathon-2021',
                'event_year' => 2021,
                'location' => 'Kafr El-Sheikh, Egypt',
                'location_ar' => 'كفر الشيخ، مصر',
                'summary' => 'The first Kafr El-Sheikh Half Marathon brought runners together for a landmark race through the city.',
                'summary_ar' => 'جمع أول نصف ماراثون لكفر الشيخ العدّائين في سباق مميز بشوارع المدينة.',
                'description' => 'The first edition of the Kafr El-Sheikh Half Marathon marked a major moment for local sport. Runners and supporters filled the city streets, turning a shared dream into a community race built on hard work and determination.',
                'description_ar' => 'مثّلت النسخة الأولى من نصف ماراثون كفر الشيخ محطة مهمة للرياضة في المدينة. اجتمع العدّاؤون والمشجعون في الشوارع، وتحول الحلم المشترك إلى سباق مجتمعي قائم على العمل والإصرار.',
                'source_url' => 'https://elatiky.com/half-marathon-2021/',
                'sort_order' => 1,
                'is_published' => true,
                'photos' => [
                    ['path' => 'images/events/half-marathon-2021-cover.jpg', 'alt' => 'Runners setting off at the Kafr El-Sheikh Half Marathon 2021', 'is_cover' => true],
                    ['path' => 'images/events/half-marathon-archive-photo-1.jpg', 'alt' => 'Runners and event crew at the 2021 marathon'],
                    ['path' => 'images/events/half-marathon-archive-photo-2.jpg', 'alt' => 'The Kafr El-Sheikh marathon event stage'],
                    ['path' => 'images/events/half-marathon-archive-photo-3.jpg', 'alt' => 'Race day activities with local runners'],
                ],
            ],
            [
                'title' => 'Kafr El-Sheikh Half Marathon 2022',
                'title_ar' => 'نصف ماراثون كفر الشيخ 2022',
                'slug' => 'half-marathon-2022',
                'event_year' => 2022,
                'location' => 'Kafr El-Sheikh, Egypt',
                'location_ar' => 'كفر الشيخ، مصر',
                'summary' => 'The second edition grew the race with fresh ideas, more sports, and an even bigger community spirit.',
                'summary_ar' => 'وسّعت النسخة الثانية السباق بأفكار جديدة وأنشطة رياضية أكثر وروح مجتمعية أكبر.',
                'description' => 'Following the success of the first edition, the second Kafr El-Sheikh Half Marathon welcomed new ideas and a growing generation of sports lovers. The event brought the city together again and celebrated the energy of its runners.',
                'description_ar' => 'بعد نجاح النسخة الأولى، استقبل نصف ماراثون كفر الشيخ الثاني أفكارًا جديدة وجيلًا أكبر من محبي الرياضة. جمع الحدث أهل المدينة مرة أخرى واحتفى بحماس العدّائين.',
                'source_url' => 'https://elatiky.com/half-marathon-2022/',
                'sort_order' => 2,
                'is_published' => true,
                'photos' => [
                    ['path' => 'images/events/half-marathon-2022-cover.jpg', 'alt' => 'Warm-up activities at the Kafr El-Sheikh Half Marathon 2022', 'is_cover' => true],
                    ['path' => 'images/events/half-marathon-2022-photo-1.jpg', 'alt' => 'Runners gathering before the 2022 race'],
                    ['path' => 'images/events/half-marathon-2022-photo-2.jpg', 'alt' => 'Participants and organizers at the 2022 marathon'],
                    ['path' => 'images/events/half-marathon-2022-photo-3.jpg', 'alt' => 'Race day moments from the 2022 marathon'],
                ],
            ],
            [
                'title' => 'Kafr El-Sheikh Half Marathon 2023',
                'title_ar' => 'نصف ماراثون كفر الشيخ 2023',
                'slug' => 'half-marathon-2023',
                'event_year' => 2023,
                'location' => 'Kafr El-Sheikh, Egypt',
                'location_ar' => 'كفر الشيخ، مصر',
                'summary' => 'The third edition welcomed runners from different nationalities and expanded the marathon community.',
                'summary_ar' => 'استقبلت النسخة الثالثة عدّائين من جنسيات مختلفة ووسّعت مجتمع الماراثون.',
                'description' => 'The third edition of the Kafr El-Sheikh Half Marathon brought new participants and nationalities into the event. Runners came together to enjoy race day, meet new people, and grow a wider community around sport.',
                'description_ar' => 'جمعت النسخة الثالثة من نصف ماراثون كفر الشيخ مشاركين وجنسيات جديدة. اجتمع العدّاؤون للاستمتاع بيوم السباق والتعارف وتوسيع مجتمع الرياضة.',
                'source_url' => 'https://elatiky.com/half-marathon-2023/',
                'sort_order' => 3,
                'is_published' => true,
                'photos' => [
                    ['path' => 'images/events/half-marathon-2023-cover.jpg', 'alt' => 'The Kafr El-Sheikh Half Marathon 2023 race start', 'is_cover' => true],
                    ['path' => 'images/events/half-marathon-archive-photo-1.jpg', 'alt' => 'Runners and event crew at the 2023 marathon'],
                    ['path' => 'images/events/half-marathon-archive-photo-2.jpg', 'alt' => 'The Kafr El-Sheikh marathon event stage'],
                    ['path' => 'images/events/half-marathon-archive-photo-3.jpg', 'alt' => 'Race day activities with local runners'],
                ],
            ],
        ];

        foreach ($events as $item) {
            $photos = $item['photos'];
            unset($item['photos']);

            $event = Event::query()->firstOrCreate(['slug' => $item['slug']], $item);
            foreach ($photos as $index => $photo) {
                $event->photos()->firstOrCreate(
                    ['path' => $photo['path']],
                    [...$photo, 'sort_order' => $index + 1]
                );
            }
        }
    }
}
