<?php
/**
 * captchaLogin.php
 * 
 * Datei zum händischen Einloggen eines Nicht-Bot-Accounts.
 * 
 * Ausführung über die CLI!
 */
$captchalogin = 1;
require_once(__DIR__.DIRECTORY_SEPARATOR."apiCall.php");

/**
 * Prüfen ob das Script in der CLI läuft.
 */
if(php_sapi_name() != 'cli') {
  die("Das Script kann nur per Terminal ausgeführt werden.\n");
}

/**
 * Dateien-Löschfunktion
 */
function delFiles() {
  global $captchafile;
  global $tokenfile;
  global $solvedcaptcha;
  if(file_exists($solvedcaptcha)) {
    unlink($solvedcaptcha);
  }
  if(file_exists($tokenfile)) {
    unlink($tokenfile);
  }
  if(file_exists($captchafile)) {
    unlink($captchafile);
  }
}

/**
 * Definierung der Captcha-Dateien
 */
$captchafile = __DIR__.DIRECTORY_SEPARATOR."__captcha.png";
$tokenfile = __DIR__.DIRECTORY_SEPARATOR."__token.txt";
$solvedcaptcha = __DIR__.DIRECTORY_SEPARATOR."__captcha.txt";

/**
 * Prüfung ob diese Dateien bereits existieren, falls nicht wird ein neues Captcha angefragt.
 */
if((!file_exists($tokenfile) OR !file_exists($solvedcaptcha)) OR !file_exists($captchafile)) {
  /**
   * Prüfen, ob vielleicht eine einzelne der Dateien existiert, falls ja: löschen.
   */
  delFiles();

  /**
   * Der Bust ist ein Float-Wert zwischen 0 und 1, aber nicht 1.
   * Wie die JavaScript Funktion Math.random() die auf pr0gramm dazu verwendet wird.
   * @see https://developer.mozilla.org/de/docs/Web/JavaScript/Reference/Global_Objects/Math/math.random
   */
  $captcha = apiCall("https://pr0gramm.com/api/user/captcha?bust=".(mt_rand()/mt_getrandmax()));

  /**
   * Schreiben des Captchabildes.
   * Wichtig ist, dass der "data:image/png;base64,"-Teil vor dem
   * eigentlichen Base64 String entfernt wird.
   */
  $fp = fopen($captchafile, "w+");
  fwrite($fp, base64_decode(explode(",", $captcha['captcha'])[1]));
  fclose($fp);

  /**
   * Das Token muss auch gespeichert werden.
   */
  $fp = fopen($tokenfile, "w+");
  fwrite($fp, $captcha['token']);
  fclose($fp);

  /**
   * Eine leere Datei wird angelegt, in die das Captcha-Ergebnis geschrieben werden muss.
   */
  $fp = fopen($solvedcaptcha, "w+");
  fwrite($fp, "");
  fclose($fp);

  /**
   * Beenden mit der Fehlermeldung, dass ein Captcha erforderlich ist.
   */
  die("Captcha wurde abgerufen.\n\nVorgehensweise:\n".$captchafile."\nbetrachten und in\n".$solvedcaptcha."\ndie Lösung schreiben, dann erneut aufrufen.\n");
} else {
  /**
   * Tokendatei und Datei mit Lösung des Captchas liegen vor.
   * Prüfung, ob die Tokendatei und die Lösung korrekt sind.
   */
  if(preg_match("/[0-9a-fA-F]{32}/", file_get_contents($tokenfile), $token) AND preg_match("/[0-9a-zA-Z]{5}/", file_get_contents($solvedcaptcha), $captcha)) {
    $login = apiCall("https://pr0gramm.com/api/user/login", array("name" => $pr0Username, "password" => $pr0Password, "captcha" => $captcha[0], "token" => $token[0]));
    /**
     * Wenn der Login nicht erfolgreich war, dann wird noch geprüft wieso dies so
     * ist. In jedem Fall wird die Ausführung unterbrochen.
     */
    if($login['success'] !== TRUE) {
      /**
       * Wenn kein Ban vorliegt, dann kann es einerseits an einem falschen
       * Passwort, oder daran liegen, dass es sich nicht um einen Bot-Account
       * handelt.
       */
      if($login['ban'] === NULL) {
        if($login['error'] == 'invalidLogin') {
          /**
           * Falsches Passwort
           */
          delFiles();
          die("Login - falsches Passwort\n");
        } elseif($login['error'] == 'invalidCaptcha') {
          /**
           * Captcha ungültig.
           */
          delFiles();
          die("Login - Captcha ungültig. Bitte erneut probieren.\n");
        } else {
          /**
           * Unbekannter Fehler mit Ausgabe der Response
           */
          delFiles();
          die("Login - unbekannter Fehler\n".json_encode($login)."\n");
        }
      } else {
        /**
         * Account gesperrt
         */
        delFiles();
        die("Login - der Account ist gesperrt\n");
      }
    }
  } else {
    delFiles();
    die("Dateien ungültig. Bitte erneut probieren.\n");
  }
}
delFiles();
die("Eingeloggt. Bitte apiCall.php aufrufen.\n");
?>
