<?php
namespace Modules\JwtAuthentication\Repositories;

use Modules\Core\Repositories\MyRepository;
use Modules\JwtAuthentication\Entities\User;

class UserRepository extends MyRepository
{
    public function model()
    {
        return User::class;
    }
}
