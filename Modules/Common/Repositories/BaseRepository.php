<?php

namespace Modules\Common\Repositories;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected $model;
    protected $query;
    protected $wheres = [];
    protected $orderBy = [];

    /**
     * @param array $fields
     * @return mixed
     */
    public function all(array $fields = ['*'])
    {
        return $this->model->all($fields);
    }

    /** find item of model by id
     * @param int $id
     * @param array $relationships
     * @return mixed
     */
    public function find(int $id, array $relationships = [])
    {
        return $this->model->with($relationships)->find($id);
    }

    /** find item of model by id
     * @param int $id
     * @return model or false
     */
    public function findOrFail(int $id)
    {
        return $this->model->findOrFail($id);
    }

    /**
     * @param array $data
     * @return object
     */
    public function create(array $data)
    {
        $data = $this->removeNotExistColumns($data);
        return $this->model->create($data);
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data)
    {
        $result = $this->model->find($id);
        $data = $this->removeNotExistColumns($data);
        foreach ($data as $key => $value) {
            $result->$key = $value;
        }
        return $result->save();
    }

    /**
     * @param array $data
     * @return object
     */
    public function updateOrCreate(array $condition, array $data)
    {
        return $this->model->updateOrCreate($condition, $data);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function delete(int $id)
    {
        $result = $this->model->find($id)->delete();

        return $result;
    }

    /** get item of model by column
     * @param string $column
     * @param $option
     * @return mixed
     */
    public function findBy(string $column, $option)
    {
        $data = $this->model->where($column, $option);

        return $data;
    }

    /**
     * @param array $condition
     * @return mixed
     */
    public function findByCondition(array $condition)
    {
        return $this->model->where($condition);
    }

    public function removeNotExistColumns($input)
    {
        $tableColumns = $this->getTableColumns();
        foreach ($input as $keyInput => $valueInput) {
            if (!in_array($keyInput, $tableColumns)) {
                unset($input[$keyInput]);
            }
        }
        return $input;
    }

    public function getTableColumns()
    {
        return $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
    }

    public function createMultiple(array $input)
    {
        $data = $this->model->insert($input);
        if (!$data) {
            // throw new Exception(trans('message.create_error'));
        }

        return $data;
    }

    public function whereIn($field, $value)
    {
        return $this->model->whereIn($field, $value);
    }

    public function with($relation = [])
    {
        return $this->model->with($relation);
    }
}
