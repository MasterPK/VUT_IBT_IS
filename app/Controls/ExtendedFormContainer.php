<?php


declare(strict_types=1);

namespace App\Controls;

use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Vodacek\Forms\Controls\DateInput;


class ExtendedFormContainer extends Container
{
    public function addDate($name, $title = "My input", $type = DateInput::TYPE_DATETIME_LOCAL)
    {
        return $this[$name] = new DateInput($title, $type);
    }

    public function addId()
    {
        $this->addText('id')
            ->setHtmlType('number')
            ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER);
    }
}