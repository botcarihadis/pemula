<?php
$token = "TOKEN_BOT_KAMU";

// BIARKAN KODE DI BAWAH INI, JANGAN DIUBAH KALAU BELUM PAHAM
$tg = "https://api.telegram.org/bot".$token;
$updates = json_decode(file_get_contents("php://input"),true);

//kondisi
if(isset($updates['message']['text'])){//kalau ada pesan teks masuk
    $pesan = $updates['message']['text'];
    
    $pesan = str_replace("'","\'",$pesan);
    
    $find = array("َ","ِ","ُ","ً","ٍ","ٌ","ْ","ّ");
    
    $pesan = str_replace($find,"",$pesan);
    
    $length = strlen($pesan);
    
    $first_name = $updates['message']['chat']['first_name'];
    
    isset($updates['message']['chat']['last_name'])
    ?$last_name= $updates['message']['chat']['last_name']
    :$last_name='';
    
    $name = $first_name.' '.$last_name;
    
    isset($updates['message']['chat']['username'])
    ?$username = $updates['message']['chat']['username']
    :$username='';
    
    $chat_id = $updates['message']['chat']['id'];
    
    $message_id = $updates['message']['message_id'];
    
    //start
    if($pesan == "/start"){
        //kalau pesannya "/start"
        sedangMengetik();
        $pesan_balik = "Assalamualaikum {$name}\n\nSelamat datang di bot Cari Hadis. Silahkan tulis kata atau kalimat yang ingin anda cari.";
    }elseif($pesan == "/kitab"){
        //kalau pesannya "/kitab"
        sedangMengetik();
        $get = json_decode(sedothtml("http://api2.carihadis.com"),true);
        $count = count($get['kitab']);
        if($count>0){
            $pesan_balik = "Tersedia {$count} kitab:\n";
            for($x=0;$x<$count;$x++){
            $kitab = $get['kitab'][$x];
            $pesan_balik .= $x + 1 .". /{$kitab}\n";
            }
        }
    }elseif($pesan == "/keterangan"){
            $pesan_balik = "Keterangan\n\n1. Mesin akan mencari kata kunci yang anda masukkan, tanpa melihat apa karakter sebelumnya atau sesudahnya. Misalnya, jika anda memasukkan kata kunci \"makan\", maka mesin akan mencari kata \"makan\", \"memakan\", \"dimakan\", \"disamakan\", \"makanan\" dan sebagainya.\n\n2. Urutan kata menentukan hasil pencarian. Misalnya, \"kaki dan tangan\" akan memberikan hasil yang berbeda dengan \"tangan dan kaki\".\n\n3. Untuk pencarian menggunakan kata kunci berbahasa Arab, mesin akan membedakan antara hamzah washol (ا) dan hamzah qotho (أ إ ء).";
    }elseif(preg_match("/^\/__([a-zA-Z_]+)__(\d+)/",$pesan,$ke)){
        //kalau format pesannya /__Nama_Kitab__123 (bernomor)
        sedangMengetik();
        $kitab = $ke[1];
        $id = $ke[2];
        $get = json_decode(sedothtml("http://api2.carihadis.com/?kitab=".$kitab."&id=".$id),true);
        $count = count($get['data']);
        if($count>0){
            $id = $get['data'][1]['id'];
            $nass = $get['data'][1]['nass'];
            $terjemah = $get['data'][1]['terjemah'];
            $link = "<a href='https://carihadis.com/{$kitab}/{$id}'>{$kitab}: {$id}</a>";
            $pesan_balik = "{$link}\n\n{$nass}\n\n{$terjemah}\n({$link})";
        }else{
            $pesan_balik = "Data tidak ditemukan. Periksa nama kitab dan nomor secara benar.";
        }
        
    }elseif(preg_match('/^\/([a-zA-Z_]+)(\d+)?/',$pesan,$ke)){
        //kalau format pesannya /Nama_Kitab (tanpa nomor) atau /Nama_Kitab123 (bernomor)
        sedangMengetik();
        $kitab = $ke[1];
        if(!isset($ke[2])){
            $get = json_decode(sedothtml("http://api2.carihadis.com/?kitab=".$kitab."&id=1"),true);
        }else{
            $get = json_decode(sedothtml("http://api2.carihadis.com/?kitab=".$kitab."&id=".$ke[2]),true);
        }
        $count = count($get['data']);
        if($count>0){
            $id = $get['data'][1]['id'];
            $nass = $get['data'][1]['nass'];
            $terjemah = $get['data'][1]['terjemah'];
            $link = "<a href='https://carihadis.com/{$kitab}/{$id}'>{$kitab}: {$id}</a>";
            $pesan_balik = "{$link}\n\n{$nass}\n\n{$terjemah}\n({$link})";
        }else{
            $pesan_balik = "Data tidak ditemukan. Periksa nama kitab dan nomor secara benar.";
        }
    }
    else{
        //kalau pesannya teks bebas
        sedangMengetik();
        $json = cek($pesan);
        $get = json_decode($json,true);
        
        is_array($get['data'])
        ?$count = count($get['data'])
        :$count = 0;
        
        
        if($count>0){//kalau ada hasil
            $pesan_balik = "Anda mencari: ".$pesan."\n\nDitemukan hasil sebagai berikut:\n\n";
            $x=0;
            while($x<$count){
                $kitab = $get['data'][$x]['kitab'];
                $id = $get['data'][$x]['id'];
                $jml = count($id);
                $x++;
                $i=0;
                while($i<$jml){
                    $pesan_balik .= "/__".$kitab."__".$id[$i]."\n";
                    $i++;
                }
            }
            $pesan_balik .= "\nSilahkan tekan link di atas untuk membukanya.";
        }else{
            //kalau tidak ada hasil
            $pesan_balik = "Tidak ditemukan hasil.";
        }
        
    }
    kirim($pesan_balik,$chat_id,$message_id);
}


    //fungsi2
function cek($pesan){
    $url = "http://api2.carihadis.com/?q=".urlencode($pesan);
        $ch = curl_init();
        curl_setopt_array($ch,[
    CURLOPT_URL=>$url,
    #CURLOPT_HTTPHEADER=>$headers,
    #CURLOPT_POST=>1,
    #CURLOPT_POSTFIELDS=>$postfields,
    CURLOPT_RETURNTRANSFER=>true
    ]);
        $json = curl_exec($ch);
        curl_close($ch);
        return $json;
}

function kirim($pesan_balik,$chat_id,$message_id){
    
    $pesan_balik = strip_tags(preg_replace("/<\/p\>|<\s+?\/?br\s+?\>/i","\n\n",preg_replace("/<title>(.*)<\/title>/s","",$pesan_balik)),"<a><b><i><u>");
    
    if(strlen($pesan_balik)<4096){
        
        kirimpesan($pesan_balik);
    }else{
        
        $array = potong($pesan_balik,4096);
        
        foreach($array as $potongan){
            
            kirimpesan($potongan);
            
        }
        
    }
    
}

function kirimpesan($potongan){
    global $token;
    global $chat_id;
    global $message_id;
     
    #$potongan = urlencode($potongan);
     
    $headers = ["Content-Type" => "application/x-www-form-urlencoded"];
     
    $postfields = [
         "chat_id" => $chat_id,
         "text" => $potongan,
         "parse_mode" => "HTML",
         "reply_to_message_id" => $message_id
         ];
         
    $method = "sendMessage";
     
    proses($method,$postfields,$headers);
    
}

function sedangMengetik(){
    global $chat_id;
    $method = "sendChatAction";
    $postfields = [
        "chat_id" => $chat_id,
        "action" => "typing"
        ];
    proses($method,$postfields);
}

function proses($method,$postfields,$headers=[]){
    
    global $token;
    $ch = curl_init();
     
    curl_setopt($ch, CURLOPT_URL,"https://api.telegram.org/bot$token/$method");
     
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
     
    curl_setopt($ch, CURLOPT_POST, 1); 
     
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
     
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
     
    curl_exec($ch);
     
    curl_close ($ch);
}

    function pecah($text,$jml_kar){
        $karakter = $text[$jml_kar];
        while($karakter != ' ' AND $karakter != "\n") {//kalau bukan spasi atau new line
            $karakter = $text[--$jml_kar];//cari spasi sebelumnya
        }
        $pecahan = substr($text, 0, $jml_kar);
        return trim($pecahan);
    }

    function potong($text,$jml_kar){
        $panjang = strlen($text);
        $ke = 0;
        $pecahan = [];
        while($panjang>$jml_kar){
            $pecahan[] = pecah($text,$jml_kar);//str
            $panjang = strlen($pecahan[$ke]);//int
            $text = trim(substr($text,$panjang));//str
            $panjang = strlen($text);//int
            $ke++;//int
        }
        $array = array_merge($pecahan, array($text));
        return $array;
    }
    
function sedothtml($url,$postfields=[],$headers=[]){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    $html = curl_exec($ch);
    curl_close ($ch);
    return $html;#string
}
 
?>