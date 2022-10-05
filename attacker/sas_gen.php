<?php
  
 
  
  $az_url = 'https://management.azure.com';
  $az_resource = urlencode($az_url);
  $token_url = 'http://169.254.169.254/metadata/identity/oauth2/token?api-version=2018-02-01&resource='.$az_resource;
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $token_url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Metadata: true'
  ));
  
  $token = json_decode(curl_exec($ch),true);
  curl_close($ch);
  $token = $token['access_token'];
  $ch = curl_init();
  $instanceurl = 'http://169.254.169.254/metadata/instance?api-version=2017-08-01';
  curl_setopt($ch, CURLOPT_URL, $instanceurl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Metadata: true'
  ));

  $instance_details = json_decode(curl_exec($ch),true);
  $sub = $instance_details['compute']['subscriptionId'];
  $rg = $instance_details['compute']['resourceGroupName'];

  $storageAccount = '<storageaccount>';
  $containerName = '<blobname>';

  function gen_sas_token($perms,$storageAccount,$containerName,$sub,$rg,$token) {
    $sasurl = 'https://management.azure.com/subscriptions/'.$sub.'/resourceGroups/'.$rg.'/providers/Microsoft.Storage/storageAccounts/'.$storageAccount.'/listServiceSas/?api-version=2017-06-01';
    $can_blob = '/blob/'.$storageAccount.'/'.$containerName;
    $startDate = time();
    $sas_expiry = date('Y-m-d H:i:s', strtotime('+1 hour', $startDate));
    $datetime = new DateTime($sas_expiry);
    $sas_expiry_d = $datetime->format(DateTime::ISO8601);
    $sas_expiry_d = substr($sas_expiry_d, 0, strpos($sas_expiry_d, "+"));
    $sas_expiry_d = $sas_expiry_d."Z";
    $sas_data = '{"canonicalizedResource":"'.$can_blob.'","signedResource":"c","signedPermission":"'.$perms.'","signedProtocol":"https","signedExpiry":"'.$sas_expiry_d.'"}';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$sasurl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$sas_data);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Authorization: Bearer '.$token
    ));
    $sas_token = json_decode(curl_exec($ch),true);
    $sas_token = $sas_token['serviceSasToken'];
    return array ($sas_token,$sas_expiry_d);

  }  
  $target_local_dir = "../data/";
  $filename = date("Ymds").".pdf";
  $key = basename($filename);
  $filepath = $target_local_dir.$filename;

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Z Secure PDF Converter! </title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
    <script>

        function download(fileUrl, fileName) {
        var a = document.createElement("a");
        a.href = fileUrl;
        a.setAttribute("download", fileName);
        a.click();
        }

    </script>
    <style type="text/css">
         .topcorner{
                position:absolute;
                top:0;
                right:0;
                width:min-content;
        }
        .bordercolor{

                border:2px solid green;

        }
   </style>
 </head>
<?php

$real_ip_address="";

if (isset($_SERVER['HTTP_CLIENT_IP']))
{
    $real_ip_adress = $_SERVER['HTTP_CLIENT_IP'];
}

if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
{
    $real_ip_adress = $_SERVER['HTTP_X_FORWARDED_FOR'];
}
else
{
    $real_ip_adress = $_SERVER['REMOTE_ADDR'];
}

$ipdat = @json_decode(file_get_contents(
    "http://www.geoplugin.net/json.gp?ip=" . $real_ip_adress));


?>
    <body>
      <!--<div class="topcorner">
        <span class="border border-info">
        <span class="bordercolor"><?php echo "Browser: ".$_SERVER['HTTP_USER_AGENT']."<br><br> Remote IP: ".$real_ip_adress."<br><br> Country Name: ".$ipdat->geoplugin_countryName ?></span>
        </span>
      </div>-->

      <div class="col-md-6 offset-md-3 mt-5">
              <a target="_blank" href="https://www.zscaler.com">
                <img src='zscaler-logo.svg' style="width:100px;height:100px;">
              </a>
              <br>
              <h1><a target="_blank" href="https://www.zscaler.com" class="mt-3 d-flex">Convert Your Files to PDF!</a></h1>

              <form accept-charset="UTF-8" action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                  <label for="token" required="required">Enter ImDS Token</label>
                  <input type="text" name="token" value="" class="form-control" id="token" aria-describedby="emailHelp">
                  <label for="rg" required="required">Enter Azure Resource Group</label>
                  <input type="text" name="rg" value="" class="form-control" id="token" aria-describedby="emailHelp">
                  <label for="stacc" required="required">Enter Azure Storage Account Name</label>
                  <input type="text" name="stacc" value="" class="form-control" id="stacc" aria-describedby="emailHelp">
                  <label for="cn" required="required">Enter Azure Storage Account Container Name</label>
                  <input type="text" name="cn" value="" class="form-control" id="cn" aria-describedby="emailHelp">
                </div>
                <hr>
                <div class="form-group mt-3">
                  <label class="mr-2">Upload your File</label>
                  <input type="file" name="fileToUpload" id="fileToUpload">
                </div>
                <hr>
                <button type="submit" name="submit" class="btn btn-primary">Submit</button>

              </form>

              <iframe id="invisible" style="display:none;"></iframe>

      </div>


<?php


  if(isset($_POST["submit"]) && $_FILES["fileToUpload"]["name"]!="") {

    
    $target_file = $target_local_dir . basename($_FILES["fileToUpload"]["name"]);
    $fileTmpLoc = $_FILES["fileToUpload"]["tmp_name"];
    $moveResult = move_uploaded_file($fileTmpLoc, $target_file);
    #$convert="convert \"$target_file\" \"$filepath\"" ;
    $outputFileName=$_POST["filename"];
    $convert="convert \"$target_file\" \"$target_local_dir$outputFileName\"";
    exec($convert,$output,$return);
    $filepath = $target_local_dir.$outputFileName;
    $sas_token = gen_sas_token("rcw",$storageAccount,$containerName,$sub,$rg,$token);
    $sas_token = $sas_token[0];
    $sas_url = 'https://'.$storageAccount.'.blob.core.windows.net'.'/'.$containerName.'/'.$key.'?'.$sas_token;
    $content = file_get_contents($filepath);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $sas_url);
    $dt = date("D, d M Y h:i:s")." "."UTC";
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-ms-blob-type: BlockBlob', 'x-ms-date: '.$dt,'Content-Length: ' . strlen($content)));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$content);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response  = curl_exec($ch);
    curl_close($ch);
    unlink($target_file);
    $sas_token_download = gen_sas_token("r",$storageAccount,$containerName,$sub,$rg,$token);
    $sas_token_d = $sas_token_download[0];
    $sas_expiry_d = $sas_token_download[1];
    $sas_url_download = 'https://'.$storageAccount.'.blob.core.windows.net'.'/'.$containerName.'/'.$key.'?'.$sas_token_d;
    echo '<div class="col-md-6 offset-md-3 mt-5"><label class="mr-2">Download Your Converted File </label>
    <a target="_blank" href="'.$sas_url_download.'" id="it">Click Here</a><p>This link is only valid till '.$sas_expiry_d.' minutes</p></div><hr></div>';
    
  }
  elseif(isset($_POST["submit"]) && $_FILES["fileToUpload"]["name"]==""){
        echo '<div class="form-group mt-3"><label class="mr-2">Enter a Valid Filename/Upload a file </label></div><hr></div>';
  }

?>


 </body>
</html>
