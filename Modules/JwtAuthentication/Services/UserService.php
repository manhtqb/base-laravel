<?php

namespace Modules\JwtAuthentication\Services;

use Illuminate\Support\Facades\Log;
use Modules\JwtAuthentication\Repositories\UserRepository;

class UserService implements UserServiceInterface
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function createUser(array $params)
    {
        try {
            $newUser = $this->userRepository->create($params);

            return $newUser;
        } catch (\Exception $exception) {
            Log::error($exception);

            return false;
        }
    }

    public function updateUser(int $id, array $params)
    {
        try {
            $updatedUser = $this->userRepository->update($params, $id);

            return $updatedUser;
        } catch (\Exception $exception) {
            Log::error($exception);

            return false;
        }
    }

    public function deleteSingleUserById(int $id)
    {
//        todo
    }

    public function firstUserByCondition(array $condition)
    {
        try {
            $user = $this->userRepository->findWhere($condition)->first();

            return $user;
        } catch (\Exception $exception) {
            Log::error($exception);

            return false;
        }
    }
}
