<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Model Activity — jembatan ke Spatie Activitylog.
 *
 * Meng-extend model Spatie agar bisa digunakan
 * sebagai Filament Resource untuk menampilkan log aktivitas.
 */
class Activity extends SpatieActivity
{
    //
}
