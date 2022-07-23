<?php

namespace EDI\Generator;

use Carbon\Carbon;

/**
 * Class Interchange
 * @package EDI\Generator
 */
class Interchange
{
    private $interchangeCode;
    private $sender;
    private $receiver;
    private $date;
    private $time;
    private $charset;

    private $messages;
    private $composed;

    /**
     * Interchange constructor.
     * @param $sender
     * @param $receiver
     * @param null $date
     * @param null $time
     * @param null $interchangeCode
     */
    public function __construct($sender, $receiver, $date = null, $time = null,$dateTime = null ,$interchangeCode = null)
    {
        $this->messages = [];

        if ($interchangeCode === null) {
            $this->interchangeCode = 'I' . strtoupper(uniqid());
        } else {
            $this->interchangeCode = $interchangeCode;
        }

        $this->sender = $sender;
        $this->receiver = $receiver;

        if ($date === null) {
            $this->date = date('ymd');
        } else {
            $this->date = $date;
        }
        if ($time === null) {
            $this->time = date('Hi');
        } else {
            $this->time = $time;
        }

        if ($dateTime == null){
            $this->dateTime = date('YmdHi');
        }
        else{
            $this->dateTime = $dateTime;
        }

        $this->charset = ['UNOA', 2];
    }

    /**
     * Change the default character set
     * $identifier Syntax identifier
     * $version Syntax version
     * @param $identifier
     * @param $version
     * @return $this
     */
    public function setCharset($identifier, $version)
    {
        $this->charset = [$identifier, $version];

        return $this;
    }

    /**
     * Add a Message to the Interchange
     * @param $msg
     * @return $this
     */
    public function addMessage($msg)
    {
        $this->messages[] = $msg;

        return $this;
    }

    /**
     * Format the Interchange segments
     * @return $this
     */
    public function compose()
    {
        $temp = [];
        $temp[] = ['UNB', $this->charset, [$this->sender,14], [$this->receiver,14] , [$this->date, $this->time], $this->dateTime];
        foreach ($this->messages as $msg) {
            foreach ($msg->getComposed() as $i) {
                $temp[] = $i;
            }
        }
        $temp[] = ['UNZ', (string)count($this->messages), $this->dateTime];
        $this->composed = $temp;

        return $this;
    }

    /**
     * Return composed message as array
     * @return array
     */
    public function getComposed()
    {
        if ($this->composed === null) {
            $this->compose();
        }

        return $this->composed;
    }
}
