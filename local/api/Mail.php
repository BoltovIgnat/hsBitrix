<?
namespace Dbbo;

class Mail {
    public static function Send($to, $subject, $message, $additional_headers,$type="bizproc") {
        
        $res = @mail($to, $subject, $message, $additional_headers);

        $fh = fopen($_SERVER['DOCUMENT_ROOT'].'/emailLog.txt', 'a+');
        fwrite($fh, $type." ".$to ." ". (new \DateTime('now'))->format('d.m.Y H:i:s')."res: ".$res.PHP_EOL);
        fclose($fh);

        return $res;
    }
}