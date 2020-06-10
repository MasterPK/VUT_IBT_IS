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

}