<?php


namespace App\Controls;
use Nette;


class AlertControl extends Nette\Application\UI\Control
{
    public const SUCCESS = "Success";
    public const DANGER = "Danger";

    private $message;
    private $type;

    /**
     * AlertControl constructor.
     * @param $type mixed of alert by Bootstrap definitions(you need to write first letter as upper-case, e.g.: Success). If type is not specified, then component returns empty template.
     * @param $message mixed Text to be displayed as body of alert,
     */
    public function __construct($message,$type="")
    {
        if($type == null)
        {
            $this->type="";
        }
        else
        {
            $this->type=$type;
        }
        if($message == null)
        {
            $this->message="";
        }
        else
        {
            $this->message=$message;
        }


    }

    public function render(): void
    {
        $this->template->message = $this->message;
        $this->template->render(__DIR__ . '/AlertControl'.$this->type.'.latte');
    }
}