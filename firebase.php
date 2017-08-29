<?php

class firebase {
    public function send($registration_id,$message){
        $fields = array(
            'registration_ids' => $registration_id,
            'data' => $message
        );
        return $this->sendPushNotification($fields);
    }

    private function sendPushNotification($fields){
        require_once 'config.php';
        $url = 'https://fcm.googleapis.com/fcm/send';
        $headers = array(
            'Authorization: key='.FIREBASE_API_KEY,
            'Content-type: application/json'
        );

        // initializing curl to open a connectiona
        $ch = curl_init();

        // setting curl url
        curl_setopt($ch,CURLOPT_URL,$url);

        // setting post method
        curl_setopt($ch,CURLOPT_POST,true);

        // adding header
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

        // disabling ssl support
        curl_setopt($ch,CURLOPT_SSL_VERIVYPEER,false);

        // adding fields in json format
        curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($fields));

        // execute curl request
        $result = curl_exec($ch);
        if ($result == FALSE) {
            die('curl failed: '.curl_error($ch));
        }

        curl_close($ch);
        return $result;
    }
}
