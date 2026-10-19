<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Music\Providers\MusicServiceProvider::class,
    App\Modules\Members\Providers\MembersServiceProvider::class,
    App\Modules\Visitors\Providers\VisitorsServiceProvider::class,
    App\Modules\Dashboard\Providers\DashboardServiceProvider::class,
    App\Modules\Prayers\Providers\PrayersServiceProvider::class,
];
