<?php
/**
 * Created by Maatify.dev
 * User: Maatify.dev
 * Date: 2023-05-08
 * Time: 10:35 AM
 * https://www.Maatify.dev
 */

namespace Maatify\Realm;

use Kreait\Firebase\Exception\DatabaseException;
use Maatify\Logger\Logger;

class RealmSms extends RealmDB
{
    private string $db_path = 'sms';
    private string $phone;
    private string $message;
    private int $ref;

    private const ETISALAT = 'etisalat';
    private const VODAFONE = 'vodafone';
    private const ORANGE = 'orage';
    private const WE = 'we';
    private const DEFAULT = 'default';

    private static self $instance;
    private string $sender;

    public static function obj(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
//        $this->json_file_credentials = __DIR__ . '';
//        $this->url = '';
        parent::__construct();
    }

    protected function Set(string $phone, string $message, int $ref = 0): self
    {
        $this->phone = $phone;
        $this->message = $message;
        $this->ref = $ref;
        return $this;
    }

    protected function EtisalatSender(): bool
    {
        $this->sender = self::ETISALAT;
        return $this->Send();
    }

    protected function VodafoneSender(): bool
    {
        $this->sender = self::VODAFONE;
        return $this->Send();
    }

    protected function OrangeSender(): bool
    {
        $this->sender = self::ORANGE;
        return $this->Send();
    }

    protected function WeSender(): bool
    {
        $this->sender = self::WE;
        return $this->Send();
    }

    protected function DefaultSender(): bool
    {
        $this->sender = self::DEFAULT;
        return $this->Send();
    }

    private function Send(): bool
    {

        if(empty($this->ref)){
            $this->ref = round(microtime(true) *1000);
        }

        try {
            $this->database->getReference($this->db_path)->push([
                'sender'  => $this->sender,
                'phone'   => $this->phone,
                'message' => $this->message,
                'ref'     => $this->ref,
            ]);
            return true;
        }catch (DatabaseException $exception){
            Logger::RecordLog($exception);
            return false;
        }

    }
}