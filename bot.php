<?php
error_reporting(0);

function curl($url, $h, $post = 0, $proxy = 0){    
    $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_COOKIE,TRUE);
        if($proxy){
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
            curl_setopt($ch, CURLOPT_PROXYTYPE, "SOCKS5");

        }
        if($post){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        if($h){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
        }
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch);
        if(!$httpcode) return "Curl Error : ".curl_error($ch); else{
            $header = substr($response, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
            $body = substr($response, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
            curl_close($ch);
            return array($header, $body);
        }
}

if(!file_exists("Auth")){
    $auth = trim(readline("Authorization : "));
    file_put_contents("Auth", $auth);
}
function h(){
    $h = [
        'Host: app.despeed.net',
        'accept: */*',
        'accept-language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
        'authorization: '.file_get_contents('Auth'),
        'content-type: application/json',
        'origin: chrome-extension://ofpfdpleloialedjbfpocglfggbdpiem',
        'user-agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36'
    ];
    return $h;
}


system('clear');
echo "[+] Starting DeSpeed Validation Bot V1.0\n";
$ip = json_decode(shell_exec('curl --silent https://ipapi.co/json/'), 1);
while(true){
    $r = json_decode(curl('https://app.despeed.net/v1/api/speedtest-eligibility', h(), "{}")[1], 1)['data'];
    $claim = $r['isEligible'];
    if($claim){
        echo "[+] is Eligible Found, Preparing Claim\n";        
        $up = rand(100,150).".".rand(10,82);
        $down = rand(220,885).".".rand(22157836104435,52157836104435);
        $lat = $ip['latitude'] ? $ip['latitude'] : 0;
        $log = $ip['longitude'] ? $ip['longitude'] : 0;
        $data = '{"download_speed":'.$down.',"upload_speed":"'.$up.'","latitude":'.$lat.',"logitude":'.$log.'}';        
        echo "[+] Sending Data : \n";
        print_r(json_decode($data));
        echo "[+] Result Data : \n";
        print_r(json_decode(curl('https://app.despeed.net/v1/api/points', h(), $data)[1], 1));
        continue;
    }
    $next = $r['timing']['nextTime'];
    $time = strtotime($next)-time();
    if($time > 0){
        echo "[+] Last Claim ".$r['timing']['lastTime']." UTC \n";
        echo "[+] is Eligible NOT Found, Wait $time Seconds \n";
        for($i = $time; $i > 0; $i--){
            echo "Countdown ($i) for next claim \r";
            sleep(1);
        }
    }
}

