<?php

namespace Illuminate\Contracts\Auth;

interface Guard
{
    /**
     * @return \Modules\Core\Models\User|null
     */
    public function user();
}