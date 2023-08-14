<?php

namespace Cher4geo35\ParseNews\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use PortedCheese\BaseSettings\Traits\InitPolicy;

class ProgressParseNewsPolicy
{
    use HandlesAuthorization;
    use InitPolicy {
        InitPolicy::__construct as private __ipoConstruct;
    }

    const VIEW_ALL = 2;
    const CREATE = 4;
    const UPDATE = 16;

    public function __construct()
    {
        $this->__ipoConstruct("ProgressParseNewsPolicy");
    }

    /**
     * Получить права доступа.
     *
     * @return array
     */
    public static function getPermissions()
    {
        return [
            self::VIEW_ALL => "Просмотр всех",
            self::CREATE => "Импорт новостей",
            self::UPDATE => "Изменение настроек",
        ];
    }

    /**
     * Стандартные права.
     *
     * @return int
     */
    public static function defaultRules()
    {
        return self::VIEW_ALL + self::CREATE + self::UPDATE;
    }

    /**
     * Determine whether the user can view any tasks.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasPermission($this->model, self::VIEW_ALL);
    }

    /**
     * Determine whether the user can create tasks.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasPermission($this->model, self::CREATE);
    }

    /**
     * Обновление пользователей.
     *
     * @param User $user
     * @return bool
     */
    public function update(User $user)
    {
        return $user->hasPermission($this->model, self::UPDATE);
    }
}
