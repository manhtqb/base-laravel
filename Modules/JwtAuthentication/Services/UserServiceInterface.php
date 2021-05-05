<?php

namespace Modules\JwtAuthentication\Services;


interface UserServiceInterface
{
    public function createUser(array $params);

    public function updateUser(int $id, array $params);

    public function deleteSingleUserById(int $id);
}
