<?php


declare(strict_types=1);

namespace App\Controls;

use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Vodacek\Forms\Controls\DateInput;

/**
 * Class ExtendedFormContainer
 * Extends form by DateTime elements
 * @package App\Controls
 * @author Petr Křehlík
 */
class ExtendedFormContainer extends Container
{
    public function addDate($name, $title = "My input", $type = DateInput::TYPE_DATETIME_LOCAL)
    {
        return $this[$name] = new DateInput($title, $type);
    }

    public function addId($name="id")
    {
        $this->addText($name)
            ->setHtmlType('number')
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER);
    }

    public function addDateTimeRange($name, $type)
    {
        $this->addComponent(new ExtendedFormContainer(),$name);
        $this[$name]->addDate('from',null,$type)->setHtmlAttribute("class", "form-control");
        $this[$name]->addDate('to',null,$type)->setHtmlAttribute("class", "form-control");
    }
}