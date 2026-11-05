<?php
declare(strict_types=1);
/**
 * Respaldo SQL verificable de la base de datos (solo administrador).
 * Genera el volcado con PDO para no depender de mysqldump en el servidor.
 * Incluye un hash SHA-256 del contenido para verificar su integridad.
 */
require __DIR__.'/includes/bootstrap.php';
require_role('admin');
if($_SERVER['REQUEST_METHOD']!=='POST'){ redirect('users.php'); }
require_csrf();

$pdo=db();
$tables=$pdo->query('SHOW FULL TABLES WHERE Table_type="BASE TABLE"')->fetchAll(PDO::FETCH_NUM);
$out="-- Respaldo de Pasantías FET\n-- Generado: ".date('Y-m-d H:i:s')." por ".current_user()['email']."\n-- Base de datos: ".scalar('SELECT DATABASE()')."\n\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
$counts=[];
foreach($tables as [$t]){
    if($t==='remember_tokens') continue; // tokens de sesión: no se respaldan por seguridad
    $create=$pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1];
    $out.="DROP TABLE IF EXISTS `$t`;\n$create;\n\n";
    $rows=$pdo->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC); $counts[$t]=count($rows);
    foreach(array_chunk($rows,100) as $chunk){
        $cols='`'.implode('`,`',array_keys($chunk[0])).'`';
        $vals=array_map(fn($r)=>'('.implode(',',array_map(fn($v)=>$v===null?'NULL':$pdo->quote((string)$v),$r)).')',$chunk);
        $out.="INSERT INTO `$t` ($cols) VALUES\n".implode(",\n",$vals).";\n";
    }
    $out.="\n";
}
$out.="SET FOREIGN_KEY_CHECKS=1;\n";
$hash=hash('sha256',$out);
$summary=implode(', ',array_map(fn($t,$n)=>"$t=$n",array_keys($counts),$counts));
$out.="-- Registros: $summary\n-- SHA-256 del contenido anterior: $hash\n";
audit('backup','database',null,'SHA-256 '.substr($hash,0,16));
$name='respaldo_intranet_fet_'.date('Ymd_His').'.sql';
header('Content-Type: application/sql; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$name.'"');
header('Content-Length: '.strlen($out));
echo $out;
