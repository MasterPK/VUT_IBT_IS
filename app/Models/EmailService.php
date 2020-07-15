<?php


namespace App\Models;

use Nette;
use Nette\Database\Context;
use Nette\Mail\IMailer;
use Nette\Mail\Message;

/**
 * Class EmailService
 * Provides functions to handle emails.
 * @package App\Models
 * @author Petr Křehlík
 */
class EmailService
{

    private $mailer;
    private $database;

    public function __construct(IMailer $mailer, Context $database)
    {
        $this->mailer = $mailer;
        $this->database = $database;
    }

    /**
     * Add email to queue.
     * Email will be send in next iteration of handle (every minute).
     * @param string $to Email where to send
     * @param string $header Subject of email
     * @param string $content Content of email
     */
    public function sendEmail($to, $header, $content)
    {
        $this->database->table("EmailQueue")->insert([
            "Email" => $to,
            "Header" => $header,
            "Content" => $content,
            "Try" => 0
        ]);
    }

    /**
     * Function try to send all emails in queue.
     * If email has 5 times error then delete it.
     * @return int Count of errors. If zero then no errors.
     */
    public function handle():int
    {
        $tableResult = $this->database->table("EmailQueue")->fetchAll();

        $err_count = 0;
        foreach ($tableResult as $row) {
            $mail = new Message;
            $mail->setFrom('noreply <dochazkovysystem@gmail.com>')
                ->addTo($row->Email)
                ->setSubject($row->Header)
                ->setBody($row->Content);
            try {
                $this->mailer->send($mail);
                $this->database->table("EmailQueue")->where("ID", $row->ID)->delete();
            } catch (Nette\Mail\SendException $exception) {
                $err_count++;
                if($row->Try==5)
                {
                    $this->database->table("EmailQueue")->where("ID", $row->ID)->delete();
                    continue;
                }
                $this->database->table("EmailQueue")->where("ID", $row->ID)->update(["Try" => ++$row->Try]);

            }
        }
        return $err_count;
    }

}