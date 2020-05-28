<?php declare(strict_types=1);

namespace App\Models;

use Nette;
use Contributte;
use Nette\Security\Permission;
use Nittro;
use App\Controls;

class BasePresenter extends Nittro\Bridges\NittroUI\Presenter
{

    /** @persistent */
    public $locale;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    /** @var Contributte\Translation\LocalesResolvers\Router @inject */
    public $translatorSessionResolver;


    /** @var String Type of alert. */
    public $alertState;

    /** @var String Text in alert. */
    public $alertText;



    /**
     * Create universal alert box.
     * @return Controls\AlertControl  Return new component.
     */
    public function createComponentAlert(): Controls\AlertControl
    {
        return new Controls\AlertControl($this->alertText, $this->alertState);
    }

    /**
     * Show toast notification by iziToast library.
     * Currently support only basic options key => value (string).
     * Support only AJAX requests.
     * Example:
     * iziToast.show({
     *  title: 'Hey',
     *  message: 'What would you like to add?'
     *  });
     * @param array $options Holds toast options. Array has to has same syntax like original library. See documentation at https://izitoast.marcelodolza.com/.
     * Implicitly sets position of toast at top right. You cannot change this because this position is best for template.
     */
    public function showToast(array $options)
    {
        if (!$this->isAjax() || $options == null || empty($options)) {
            return;
        }

        $html = "iziToast.show({";
        $html.= "position: 'bottomRight',";
        foreach ($options as $option => $value) {
            $html .= $option . ":\"" . $value . "\",";
        }
        $html .= "});";

        $this->template->toastHTML = $html;
        $this->redrawControl("toastSnippet");

    }

    /**
     * Helper function to easily show toast notification.
     * You can only specify message.
     * For full options use function showToast.
     * @param string $message Message to be displayed.
     */
    public function showDangerToast(string $message)
    {
        $this->showToast(["color"=>"red","message"=>$message]);
    }

    /**
     * Helper function to easily show toast notification.
     * You can only specify message.
     * For full options use function showToast.
     * @param string $message Message to be displayed.
     */
    public function showSuccessToast(string $message)
    {
        $this->showToast(["color"=>"green","message"=>$message]);
    }

    protected function startup()
    {
        parent::startup();

        $this->setDefaultSnippets(['all']);
    }

    protected function translate($value): string
    {
        return $this->translator->translate($value);
    }

    protected function afterRender()
    {
        parent::afterRender();

        // Set variable with language info
        if($this->getParameter("locale") == null || $this->getParameter("locale") == "cs")
        {
            $this->template->locale=true;
        }
        else
        {
            $this->template->locale=false;
        }
    }

    public function handleChangeLocale($locale)
    {
        $this->showToast(["message"=>$this->translate("messages.main.global.localeChanged"),"color"=>"green"]);
        $this->redrawControl("all");
    }


}