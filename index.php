<?php 

$downloadFolder = "download";
delete_directory($downloadFolder);
mkdir($downloadFolder, 0777, true);

$urls = [];
$fileWithURLs = fopen("videos.txt", "r");
while (!feof($fileWithURLs)) {
	$current_line = fgets($fileWithURLs);
	$urls[] = trim(str_replace("\r\n","",$current_line));
}

$ind = 0;
unlink($downloadFolder . "/downloaded.txt");
$donloadedFile = fopen($downloadFolder . "/downloaded.txt", "w");
foreach ($urls as $url) {
	echo $url . "\n";
	$result = get_web_page( $url );
	$content = $result['content'];

	if (!$content) {
		echo "ups..";
		continiue;
	}

	$dom = new DOMDocument;
	$dom->loadHTML($content);
	$videos = $dom->getElementsByTagName('video');
	foreach ($videos as $video) {
	    $src = $video->getAttribute('src');
	    if (!$src) {
	    	echo "skipped...";
	    	continiue;
	    }
	    
	    $fileName = "file_" . $ind++ . ".mp4";
	    download($src, $downloadFolder . "/" . $fileName);
	    fwrite($donloadedFile, $fileName . "\n");
	    echo $fileName . " downloaded!\n";
	}
	sleep(5);
}
fwrite($donloadedFile, "file '../end.mp4'\n");
fclose($donloadedFile);

echo "\n\nStart compilation... \n";
unlink("compilation.mp4");
compile($downloadFolder . "/downloaded.txt");

function compile($downloadFile) {
    $paths = [];
    $files = fopen($downloadFile, "r");
    while (!feof($files)) {
        $current_line = fgets($files);
        $paths[] = trim(str_replace("\r\n","",$current_line));
    }

    $ffmpegCommand = "ffmpeg -y -loglevel warning ";
    foreach ($paths as $path) {
        $ffmpegCommand = $ffmpegCommand . "-i ".$path." ";
    }

    $ffmpegCommand = $ffmpegCommand . "-filter_complex ";
    $ffmpegCommand = $ffmpegCommand . "\"";

    $inc = 0;
    foreach ($paths as $path) {
        $ffmpegCommand = $ffmpegCommand . "[".$inc.":v]scale=1280:720:force_original_aspect_ratio=decrease,pad=1280:720:(ow-iw)/2:(oh-ih)/2,setsar=1,fps=30,format=yuv420p[v".$inc."]; ";
        $inc++;
    }
    $inc = 0;
    foreach ($paths as $path) {
        $ffmpegCommand = $ffmpegCommand . "[v".$inc."][".$inc.":a]";
        $inc++;
    }
    $ffmpegCommand = $ffmpegCommand . " concat=n=".$inc.":v=1:a=1[v][a]";
    $ffmpegCommand = $ffmpegCommand . "\"";
    $ffmpegCommand = $ffmpegCommand . " -map \"[v]\" -map \"[a]\" -c:v libx264 -c:a aac -movflags +faststart compilation.mp4";

    echo $ffmpegCommand . "\n\n";
    exec($ffmpegCommand);
}

function download($url, $output)
{
	$user_agent='5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.120 Safari/537.36';
	$output_filename = $output;
    $host = $url;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $host);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, false);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
    curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");

    $result = curl_exec($ch);
    curl_close($ch);
    // the following lines write the contents to a file in the same directory (provided permissions etc)
    $fp = fopen($output_filename, 'w');
    fwrite($fp, $result);
    fclose($fp);
}

function delete_directory($dirname) {
         if (is_dir($dirname))
           $dir_handle = opendir($dirname);
     if (!$dir_handle)
          return false;
     while($file = readdir($dir_handle)) {
           if ($file != "." && $file != "..") {
                if (!is_dir($dirname."/".$file))
                     unlink($dirname."/".$file);
                else
                     delete_directory($dirname.'/'.$file);
           }
     }
     closedir($dir_handle);
     rmdir($dirname);
     return true;
}

function get_web_page( $url )
    {
    	$user_agent='5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.120 Safari/537.36';
        $options = array(
            CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
            CURLOPT_POST           =>false,        //set to GET
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        );

        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;

        if ($err) {
        	echo $err . "\n";
        }
        if ($errmsg) {
        	echo $errmsg . "\n";
        }

        return $header;
    }

?>