<?php

require_once('DataLayer.php');

/**
 * Class MailAbstract
 */
abstract class MailAbstract
{
    /**
     * @var boolean
     */
    protected $debug;

    /**
     * @return array
     */
    abstract function getCustomerList();

    /**
     * @return string
     */
    abstract function getMailSubject();

    /**
     * @param $email string
     * @return string
     */
    abstract function getMailBody($email);

    /**
     *
     */
    public function sendMail()
    {
        $customers = $this->getCustomerList();

        $isDebugMode = $this->isDebugMode();

        foreach ($customers as $customer) {
            //Add customer to reciever list
            $to = $customer->email;
            //Add subject
            $subject = $this->getMailSubject();
            //Send mail from info@cdon.com
            $from = $this->getFrom();

            $headers = $headers = "From: " . $from . "\r\n";

            //Add body to mail
            $body = $this->getMailBody($to);

            if ($isDebugMode) {
                $this->logResult("Send mail to:" . $to, 'DEBUG');
            } else {
                $result = mail($to, $subject, $body, $headers);

                if ($result == false) {
                    $this->logResult("Can not send mail to:" . $to, "WARN");
                } else {
                    $this->logResult("Sent mail to:" . $to, "SUCCESS");
                }
            }
        }
    }

    /**
     * @param $msg string
     * @param $type string
     */
    private function logResult($msg, $type)
    {
        $fileName = (new DateTime())->format('Y-m-d');

        $dirName = __DIR__ . "/logs/";

        @mkdir($dirName, 755, true);

        $fileName = $dirName . $fileName . ".txt";

        $msg = date("M j G:i:s Y") . "\t" . $type . ": " . $msg . "\n";

        if (!file_exists($fileName)) {
            file_put_contents($fileName, $msg);
        } else {
            file_put_contents($fileName, $msg, FILE_APPEND);
        }
    }

    /**
     * @return string
     */
    protected function getFooter()
    {
        return "<br>Best Regards,<br>Roman Rybachok";
    }

    /**
     * @return string
     */
    protected function getFrom()
    {
        return "info@rybachok.com";
    }


    /**
     * @return bool mode of application
     */
    private function isDebugMode()
    {
        global $argv;
        
        if (isset($argv[1])) {
            return (boolean)$argv[1];
        }

        return false;
    }
}

/**
 * Class NewCustomersMail
 */
class NewCustomersMail extends MailAbstract
{

    /**
     * @return array
     */
    function getCustomerList()
    {
        //List all customers
        $all = DataLayer::ListCustomers();
        $newCustomers = array();

        foreach ($all as $customer) {
            if ($customer > (new DateTime())->modify('-1 day')) {
                $newCustomers[] = $customer;
            }
        }

        return $newCustomers;
    }

    /**
     * @return string
     */
    function getMailSubject()
    {
        return "Welcome as a new customer";
    }

    /**
     * @param $email
     * @return string
     */
    function getMailBody($email)
    {
        return "Hi " . $email . "<br>We would like to welcome you as customer on our site!<br>" . $this->getFooter();
    }
}

/**
 * Class InactiveCustomersMail
 */
class InactiveCustomersMail extends MailAbstract
{

    /**
     * @return array
     */
    function getCustomerList()
    {
        //List all customers
        $allCustomers = DataLayer::ListCustomers();
        //List all orders
        $allOrders = DataLayer::ListOrders();

        $list = array();

        foreach ($allCustomers as $customer) {
            $isActive = false;

            foreach ($allOrders as $order) {
                if ($customer->email == $order->customerEmail) {
                    $isActive = true;
                    break;
                }
            }

            if (!$isActive) {
                $list[] = $customer;
            }
        }

        return $list;
    }

    /**
     * @return string
     */
    function getMailSubject()
    {
        return "We miss you as a customer";
    }

    /**
     * @param $email
     * @return string
     */
    function getMailBody($email)
    {
        return "Hi " . $email . "<br>We would like to welcome you as customer on our site!<br>" . $this->getFooter();
    }
}

//usage:
$newCustomersSender = new NewCustomersMail();
$newCustomersSender->sendMail();

$inactiveCustomersSender = new InactiveCustomersMail();
$inactiveCustomersSender->sendMail();

