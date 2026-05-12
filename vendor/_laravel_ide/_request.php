<?php

namespace Illuminate\Http;

interface Request
{
    /**
     * @return \Modules\Core\Models\User|null
     */
    public function user($guard = null);
}