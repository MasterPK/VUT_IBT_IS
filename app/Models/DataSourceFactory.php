<?php


namespace App\Models;


use App\Models\Orm\Orm;

class DataSourceFactory
{
    private $orm;

    public function __construct(Orm $orm)
    {
        $this->orm = $orm;
    }

    private $filters;

    public function setFilters($filter)
    {
        $filters = [];
        foreach ($filter as $k => $v) {
            if ($k == 'id' || is_array($v)) {
                $filters[$k] = $v;
            } else {
                $filters[$k . ' LIKE ?'] = "%$v%";
            }
        }
        $this->filters=$filters;
    }

    public function addFilter($filter)
    {
        foreach ($filter as $key=>$value)
        {
            $this->filters[$key]=$value;
        }

    }

    public function getData($repository, $order = null)
    {
        if (isset($order[0])) {
            $data = $this->orm->getRepositoryByName($repository)->findBy($this->filters)->orderBy($order[0], $order[1])->fetchAll();
        } else {
            $data = $this->orm->getRepositoryByName($repository)->findBy($this->filters)->fetchAll();
        }
        return $data;

    }

}