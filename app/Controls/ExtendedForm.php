<?php
declare(strict_types=1);

namespace App\Controls;


use Nette\Application\UI\Form;
use Vodacek\Forms\Controls\DateInput;

class ExtendedForm extends Form
{
    public function addDate($name, $title = "My input",$type=DateInput::TYPE_DATETIME_LOCAL)
    {
        return $this[$name] = new DateInput($title,$type);
    }
    public function addId()
    {
        $this->addText('id')
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