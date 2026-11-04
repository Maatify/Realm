<?php
/**
 * Created by Maatify.dev
 * User: Maatify.dev
 * Date: 2023-03-21
 * Time: 10:11 AM
 */

namespace Maatify\Realm;

use Maatify\Logger\Logger;

/**
 * Sends notifications through the legacy FCM HTTP API (`/fcm/send`, server key in a header).
 *
 * That API has been retired by Google in favor of the HTTP v1 API with OAuth 2.0; prefer the
 * messaging component of `kreait/firebase-php`, which this package already requires. The TLS
 * certificate and host name of the server are verified.
 *
 * @deprecated
 */
abstract class FCMDeprecated
{
    protected string $api_key;

    private array $fields;

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
            'registration_ids' => [$device_token],
            'notification' => array('title' => $title, 'body' => $message,'sound' => 'default'),
            'data' => array('Title' => $title, 'Message' => $message),
        ];
        return $this->Send();
    }

    /** The legacy FCM HTTP API URL. A subclass may override it, for example to target a local test double. */
    protected function endpoint(): string
    {
        return 'https://fcm.googleapis.com/fcm/send';
    }

    private function Send(){
        $url = $this->endpoint();

        $headers = array(
            'Authorization: key=' . $this->api_key,
            'Content-Type: application/json'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // The server key travels in a header: only send it over a connection whose certificate and
        // host name were verified.
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
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
            Logger::RecordLog(message: $err, logFile: __CLASS__ . '_err');
            return $err;
        }
    }
}