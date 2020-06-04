<?php


namespace App\Models\Orm;


use Nextras\Orm\Repository\Repository;

abstract class BaseRepository extends Repository
{
    /**
     * Update item with new values.
     * @param int $id
     * @param array|mixed $newValues
     */
    public function update(int $id, $newValues)
    {
        $user = $this->getById($id);

        if(!$user)
            return;

        foreach ($newValues as $key => $value) {
            if($key=="id")
                continue;
            $user->$key=$value;
        }

        $this->persistAndFlush($user);
    }

    /**
     * Delete row with id;
     * @param $id
     */
    public function delete($id)
    {
        $row=$this->getById($id);
        if(!$row)
        {
            return;
        }
        $this->removeAndFlush($row);
    }
}