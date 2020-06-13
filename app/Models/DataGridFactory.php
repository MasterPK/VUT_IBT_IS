<?php
declare(strict_types=1);

namespace App\Models;


use App\Controls\ExtendedFormContainer;
use App\Models\Orm\LikeFilterFunction;
use App\Models\Orm\Orm;
use Contributte\Translation\Exceptions\InvalidArgument;
use Contributte\Translation\Translator;
use DateTimeImmutable;
use Exception;
use Nette\Utils\Paginator;
use Nette;
use Nextras\Datagrid\Datagrid;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\IEntity;

class DataGridFactory
{
    private $orm;

    private $translator;

    public function __construct(Orm $orm, Translator $translator)
    {
        $this->orm = $orm;
        $this->translator = $translator;
    }


    /**
     * Get data for DataGrid from database with use of ORM. Supports filters, order, LIKE filtering, custom filtering, reset option for Select components and pagination.
     * @param string $repository Name of repository to use.
     * @param array $filter Array of filters in format key=>value. All will be converted to LIKE search.
     * @param array $order Array in format key=>value.
     * @param array $resetFilter Array in simple format. Items to be ignored if they are -1 in filter. Used for filtering select option ALL.
     * @param array $customFilter Add custom filter that will not be converted to LIKE search. Default operator is =.
     * @param Paginator|null $paginator Paginator instance.
     * @param array $customOrder Array of 2 elements of order. If $order is empty than use this. If size is not 2, or $order is not empty, than this is ignored.
     * @return IEntity[] Return collection of result data.
     * @throws Exception
     */
    public function createDataSource(string $repository, $filter, $order, array $resetFilter = [], array $customFilter = [], Paginator $paginator = null, array $customOrder = [])
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
            } else if (is_a($v, DateTimeImmutable::class)) {
                $filters["$k>="] = $v;
                $date = new Nette\Utils\DateTime($v->format("m/d/Y"));
                $date->modify("+ 1 day");
                $filters["$k<="] = $date;
            } else {
                array_push($filters, [LikeFilterFunction::class, $k, $v]);
            }
        }

        foreach ($customFilter as $key => $value) {
            if (is_array($value)) {
                array_push($filters, $value);
            } else {
                $filters[$key] = $value;
            }
        }

        if (count($customOrder) == 2 && !isset($order[0])) {
            $order[0] = $customOrder[0];
            $order[1] = $customOrder[1];
        }


        if (isset($order[0])) {
            $data = $this->orm->getRepositoryByName($repository)->findBy($filters)->orderBy($order[0], $order[1]);
        } else {
            $data = $this->orm->getRepositoryByName($repository)->findBy($filters);
        }

        if ($paginator != null) {
            $data = $data->limitBy($paginator->getItemsPerPage(), $paginator->getOffset());
        }

        return $data->fetchAll();

    }

    /**
     * Create Form container for DataGrid with filter and cancel button. Supports DateTime input.
     * @return ExtendedFormContainer New container with elements.
     * @throws InvalidArgument
     */
    public function createFilterForm()
    {
        $form = new ExtendedFormContainer();
        $form->addSubmit('filter', $this->translator->translate("all.filter"))->getControlPrototype()->class = 'btn btn-sm btn-primary m-1';
        $form->addSubmit('cancel', $this->translator->translate("all.cancel"))->getControlPrototype()->class = 'btn btn-sm btn-danger m-1';
        return $form;
    }

    /**
     * Create Form container for DataGrid with save and cancel button. Supports DateTime input.
     * @return ExtendedFormContainer New container with elements.
     * @throws InvalidArgument
     */
    public function createEditForm()
    {
        $form = new ExtendedFormContainer();
        $form->addSubmit('save', $this->translator->translate("all.save"))->getControlPrototype()->class = 'btn btn-sm btn-success m-1';
        $form->addSubmit('cancel', $this->translator->translate("all.cancel"))->getControlPrototype()->class = 'btn btn-sm btn-danger m-1';
        return $form;
    }

    /**
     * Create DataGrid component and set pagination,default template and translator.
     * @return Datagrid
     */
    public function createDataGrid()
    {
        $grid = new Datagrid();

        $grid->setPagination(10, function ($filter, $order) use ($grid) {
            return count((array)($grid->getDataSourceCallback())($filter, $order, null));
        });

        $grid->addCellsTemplate(__DIR__ . '/../Controls/templateDataGrid.latte');

        $grid->setTranslator($this->translator);

        return $grid;
    }


}