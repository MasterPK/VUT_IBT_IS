<?php


namespace App\Models\Orm;


use Nextras\Orm\Repository\Repository;

/**
 * Class BaseRepository
 * Used as base repository for all other repositories. Provides universal functions.
 * @package App\Models\Orm
 * @author Petr Křehlík
 */
abstract class BaseRepository extends Repository
{
    /**
     * Update item with new values by ID.
     * @param int $id
     * @param array|mixed $newValues
     */
    public function update(int $id, $newValues)
    {
        $user = $this->getById((int)$id);

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
     * Update item with new values by mixed.
     * Update only first found row!
     * @param array $by
     * @param array|mixed $newValues
     */
    public function updateBy(array $by, $newValues)
    {
        $user = $this->getBy($by);

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
        $row=$this->getById((int)$id);
        if(!$row)
        {
            return;
        }
        $this->removeAndFlush($row);
    }

    /**
     * Delete row with id;
     * Delete only first found row!
     * @param array $by
     */
    public function deleteBy(array $by)
    {
        $row=$this->getBy($by);
        if(!$row)
        {
            return;
        }
        $this->removeAndFlush($row);
    }

    /**
     * Create collection. If LIKE found use LikeFilterFunction.
     * @param string $name
     * @return LikeFilterFunction|\Nextras\Orm\Repository\Functions\ConjunctionOperatorFunction|\Nextras\Orm\Repository\Functions\DisjunctionOperatorFunction|\Nextras\Orm\Repository\Functions\ValueOperatorFunction
     */
    public function createCollectionFunction(string $name)
    {
        if ($name === LikeFilterFunction::class) {
            return new LikeFilterFunction();
        } else {
            return parent::createCollectionFunction($name);
        }
    }
}