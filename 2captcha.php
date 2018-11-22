<?php
/*
$filename - File name and local path. URL is not supported.
$apikey   - Your API key
$rtimeout - timeout of answer check
$mtimeout - max waiting time of answer

$is_verbose - false(commenting OFF),  true(commenting ON)

Additional captcha settings:
$is_phrase - 0 OR 1 - captcha contains two or more words
$is_regsense - 0 OR 1 - case sensitive captcha
$is_numeric -  0 OR 1 OR 2 OR 3
0 = parameter is not used (default value)
1 = captcha contains numbers only
2 = captcha contains letters only
3 = captcha contains numbers only or letters only
$min_len    -  0 - unlimited, otherwise sets the max length of the answer
$max_len    -  0 - unlimited, otherwise sets the min length of the answer
$language   - 0 OR 1 OR 2
0 = parameter is not used (default value)
1 = cyrillic captcha
2 = latin captcha

usage examples:
$text=recognize("captcha.jpg","YOUR_KEY_HERE",true, "2captcha.com");

$text=recognize("/path/to/file/captcha.jpg","YOUR_KEY_HERE",false, "2captcha.com");

$text=recognize("/path/to/file/captcha.jpg","YOUR_KEY_HERE",false, "2captcha.com",1,0,0,5);

*/



function recognize(
            $filename,
            $apikey,
            $rtimeout = 30,
            $mtimeout = 300,
            $is_verbose = true,
            $is_phrase = 0,
            $is_regsense = 0,
            $is_numeric = 2,
            $min_len = 0,
            $max_len = 16,
            $language = 2
            )
{
  $domain="2captcha.com";

  if (!file_exists($filename)) {
    if ($is_verbose) echo "file $filename not found\n";
    return false;
  }

  if (function_exists('curl_file_create')) { // php 5.5+
    $cFile = curl_file_create($filename, mime_content_type($filename), 'file');
  } else { //
    $cFile = '@' . realpath($filename);
  }

  $postdata = array(
      'file'      => $cFile,
      'key'       => $apikey,
      'phrase'    => $is_phrase,
      'regsense'  => $is_regsense,
      'numeric'   => $is_numeric,
      'min_len'   => $min_len,
      'max_len'   => $max_len,
      'language'  => $language,
      'lang'      => "en"
  );

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,             "http://$domain/in.php");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,  1);
  curl_setopt($ch, CURLOPT_TIMEOUT,         60);
  curl_setopt($ch, CURLOPT_POST,            1);
  curl_setopt($ch, CURLOPT_POSTFIELDS,      $postdata);

  $result = curl_exec($ch);

  if (curl_errno($ch)) {
    if ($is_verbose) echo "CURL returned error: " . curl_error($ch)."\n";
      return false;
  }

  curl_close($ch);

  if (strpos($result, "ERROR")!==false) {
    if ($is_verbose) echo "server returned error: $result\n";
      return false;
  }
  else {
      $ex = explode("|", $result);
      $captcha_id = $ex[1];
      if ($is_verbose) echo "captcha sent, got captcha ID $captcha_id\n";
      $waittime = 0;
      if ($is_verbose) echo "waiting for $rtimeout seconds\n";
      sleep($rtimeout);
      while(true) {
          $result = file_get_contents("http://$domain/res.php?key=".$apikey.'&action=get&id='.$captcha_id);
          if (strpos($result, 'ERROR')!==false) {
            if ($is_verbose) echo "server returned error: $result\n";
              return false;
          }
          if ($result=="CAPCHA_NOT_READY") {
            if ($is_verbose) echo "captcha is not ready yet\n";
            $waittime += $rtimeout;
            if ($waittime>$mtimeout) {
              if ($is_verbose) echo "timelimit ($mtimeout) hit\n";
              break;
            }
          if ($is_verbose) echo "waiting for $rtimeout seconds\n";
            sleep($rtimeout);
          }
          else {
            $ex = explode('|', $result);
            if (trim($ex[0])=='OK') {
              $captcha = trim($ex[1]);
              if ($is_verbose) echo "Got captcha text $captcha\n";
              echo "Enter captcha text as expected: ";
              $stdin = fopen("php://stdin", "r");
              $captcha_expected = rtrim(fgets($stdin, 1024));
              if ($captcha != $captcha_expected) {
                if ($is_verbose) echo "Reporting bad $captcha\n";
                $result = file_get_contents("http://$domain/res.php?key=".$apikey.'&action=reportbad&id='.$captcha_id);
                if ($is_verbose) echo "Reporting bad returned $result\n";
                return false;
              } else
                return $captcha;
            }
          }
      }

      return false;
  }
}
?>