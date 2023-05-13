<?php
/**
 * Created by Maatify.dev
 * User: Maatify.dev
 * Date: 2023-03-21
 * Time: 10:11 AM
 */

namespace Maatify\Realm;

use Maatify\Logger\Logger;

abstract class FCM
{
    private string $api_key;

    private array $fields;

    public function __construct(string $api_key)
    {
        $this->api_key = $api_key;
        return $this;
    }

    private function SendTopicOnly(string $message, string|int $topic,int $type){
        $this->fields = [
            'to' => "/topics/" . ($topic ? : "all"),
            'data' => array(
                'target' => ($type == 1 ? $topic : 'user'),
                'message' => $message,
                'type' => ($type ? : 1),
                'user' => ($type == 1 ? false : $topic)
            )
        ];
        return $this->Send();
    }

    public function SendTopicsNotification(string $message, string|int $topic,int $type)
    {
        $this->fields = [
            'to' => "/topics/" . ($topic ? : "all"),
            'notification' => array('title' => ($type == 1 ? $topic : 'user'), 'body' => $message,'sound' => 'default'),
            'data' => array(
                'target' => ($type == 1 ? $topic : 'user'),
                'message' => $message,
                'type' => ($type ? : 1),
                'user' => ($type == 1 ? false : $topic)
            ),
        ];
        return $this->Send();
    }

    public function SendDeviceIDNotification(string $device_token, string $title, string $message)
    {
        $this->fields = [
            'registration_ids' => $device_token,
            'notification' => array('title' => $title, 'body' => $message,'sound' => 'default'),
            'data' => array('Title' => $title, 'Message' => $message),
        ];
        return $this->Send();
    }

    private function Send(){
        //Google Firebase messaging FCM-API url
        $url = 'https://fcm.googleapis.com/fcm/send';

        $headers = array(
            'Authorization: key=' . $this->api_key,
            'Content-Type: application/json'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->fields));
        $result = curl_exec($ch);

        if ($result === FALSE) {
            $res = false;
            $err = ('Curl failed: ' . curl_error($ch));
        }else{
            $res = true;
            $err = false;
        }
        curl_close($ch);
        if($res){
            return json_decode($result, true);
        }else{
            Logger::RecordLog($err, __CLASS__ . '_err');
            return $err;
        }
    }
}