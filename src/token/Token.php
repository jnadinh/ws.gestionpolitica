<?php

class Token {
            
    public function generarToken($usuario) {
        mt_srand();
        $random=null;
        for($i=1;$i<=16;$i++) {
          $random .= mt_rand (0, 9);
        }
        
        return ( $usuario . "S" . $random );
    }

    public function generarTokenPassword() {
      mt_srand();
      $random=null;
      for($i=1;$i<=16;$i++) {
        $random .= mt_rand (0, 9);
      }
      
      return ( $random );
  }    
}

?>