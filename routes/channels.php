<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channels for medical real-time updates
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('patient.{id}', function ($user, $id) {
    // Allow the patient themself or authorized family/doctor via policy
    return (int) $user->id === (int) $id || $user->hasRole('doctor') || $user->hasRole('family');
});

Broadcast::channel('doctor.{id}', function ($user, $id) {
    return $user->hasRole('doctor') && (int) $user->id === (int) $id;
});

Broadcast::channel('family.{id}', function ($user, $id) {
    return $user->hasRole('family') && (int) $user->id === (int) $id;
});

Broadcast::channel('doctor.patient.{id}', function ($user, $id) {
    // Doctors authorized for patient channels should be verified via relation
    return $user->hasRole('doctor');
});
