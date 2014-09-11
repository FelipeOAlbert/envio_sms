<?
    
    //echo intval(0);
    
    require_once('nuswit_sms.php');
    
    $sms = new NuswitSMS('x7BwniLTQXec+qYrdjglH5LFHNUMzkWugxyl661TK70=');
    
    //if (NuswitSMS::has_curl()) { print("curl is installed\n"); }
    
    //$sms->setDebug(true);
    
    if($_POST){
       
        //echo '<script>alert("HUE!");</script>';
        
        //print_r($_POST);die();
        
        if(empty($_POST['tel']) and empty($_POST['message'])){
            echo '<script>alert("Os campos precisam estar preenchidos!");</script>';
        }else{
            
            $retorno = $sms->send($_POST['tel'], $_POST['message']);
            
            var_dump($retorno);die();
            
            if(intval($retorno) >= 0){
                echo '<script>alert("SMS Enviado com sucesso!");</script>';
            }else{
                
                switch($retorno){
                    
                    case '-1':
                        echo '<script>alert("Não foi possível entregar o SMS. Tente novamente mais tarde.");</script>';
                    break;
                    case '-2':
                        echo '<script>alert("Crédito insuficiente na conta. Recarregue e tente novamente.");</script>';
                    break;
                    case '-3':
                        echo '<script>alert("Nosso sistema não suporta a operadora destino.");</script>';
                    break;
                    case '-4':
                        echo '<script>alert("Erro de comunicação com a operadora. Tente novamente mais tarde.");</script>';
                    break;
                    case '-6':
                    case '-9':
                    case '-13':
                        echo '<script>alert("Número de destino inválido.");</script>';
                    break;
                    case '-7':
                        echo '<script>alert("Mensagem inválida (tamanho, ou charset com problema).");</script>';
                    break;
                    case '-8':
                        echo '<script>alert("Número ou texto do remetente inválido.");</script>';
                    break;
                }
                
            }
        }
    }
?>

<html>
    <head>
        <title>Envio SMS</title>
    </head>
    
    <body>
        
        <form action="" method="post">
            
            <label>Digite o celular (ex: 55-11-8888-0000)</label>
            <br>
            <input type="number" name="tel" value="" required/>
            
            <br>
            
            <label>Mensagem:</label>
            <br>
            <textarea name="message" required></textarea>
            <br>
            <br>
            <input type="submit" value="Enviar" />
        </form>
        
    </body>
</html>