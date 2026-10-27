<?php
declare(strict_types=1);

if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$config=require __DIR__.'/../config/database.php';

try{
    $pdo=new PDO(
        "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("ALTER TABLE users MODIFY role ENUM('admin','manager','leader','student','company') NOT NULL DEFAULT 'student'");

    $password='FET2026*';
    $hash=password_hash($password,PASSWORD_DEFAULT);
    $accounts=[
        ['1000000001','Administrador FET','admin@fet.edu.co','admin','Tecnología'],
        ['1076502055','Estudiante Demostración','estudiante@fet.edu.co','student','Ingeniería de Software'],
        ['900123456','Empresa de Pasantías Demo','empresa@fet.edu.co','company','Empresa Aliada de Pasantías'],
    ];
    $find=$pdo->prepare('SELECT id FROM users WHERE identification=? OR email=? LIMIT 1');
    $update=$pdo->prepare('UPDATE users SET identification=?,full_name=?,email=?,password_hash=?,role=?,program=?,status="active",failed_attempts=0,locked_until=NULL WHERE id=?');
    $insert=$pdo->prepare('INSERT INTO users(identification,full_name,email,password_hash,role,program,status) VALUES(?,?,?,?,?,?,"active")');

    foreach($accounts as [$identification,$name,$email,$role,$program]){
        $find->execute([$identification,$email]);
        $id=$find->fetchColumn();
        if($id){
            $update->execute([$identification,$name,$email,$hash,$role,$program,$id]);
        }else{
            $insert->execute([$identification,$name,$email,$hash,$role,$program]);
        }
    }

    echo "Cuentas de demostración actualizadas.\n";
    foreach($accounts as [$identification,$name,$email,$role]){
        echo "$role | $email | $identification | $password\n";
    }
}catch(Throwable $e){
    fwrite(STDERR,$e->getMessage()."\n");
    exit(1);
}
