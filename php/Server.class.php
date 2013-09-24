<?php

class Server
{
    public static function getChmod($file, $precision)
    {
        $chmod = substr(sprintf('%o', fileperms($file)), $precision);
        return $chmod;
    }

    public static function getUptime()
    {
        $fd = fopen('/proc/uptime', 'r');
        $ar_buf = split(' ', fgets($fd, 4096));
        fclose($fd);
        $sys_ticks = trim($ar_buf[0]);

        $min   = $sys_ticks / 60;
        $hours = $min / 60;
        $days  = floor($hours / 24);
        $hours = floor($hours - ($days * 24));
        $min   = floor($min - ($days * 60 * 24) - ($hours * 60));

        $result = null;
        if ($days != 0) $result = $days.' jours et ';
        if ($hours != 0) $result .= $hours.' h ';
        $result .= $min.' min';

        return $result;
    }

    public static function load_average()
    {
        $load_average = sys_getloadavg();
        if ($load_average[0] < 5)
            $info_charge = '<em class="text-success">Charge faible, conditions optimales.</em>';
        elseif ($load_average[0] < 10)
            $info_charge = '<em class="text-warning">Charge élévée, risque de ralentissement sur le serveur.</em>';
        else
            $info_charge = '<em class="text-danger">Charge très élévée, risque de gros ralentissement sur le serveur.</em>';

        return array( 'load_average' => $load_average,
                      'info_charge' => $info_charge );
    }

    public static function logout($realm)
    {
        if ( preg_match( "#Basic#i", $_SERVER['HTTP_AUTHORIZATION'] ) || $_SERVER[AUTH_TYPE] == 'Basic' )
        {
            header('WWW-Authenticate: Basic realm="'.$realm.'"');
            header('HTTP/1.0 401 Unauthorized');
            echo "<script>document.location.href = 'http://google.fr'</script>";
            exit;
        }

        if ( preg_match( "#Digest#i", $_SERVER['HTTP_AUTHORIZATION'] ) || $_SERVER[AUTH_TYPE] == 'Digest' )
        {
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Digest realm="'.$realm.'",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"');
            echo "<script>document.location.href = 'http://google.fr'</script>";
            exit;
        }
    }

    public static function FileDownload( $file_config_name, $conf_ext_prog, $user)
    {
        $createFile = fopen('../conf/users/'.$user.'/'.$file_config_name, 'a+');
        ftruncate($createFile,0);
        fputs($createFile, $conf_ext_prog);
        fclose($createFile);

        set_time_limit(0);
        $path_file_name = '../conf/users/'.$user.'/'.$file_config_name;
        $file_name = $file_config_name;
        $file_size = filesize($path_file_name);

        ini_set('zlib.output_compression', 0);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$file_name.'"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: '.$file_size);
        ob_clean();
        flush();
        readfile($path_file_name);
        exit;
    }
}