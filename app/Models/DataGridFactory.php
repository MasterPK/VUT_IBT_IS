<?php
declare(strict_types=1);

namespace App\Models;


use App\Models\Orm\LikeFilterFunction;
use App\Models\Orm\Orm;
use Nette\Utils\Paginator;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\IEntity;

class DataGridFactory
{
    private $orm;

    public function __construct(Orm $orm)
    {
        $this->orm = $orm;
    }


    /**
     * Create data for datagrid.
     * @param string $repository Name of repository to use.
     * @param array $filter Array of filters in format key=>value. All will be converted to LIKE search.
     * @param array $order Array in format key=>value.
     * @param array $resetFilter Array in simple format. Items to be ignored if they are -1 in filter. Used for filtering select option ALL.
     * @param array $customFilter Add custom filter that will not be converted to LIKE search. Default operator is =.
     * @param Paginator|null $paginator Paginator instance.
     * @return IEntity[] Return collection of result data.
     */
    public function createDataSource(string $repository, $filter, $order, array $resetFilter = [], array $customFilter = [], Paginator $paginator = null)
    {

        foreach ($resetFilter as $value) {
            if (isset($filter[$value]) && $filter[$value] == -1) {
                unset($filter[$value]);
            }
        }

        $filters = [ICollection:: AND];
        foreach ($filter as $k => $v) {
            if ($k == 'id' || is_array($v) || is_numeric($v)) {
                $filters[$k] = $v;
            } else {
                array_push($filters, [LikeFilterFunction::class, $k, $v]);
            }
        }

        foreach ($customFilter as $key => $value) {
            $filters[$key] = $value;
        }

        if (isset($order[0])) {
            $data = $this->orm->getRepositoryByName($repository)->findBy($filters)->orderBy($order[0], $order[1]);
        } else {
            $data = $this->orm->getRepositoryByName($repository)->findBy($filters);
        }

        if($paginator!=null)
        {
            $data=$data->limitBy($paginator->getItemsPerPage(), $paginator->getOffset());
        }

        return $data->fetchAll();

    }


}