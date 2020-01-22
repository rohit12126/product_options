<?php

namespace App\Core\Models;

use App\Core\Models\BaseModel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;

class BaseUserModel extends BaseModel implements
    \Illuminate\Contracts\Auth\Authenticatable,
    \Illuminate\Contracts\Auth\Access\Authorizable,
    \Illuminate\Contracts\Auth\CanResetPassword
{
    use Authenticatable, Authorizable, CanResetPassword;
}