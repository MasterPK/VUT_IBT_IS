<?php declare(strict_types=1);

namespace App\MainModule\CorePresenters;

use App\Models\Orm\Orm;
use Nette;
use Contributte;
use Nette\Security\Permission;
use Nittro;
use App\Controls;

class BasePresenter extends Nittro\Bridges\NittroUI\Presenter
{

    /** @persistent */
    public $locale;

    /** @var Contributte\Translation\Translator */
    protected $translator;

    /** @var Contributte\Translation\LocalesResolvers\Router @inject */
    public $translatorSessionResolver;


    /** @var String Type of alert. */
    public $alertState;

    /** @var String Text in alert. */
    public $alertText;

    /** @var Orm @inject */
    public $orm;

    /** @var Nette\Database\Context @inject */
    public $database;

    public function __construct(Nette\Localization\ITranslator $translator)
    {
        parent::__construct();
        $this->translator=$translator;
    }


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
     * @param bool $refreshAll If true, whole page will be refreshed. Useful for refresh data on page.
     */
    public function showToast(array $options, bool $refreshAll=false)
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
        if($refreshAll)
        {
            $this->redrawControl("all");
        }else
        {
            $this->redrawControl("toastSnippet");
        }


    }

    /**
     * Helper function to easily show toast notification.
     * You can only specify message.
     * For full options use function showToast.
     * @param string $message Message to be displayed. If not specified, use generic message.
     * @param bool $refreshAll @deprecated If true, whole page will be refreshed. Useful for refresh data on page.
     */
    public function showDangerToast(string $message=null, bool $refreshAll=false)
    {
        if($message==null)
        {
            $message=$this->translate("all.error");
        }
        $this->showToast(["color"=>"red","message"=>$message],$refreshAll);
    }

    /**
     * Helper function to easily show toast notification.
     * You can only specify message.
     * For full options use function showToast.
     * @param string $message Message to be displayed. If not specified, use generic message.
     */
    public function showSuccessToastAndRefresh(string $message=null)
    {
        if($message==null)
        {
            $message=$this->translate("all.success");
        }
        $this->showToast(["color"=>"green","message"=>$message],true);
    }

    /**
     * Helper function to easily show toast notification.
     * You can only specify message.
     * For full options use function showToast.
     * @param string $message Message to be displayed. If not specified, use generic message.
     */
    public function showDangerToastAndRefresh(string $message=null)
    {
        if($message==null)
        {
            $message=$this->translate("all.error");
        }
        $this->showToast(["color"=>"red","message"=>$message],true);
    }

    /**
     * Helper function to easily show toast notification.
     * You can only specify message.
     * For full options use function showToast.
     * @param string $message Message to be displayed. If not specified, use generic message.
     * @param bool $refreshAll @deprecated If true, whole page will be refreshed. Useful for refresh data on page.
     */
    public function showSuccessToast(string $message=null, bool $refreshAll=false)
    {
        if($message==null)
        {
            $message=$this->translate("all.success");
        }
        $this->showToast(["color"=>"green","message"=>$message],$refreshAll);
    }

    protected function startup()
    {
        parent::startup();

        if($this->alertState==null)
        {
            $this->alertState=$this->getParameter("alertState");
        }

        if($this->alertText==null)
        {
            $this->alertText=$this->getParameter("alertText");
        }

        $this->setDefaultSnippets(['all',"scripts"]);

        if($this->getParameter("locale") == null || $this->getParameter("locale") == "cs")
        {
            setlocale(LC_TIME, "cs_CZ.utf8") or die('Locale not installed');;
        }
        else
        {
            setlocale(LC_TIME, "en_GB.utf8") or die('Locale not installed');;
        }

        $this->setDefaultSnippets(["all","content"]);
    }

    protected function translate($value): string
    {
        return $this->translator->translate($value);
    }

    protected function translateAll($value): string
    {
        return $this->translator->setPrefix(["all"])->translate($value);;
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