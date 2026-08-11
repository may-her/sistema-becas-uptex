<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command(
    'convocatorias:recordar-cierre'
)
    ->dailyAt('08:00')
    ->withoutOverlapping();