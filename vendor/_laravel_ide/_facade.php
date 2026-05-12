<?php

namespace Illuminate\Support\Facades;

interface Auth
{
    /**
     * @return \Modules\Core\Models\User|false
     */
    public static function loginUsingId(mixed $id, bool $remember = false);

    /**
     * @return \Modules\Core\Models\User|false
     */
    public static function onceUsingId(mixed $id);

    /**
     * @return \Modules\Core\Models\User|null
     */
    public static function getUser();

    /**
     * @return \Modules\Core\Models\User
     */
    public static function authenticate();

    /**
     * @return \Modules\Core\Models\User|null
     */
    public static function user();

    /**
     * @return \Modules\Core\Models\User|null
     */
    public static function logoutOtherDevices(string $password);

    /**
     * @return \Modules\Core\Models\User
     */
    public static function getLastAttempted();
}