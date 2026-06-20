<?php

$root="../../../";
include($root."src/_include/config.php");


// users:

$users = array(
    "Giulio" => 36,
    "Alex" => 40,
    "Roberta" => 36,
    "peppe"=>36
);

$jobs = array(
    "HPCTODO-2023.csv" => 856,
    "LomacTODO.csv" => 865,
    "ViaggidialegioTODO.csv" => 866,
    "SerialmindsTODO.csv" => 871,
    "SeleggoTODO.csv" => 879,
    "StratagemmiTODO.csv" => 880,
    "PianoC.csv" => 873,
    "Smask.csv" => 867,
    "GMT.csv" => 874,
    "Thiella.csv" => 870,
    "IME.csv"=>876,
    "Digitalmerenda.csv" => 875,    
    "collegio.csv" => 872
);

/*

(
    [0] => Tipo
    [1] => Cosa
    [2] => Ore
    [3] => Stato
    [4] => Priorità
    [5] => Data consegna
    [6] => Chi
    [7] => Note
)

*/

// scan files

$mesi = array("gennaio" => "01",
    "febbraio" => "02",
    "marzo" => "03",
    "aprile" => "04",
    "maggio" => "05",
    "giugno" => "06",
    "luglio" => "07",
    "agosto" => "08",
    "settembre" => "09",
    "ottobre" => "10",
    "novembre" => "11",
    "dicembre" => "12"
);

$dir = "./datacsv/";
$files = scandir($dir);
$files = array_diff($files, array('.', '..'));

foreach($files as $file) {
     $ext = pathinfo($file, PATHINFO_EXTENSION);
     echo $file."<br>";
     if ($ext == "csv") {
         $arData = array();
         $arSql = array();

         $f = fopen($dir.$file, "r");
         $r=0;
         while($row = fgetcsv($f))  {
             $r++;
             $arData[] = $row;
             $cd_utente = isset($users[$row[6]]) ? $users[$row[6]] : "";
             $cd_job = isset($jobs[$file]) ? $jobs[$file] : "";
             if($cd_utente && $cd_job) {
                
                $de_nota = $row[1];
                $nu_ore = str_replace(",",".", $row[2] );
                $mese = substr($row[5],0,strpos($row[5],"-"));
                $dt_giorno = str_replace($mese, $mesi[$mese], $row[5]);
                $dt_giorno = preg_replace("/\-23$/","-2023",$dt_giorno);
                $ar = explode("-", $dt_giorno);
                $dt_giorno = $ar[2] . "-" . $ar[0] . "-" . $ar[1];
   
                $dt_giorno =  date("Y-m-d",strtotime($dt_giorno));
                $en_tipo='ore su progetto';
                $tipo_ora = 269;
  
                $q = execute_scalar("select count(1) from ".DB_PREFIX."ts_ore where 
                    cd_utente = '".$cd_utente."' and
                    cd_job = '".$cd_job."' and
                    dt_giorno = '".$dt_giorno."' and
                    cd_tipoora = '".$tipo_ora."'
                ", 0);
                if($q == 0) {
                    $sql = "INSERT INTO `".DB_PREFIX."ts_ore` (`id_ora`, `cd_utente`, `de_nota`, `cd_job`, `nu_ore`, `dt_giorno`, `cd_tipoora`) VALUES (NULL, '".$cd_utente."', '".addslashes($de_nota." [".$r."-".$file."]")."', '".$cd_job."', '".$nu_ore."', '".$dt_giorno."', '".$tipo_ora."');";
                    $arSql[] = $sql;
                }
   
   
             }

         }
         fclose($f);
         
        

         foreach($arSql as $sql) {
            $conn->query($sql) or die($conn->error);
            echo $sql."<br>";
         }
    }

    
    
}

