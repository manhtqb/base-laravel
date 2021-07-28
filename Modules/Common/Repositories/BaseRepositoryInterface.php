<?php

namespace Modules\Common\Repositories;

interface BaseRepositoryInterface
{
    /**
     * @param $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function update(int $id, array $data);

    /**
     * @param int $id
     * @return mixed
     */
    public function delete(int $id);
}
