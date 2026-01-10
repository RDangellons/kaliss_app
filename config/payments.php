<?php
declare(strict_types=1);

function mp_access_token(): string {
  // 1) variable de entorno (Hostinger lo permite)
  //$t = getenv('MP_ACCESS_TOKEN');
  //if ($t && trim($t) !== '') return trim($t);

  // 2) fallback (solo local) -> pon aquí tu token de TEST si quieres
  return 'TEST-6122827775414678-011012-f216aacaef5cd2eb42694df264e67252-593904910';

}

function mp_public_key(): string{
    return 'TEST-dfa6f6b7-9ca5-4471-977b-fa5b62d178e8';
}

function base_url(): string {
  // Ajusta si ya tienes un dominio fijo:
   return 'https://danyel-pseudospherical-nicky.ngrok-free.dev/kaliss_app';

 /* $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  $scheme = $isHttps ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $path = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
  // Esto intenta devolver la carpeta del proyecto
  return $scheme . '://' . $host . $path;

  */
}
