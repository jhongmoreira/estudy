<?php

namespace App\Events;

use App\Models\StudySession;
use Illuminate\Foundation\Events\Dispatchable;

/** Disparado ao salvar uma sessão de estudo (revisões, ciclo e constância escutam nas próximas etapas). */
class SessionRecorded
{
    use Dispatchable;

    public function __construct(public StudySession $session) {}
}
