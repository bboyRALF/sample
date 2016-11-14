<?php
include ('asset_management_catalog.php');
 $urls = array("http://wedups.s3.amazonaws.com/hezi67982%2F0545450951%D7%90%D7%9C%D7%99_%D7%91%D7%9C/gallery%2FDSC-001.JPG",
"http://wedups.s3.amazonaws.com/hezi67982%2F0545450951%D7%90%D7%9C%D7%99_%D7%91%D7%9C/gallery%2FDSC-002.JPG",
"http://wedups.s3.amazonaws.com/hezi67982%2F0545450951%D7%90%D7%9C%D7%99_%D7%91%D7%9C/gallery%2FDSC-003.JPG",
"http://wedups.s3.amazonaws.com/hezi67982%2F0545450951%D7%90%D7%9C%D7%99_%D7%91%D7%9C/gallery%2FDSC-004.JPG",
"http://wedups.s3.amazonaws.com/hezi67982%2F0545450951%D7%90%D7%9C%D7%99_%D7%91%D7%9C/gallery%2FDSC-005.JPG");
 
	foreach($urls as $url){
//Получаем название картинки, в итоге будет к примеру 123.jpg 
        $tmp = strtolower(strrchr($url,"/"));
        $pic_name = substr($tmp,1);

        $ch = curl_init($url);

        //Конечный файл:
        $file = "assetroot/".$pic_name;
        $save = fopen($file,"w");

        curl_setopt($ch, CURLOPT_HEADER, 0);
        $useragent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:6.0.2) Gecko/20100101 Firefox/6.0.2";
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        //Сохраняем файл:
        curl_setopt($ch, CURLOPT_FILE, $save);

        curl_exec($ch);
        curl_close($ch);

        fclose($save);
}


 scanPath(kAssetRootFilePath, '');

echo "Finished\n";
