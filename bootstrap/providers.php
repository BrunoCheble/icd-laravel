<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Music\Providers\MusicServiceProvider::class,
    App\Modules\Members\Providers\MembersServiceProvider::class,
    App\Modules\Visitors\Providers\VisitorsServiceProvider::class,
    App\Modules\Dashboard\Providers\DashboardServiceProvider::class,
    App\Modules\Prayers\Providers\PrayersServiceProvider::class,
    App\Modules\Announcements\Providers\AnnouncementsServiceProvider::class,
    App\Modules\Ministries\Providers\MinistriesServiceProvider::class,
    App\Modules\Gallery\Providers\GalleryServiceProvider::class,
    App\Modules\Discipleship\Providers\DiscipleshipServiceProvider::class,
    App\Modules\Users\Providers\UsersServiceProvider::class,
];
